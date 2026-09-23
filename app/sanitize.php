<?php
/**
 * HTML 정리: 허용한 태그만 남기고 속성·스크립트는 모두 지웁니다.
 * EPUB 본문에서 뽑은 미리보기 HTML을 안전하게 보여 줄 때 씁니다.
 */

// 미리보기 본문에 남길 태그. 제목은 모두 h3(장 제목)으로 맞춥니다.
const PREVIEW_TAGS = array('p', 'br', 'h3', 'strong', 'b', 'em', 'i', 'blockquote', 'ul', 'ol', 'li', 'hr');
const PREVIEW_RENAME = array('h1' => 'h3', 'h2' => 'h3', 'h4' => 'h3', 'h5' => 'h3', 'h6' => 'h3');
// 내용까지 통째로 지우는 태그
const DROP_TAGS = array('script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea',
    'noscript', 'template', 'svg', 'math', 'link', 'meta', 'head', 'title', 'img', 'image', 'video', 'audio', 'nav', 'aside');

function dom_from_html($html)
{
    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return $doc;
}

/** 허용 태그만 남긴 HTML. 속성은 전부 지웁니다. */
function sanitize_preview_html($html)
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }
    $doc = dom_from_html($html);
    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }
    sanitize_children($doc, $root);
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function sanitize_children(DOMDocument $doc, DOMNode $parent)
{
    $children = array();
    foreach ($parent->childNodes as $c) {
        $children[] = $c;
    }
    foreach ($children as $node) {
        if ($node instanceof DOMText) {
            continue;
        }
        if (!($node instanceof DOMElement)) {
            $parent->removeChild($node);
            continue;
        }
        $tag = strtolower($node->localName ?: $node->nodeName);
        if (in_array($tag, DROP_TAGS, true)) {
            $parent->removeChild($node);
            continue;
        }
        if (array_key_exists($tag, PREVIEW_RENAME)) {
            $node = rename_element($doc, $node, PREVIEW_RENAME[$tag]);
            $tag = PREVIEW_RENAME[$tag];
        }
        sanitize_children($doc, $node);
        if (!in_array($tag, PREVIEW_TAGS, true)) {
            unwrap_element($node);
            continue;
        }
        while ($node->attributes->length) {
            $node->removeAttribute($node->attributes->item(0)->nodeName);
        }
        if (!in_array($tag, array('br', 'hr'), true) && trim(str_replace("\xC2\xA0", ' ', $node->textContent)) === '') {
            $parent->removeChild($node);
        }
    }
}

function rename_element(DOMDocument $doc, DOMElement $el, $tag)
{
    $new = $doc->createElement($tag);
    while ($el->firstChild) {
        $new->appendChild($el->firstChild);
    }
    $el->parentNode->replaceChild($new, $el);
    return $new;
}

function unwrap_element(DOMNode $el)
{
    $parent = $el->parentNode;
    while ($el->firstChild) {
        $parent->insertBefore($el->firstChild, $el);
    }
    $parent->removeChild($el);
}

/* ───────── 뷰어 본문 ───────── */

// 뷰어에 남길 태그와 허용 속성(나머지 속성·EPUB 자체 CSS는 지우고 사이트 글꼴로 다시 그립니다)
const READER_TAGS = array(
    'p' => array(), 'br' => array(), 'h2' => array(), 'h3' => array(), 'hr' => array(),
    'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'u' => array(), 's' => array(),
    'sup' => array(), 'sub' => array(), 'small' => array(), 'blockquote' => array(), 'pre' => array(), 'code' => array(),
    'ul' => array(), 'ol' => array('start'), 'li' => array(), 'dl' => array(), 'dt' => array(), 'dd' => array(),
    'table' => array(), 'thead' => array(), 'tbody' => array(), 'tfoot' => array(), 'tr' => array(), 'caption' => array(),
    'th' => array('colspan', 'rowspan'), 'td' => array('colspan', 'rowspan'),
    'figure' => array(), 'figcaption' => array(), 'img' => array('src', 'alt'), 'a' => array('href'),
);
const READER_RENAME = array('h1' => 'h2', 'h4' => 'h3', 'h5' => 'h3', 'h6' => 'h3');
const READER_DROP = array('script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea',
    'noscript', 'template', 'svg', 'math', 'link', 'meta', 'head', 'title', 'video', 'audio', 'nav');

/**
 * EPUB 장 HTML 정리. $rewrite($kind, $value): kind 는 src|href|id, 바꾼 값을 돌려주고 null 이면 지웁니다.
 * (img 는 통째로, a 는 글자만 남기고, id 는 속성만)
 */
function sanitize_reader_html($html, $rewrite)
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }
    $doc = dom_from_html($html);
    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }
    reader_children($doc, $root, $rewrite);
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function reader_children(DOMDocument $doc, DOMNode $parent, $rewrite)
{
    $children = array();
    foreach ($parent->childNodes as $c) {
        $children[] = $c;
    }
    foreach ($children as $node) {
        if ($node instanceof DOMText) {
            continue;
        }
        if (!($node instanceof DOMElement)) {
            $parent->removeChild($node);
            continue;
        }
        $tag = strtolower($node->localName ?: $node->nodeName);
        if (in_array($tag, READER_DROP, true)) {
            $parent->removeChild($node);
            continue;
        }
        if (array_key_exists($tag, READER_RENAME)) {
            $id = $node->getAttribute('id');
            $node = rename_element($doc, $node, READER_RENAME[$tag]);
            if ($id !== '') {
                $node->setAttribute('id', $id);
            }
            $tag = READER_RENAME[$tag];
        }
        reader_children($doc, $node, $rewrite);
        $id = $node->getAttribute('id');
        if (!array_key_exists($tag, READER_TAGS)) {
            // 알 수 없는 태그(div, span, section 등)는 벗기되, 링크 목적지가 될 수 있는 id 는 빈 표식으로 남깁니다.
            if ($id !== '') {
                $mark = $doc->createElement('a');
                $mark->setAttribute('id', $rewrite('id', $id));
                $parent->insertBefore($mark, $node);
            }
            unwrap_element($node);
            continue;
        }
        $allowed = READER_TAGS[$tag];
        $remove = array();
        foreach ($node->attributes as $attr) {
            if (!in_array(strtolower($attr->name), $allowed, true)) {
                $remove[] = $attr->name;
            }
        }
        foreach ($remove as $name) {
            $node->removeAttribute($name);
        }
        if ($id !== '') {
            $node->setAttribute('id', $rewrite('id', $id));
        }
        if ($tag === 'img') {
            $src = $rewrite('src', $node->getAttribute('src'));
            if ($src === null) {
                $parent->removeChild($node);
                continue;
            }
            $node->setAttribute('src', $src);
            $node->setAttribute('loading', 'lazy');
        } elseif ($tag === 'a' && $node->hasAttribute('href')) {
            $href = $rewrite('href', $node->getAttribute('href'));
            if ($href === null) {
                $node->removeAttribute('href');
            } else {
                $node->setAttribute('href', $href);
                if (preg_match('~^https?:~i', $href)) {
                    $node->setAttribute('target', '_blank');
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }
        if (($tag === 'ol') && $node->hasAttribute('start') && !ctype_digit($node->getAttribute('start'))) {
            $node->removeAttribute('start');
        }
        foreach (array('colspan', 'rowspan') as $n) {
            if ($node->hasAttribute($n) && !ctype_digit($node->getAttribute($n))) {
                $node->removeAttribute($n);
            }
        }
    }
}
