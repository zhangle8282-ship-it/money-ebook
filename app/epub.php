<?php
/**
 * EPUB 미리보기: 책 순서(spine)대로 본문을 읽어 앞부분 N쪽 분량의 HTML을 만듭니다.
 * EPUB은 XHTML 파일을 묶은 zip이라 서버에서 바로 읽을 수 있습니다.
 */

const EPUB_BLOCKS = array('p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'ul', 'ol', 'pre', 'hr');
const EPUB_CONTAINERS = array('div', 'section', 'article', 'main', 'header', 'footer', 'body', 'figure', 'center');

function epub_valid($path)
{
    if (!class_exists('ZipArchive')) {
        // zip 확장이 없으면 EPUB 규격의 첫 항목(mimetype)만 확인합니다.
        $head = (string) file_get_contents($path, false, null, 0, 64);
        return strpos($head, "PK\x03\x04") === 0 && strpos($head, 'application/epub+zip') !== false;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return false;
    }
    $ok = $zip->locateName('META-INF/container.xml') !== false;
    $zip->close();
    return $ok;
}

/**
 * 앞부분 $maxChars 글자 분량의 미리보기 HTML과 책 전체 글자 수.
 * 반환: ['html' => ..., 'total_chars' => ...] 또는 읽을 수 없으면 null
 */
function epub_extract($path, $maxChars)
{
    if (!class_exists('ZipArchive')) {
        return null;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return null;
    }
    $html = '';
    $taken = 0;
    $total = 0;
    foreach (epub_spine($zip) as $href) {
        $xhtml = $zip->getFromName($href);
        if ($xhtml === false) {
            continue;
        }
        foreach (epub_blocks($xhtml) as $block) {
            list($blockHtml, $text, $len) = $block;
            $total += $len;
            if ($taken >= $maxChars) {
                continue;
            }
            $left = $maxChars - $taken;
            // 한 덩어리가 남은 분량보다 훨씬 길면 글자 수에 맞춰 자릅니다.
            if ($len > $left * 1.5) {
                $blockHtml = '<p>' . e(str_cut($text, max(1, $left))) . '</p>';
                $len = $left;
            }
            $html .= $blockHtml . "\n";
            $taken += $len;
        }
    }
    $zip->close();
    return array('html' => sanitize_preview_html($html), 'total_chars' => $total);
}

/** container.xml → OPF → spine 순서의 본문 파일 경로 목록. */
function epub_spine(ZipArchive $zip)
{
    $container = $zip->getFromName('META-INF/container.xml');
    if ($container === false || !preg_match('/full-path\s*=\s*"([^"]+)"/', $container, $m)) {
        return array();
    }
    $opfPath = $m[1];
    $opf = $zip->getFromName($opfPath);
    if ($opf === false) {
        return array();
    }
    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $loaded = $doc->loadXML($opf, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$loaded) {
        return array();
    }
    $base = dirname($opfPath);
    $base = $base === '.' ? '' : $base . '/';

    $manifest = array();
    foreach ($doc->getElementsByTagNameNS('*', 'item') as $item) {
        $props = ' ' . $item->getAttribute('properties') . ' ';
        $manifest[$item->getAttribute('id')] = array(
            'href' => $item->getAttribute('href'),
            'type' => $item->getAttribute('media-type'),
            'nav' => strpos($props, ' nav ') !== false,
        );
    }
    $files = array();
    foreach ($doc->getElementsByTagNameNS('*', 'itemref') as $ref) {
        $item = $manifest[$ref->getAttribute('idref')] ?? null;
        if (!$item || $item['nav'] || $ref->getAttribute('linear') === 'no') {
            continue;
        }
        if ($item['type'] !== '' && strpos($item['type'], 'html') === false) {
            continue;
        }
        $files[] = epub_resolve($base . rawurldecode(strtok($item['href'], '#')));
    }
    return $files;
}

/** a/b/../c.xhtml → a/c.xhtml */
function epub_resolve($path)
{
    $out = array();
    foreach (explode('/', $path) as $part) {
        if ($part === '..') {
            array_pop($out);
        } elseif ($part !== '.' && $part !== '') {
            $out[] = $part;
        }
    }
    return implode('/', $out);
}

/** 본문 파일 하나를 문단 단위로 나눕니다. 반환: [[html, 텍스트, 글자 수], ...] */
function epub_blocks($xhtml)
{
    if (preg_match('~<body\b[^>]*>(.*)</body>~is', $xhtml, $m)) {
        $xhtml = $m[1];
    }
    $doc = dom_from_html($xhtml);
    $root = $doc->getElementsByTagName('div')->item(0);
    $blocks = array();
    if ($root) {
        epub_collect($doc, $root, $blocks);
    }
    return $blocks;
}

function epub_collect(DOMDocument $doc, DOMNode $parent, &$blocks)
{
    $inline = '';
    $flush = function () use (&$inline, &$blocks) {
        $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags($inline), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($text !== '') {
            $blocks[] = array('<p>' . $inline . '</p>', $text, str_len($text));
        }
        $inline = '';
    };
    foreach ($parent->childNodes as $node) {
        if ($node instanceof DOMElement) {
            $tag = strtolower($node->localName ?: $node->nodeName);
            if (in_array($tag, DROP_TAGS, true)) {
                continue;
            }
            if (in_array($tag, EPUB_CONTAINERS, true)) {
                $flush();
                epub_collect($doc, $node, $blocks);
                continue;
            }
            if (in_array($tag, EPUB_BLOCKS, true)) {
                $flush();
                $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $node->textContent));
                if ($text !== '' || $tag === 'hr') {
                    $blocks[] = array($doc->saveHTML($node), $text, str_len($text));
                }
                continue;
            }
        }
        $inline .= $doc->saveHTML($node);
    }
    $flush();
}
