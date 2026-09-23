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

/** container.xml → OPF 를 읽어 파일 목록(manifest)·읽는 순서(spine)·목차 파일을 돌려줍니다. */
function epub_package(ZipArchive $zip)
{
    $container = $zip->getFromName('META-INF/container.xml');
    if ($container === false || !preg_match('/full-path\s*=\s*"([^"]+)"/', $container, $m)) {
        return null;
    }
    $opfPath = $m[1];
    $opf = $zip->getFromName($opfPath);
    if ($opf === false) {
        return null;
    }
    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $loaded = $doc->loadXML($opf, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$loaded) {
        return null;
    }
    $base = dirname($opfPath);
    $base = $base === '.' ? '' : $base . '/';

    $manifest = array();
    $nav = '';
    $ncx = '';
    foreach ($doc->getElementsByTagNameNS('*', 'item') as $item) {
        $href = epub_resolve($base . rawurldecode(strtok($item->getAttribute('href'), '#')));
        $type = $item->getAttribute('media-type');
        $props = ' ' . $item->getAttribute('properties') . ' ';
        $manifest[$item->getAttribute('id')] = array('href' => $href, 'type' => $type, 'nav' => strpos($props, ' nav ') !== false);
        if (strpos($props, ' nav ') !== false) {
            $nav = $href;
        } elseif ($type === 'application/x-dtbncx+xml') {
            $ncx = $href;
        }
    }
    $spine = array();
    foreach ($doc->getElementsByTagNameNS('*', 'itemref') as $ref) {
        $item = $manifest[$ref->getAttribute('idref')] ?? null;
        if (!$item || $item['nav'] || $ref->getAttribute('linear') === 'no') {
            continue;
        }
        if ($item['type'] !== '' && strpos($item['type'], 'html') === false) {
            continue;
        }
        $spine[] = $item['href'];
    }
    return array('spine' => $spine, 'nav' => $nav, 'ncx' => $ncx);
}

/** spine 순서의 본문 파일 경로 목록. */
function epub_spine(ZipArchive $zip)
{
    $pkg = epub_package($zip);
    return $pkg ? $pkg['spine'] : array();
}

/** 뷰어용으로 EPUB 을 엽니다. 반환: ['zip' => ZipArchive, 'spine' => [...], ...] 또는 null */
function epub_open($path)
{
    if (!class_exists('ZipArchive') || !is_file($path)) {
        return null;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return null;
    }
    $pkg = epub_package($zip);
    if (!$pkg || !$pkg['spine']) {
        $zip->close();
        return null;
    }
    $pkg['zip'] = $zip;
    return $pkg;
}

/** 목차 파일(EPUB3 nav 또는 EPUB2 ncx)에서 본문 파일 → 제목. */
function epub_toc_titles($epub)
{
    $titles = array();
    $zip = $epub['zip'];
    if ($epub['nav'] !== '' && ($xhtml = $zip->getFromName($epub['nav'])) !== false) {
        $doc = dom_from_html(preg_match('~<body\b[^>]*>(.*)</body>~is', $xhtml, $m) ? $m[1] : $xhtml);
        $navs = $doc->getElementsByTagName('nav');
        $toc = $navs->length ? $navs->item(0) : null;
        foreach ($navs as $n) {
            if (strpos((string) $n->getAttribute('epub:type'), 'toc') !== false) {
                $toc = $n;
                break;
            }
        }
        if ($toc) {
            $dir = dirname($epub['nav']);
            foreach ($toc->getElementsByTagName('a') as $a) {
                $href = epub_resolve(($dir === '.' ? '' : $dir . '/') . rawurldecode(strtok($a->getAttribute('href'), '#')));
                $title = trim(preg_replace('/\s+/u', ' ', $a->textContent));
                if ($title !== '' && !isset($titles[$href])) {
                    $titles[$href] = $title;
                }
            }
        }
    } elseif ($epub['ncx'] !== '' && ($ncx = $zip->getFromName($epub['ncx'])) !== false) {
        $prev = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        if ($doc->loadXML($ncx, LIBXML_NONET)) {
            $dir = dirname($epub['ncx']);
            foreach ($doc->getElementsByTagNameNS('*', 'navPoint') as $point) {
                $label = $point->getElementsByTagNameNS('*', 'text')->item(0);
                $content = $point->getElementsByTagNameNS('*', 'content')->item(0);
                if (!$label || !$content) {
                    continue;
                }
                $href = epub_resolve(($dir === '.' ? '' : $dir . '/') . rawurldecode(strtok($content->getAttribute('src'), '#')));
                $title = trim(preg_replace('/\s+/u', ' ', $label->textContent));
                if ($title !== '' && !isset($titles[$href])) {
                    $titles[$href] = $title;
                }
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
    }
    return $titles;
}

/** 뷰어 목차: spine 순서대로 [['title' => ...], ...]. 한 번 만든 목록은 storage/cache 에 보관합니다. */
function epub_chapters($epub, $path)
{
    $cache = STORAGE_DIR . '/cache/toc-' . md5($path . '|' . @filemtime($path)) . '.json';
    $cached = is_file($cache) ? json_decode((string) file_get_contents($cache), true) : null;
    if (is_array($cached) && count($cached) === count($epub['spine'])) {
        return $cached;
    }
    $titles = epub_toc_titles($epub);
    $list = array();
    foreach ($epub['spine'] as $i => $href) {
        $title = $titles[$href] ?? '';
        if ($title === '') {
            $xhtml = (string) $epub['zip']->getFromName($href);
            if (preg_match('~<h[1-3]\b[^>]*>(.*?)</h[1-3]>~is', $xhtml, $m)) {
                $title = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            }
        }
        $list[] = array('title' => $title !== '' ? str_cut($title, 60) : '본문 ' . ($i + 1));
    }
    if (!is_dir(STORAGE_DIR . '/cache')) {
        @mkdir(STORAGE_DIR . '/cache', 0755, true);
    }
    @file_put_contents($cache, json_encode($list, JSON_UNESCAPED_UNICODE));
    return $list;
}

/** 뷰어에 보여 줄 장 하나의 HTML. 그림은 /read/{책}/asset 로, 다른 장 링크는 ?c= 로 바꿉니다. */
function epub_chapter_html($epub, $index, $bookId)
{
    $href = $epub['spine'][$index];
    $xhtml = $epub['zip']->getFromName($href);
    if ($xhtml === false) {
        return '';
    }
    $body = preg_match('~<body\b[^>]*>(.*)</body>~is', $xhtml, $m) ? $m[1] : $xhtml;
    $dir = dirname($href);
    $dir = $dir === '.' ? '' : $dir . '/';
    $zip = $epub['zip'];
    $spine = array_flip($epub['spine']);
    $rewrite = function ($kind, $value) use ($dir, $zip, $spine, $href, $bookId) {
        if ($kind === 'id') {
            return 'e-' . preg_replace('/[^A-Za-z0-9_-]/', '_', $value);
        }
        if (preg_match('~^(https?:|mailto:)~i', $value)) {
            return $kind === 'href' ? $value : null;
        }
        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $value)) {
            return null;
        }
        $parts = explode('#', $value, 2);
        $frag = isset($parts[1]) && $parts[1] !== '' ? '#e-' . preg_replace('/[^A-Za-z0-9_-]/', '_', rawurldecode($parts[1])) : '';
        $target = $parts[0] === '' ? $href : epub_resolve($dir . rawurldecode($parts[0]));
        if ($kind === 'src') {
            $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
            if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'), true) || $zip->locateName($target) === false) {
                return null;
            }
            return '/read/' . (int) $bookId . '/asset?p=' . rawurlencode($target);
        }
        if ($target === $href) {
            return $frag !== '' ? $frag : null;
        }
        return isset($spine[$target]) ? '?c=' . ($spine[$target] + 1) . $frag : null;
    };
    return sanitize_reader_html($body, $rewrite);
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
