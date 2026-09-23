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
