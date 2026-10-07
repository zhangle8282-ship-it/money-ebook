<?php
/**
 * 서식 있는 글(블로그 본문) 정리.
 * 네이버 블로그 · 웹페이지 · 워드 등에서 복사해 붙여 넣은 글의 굵게 · 기울임 · 밑줄 · 취소선 · 글자색 · 형광펜 · 글자 크기 ·
 * 가운데/오른쪽 정렬 · 목록 · 인용 · 링크 · 사진 · 이모지는 살리고, 스크립트 · 이벤트 속성 · 외부 틀(iframe) · 남의 CSS 같은
 * 위험하거나 지저분한 것은 모두 지웁니다. 붙여 넣을 때, 저장할 때, 보여 줄 때 이 함수를 거칩니다.
 *
 * 남기는 모양: <p|h2|h3|li|blockquote|figure style="text-align:center">, <span class="fs-sm|fs-lg|fs-xl" style="color:#…;background-color:#…">,
 *             <strong> <em> <u> <s> <sub> <sup> <a href> <img src alt> <ul> <ol> <hr> <br> <figcaption>
 */

const RICH_TAGS = array('p', 'h2', 'h3', 'blockquote', 'ul', 'ol', 'li', 'figure', 'figcaption', 'hr', 'br',
    'strong', 'em', 'u', 's', 'span', 'a', 'img', 'sub', 'sup');
const RICH_RENAME = array('b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'ins' => 'u', 'cite' => 'em',
    'h1' => 'h2', 'h4' => 'h3', 'h5' => 'h3', 'h6' => 'h3', 'font' => 'span', 'mark' => 'span', 'big' => 'span', 'small' => 'span',
    'center' => 'p', 'pre' => 'p');
// 내용까지 통째로 지우는 태그
const RICH_DROP = array('script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea',
    'noscript', 'template', 'svg', 'math', 'link', 'meta', 'head', 'title', 'video', 'audio', 'canvas', 'map', 'area',
    'frame', 'frameset', 'applet', 'base', 'source', 'track', 'dialog');
// 풀어 낼 때 문단 경계가 되는 틀(div 등). 안에 글만 있으면 문단(p)이 되고, 문단이 섞여 있으면 틀만 벗깁니다.
const RICH_BOXES = array('div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav', 'table', 'tbody', 'thead',
    'tfoot', 'tr', 'td', 'th', 'caption', 'dl', 'dt', 'dd', 'address', 'details', 'summary', 'fieldset', 'legend');
const RICH_BLOCK = array('p', 'h2', 'h3', 'blockquote', 'ul', 'ol', 'li', 'figure', 'figcaption', 'hr');
const RICH_ALIGN = array('p', 'h2', 'h3', 'li', 'blockquote', 'figure');
const RICH_INLINE_WRAP = array('strong', 'em', 'u', 's', 'sub', 'sup', 'span', 'a');
const RICH_NAMED_COLORS = array(
    'black' => '000000', 'white' => 'ffffff', 'red' => 'ff0000', 'blue' => '0000ff', 'green' => '008000', 'orange' => 'ffa500',
    'purple' => '800080', 'gray' => '808080', 'grey' => '808080', 'yellow' => 'ffff00', 'navy' => '000080', 'maroon' => '800000',
    'teal' => '008080', 'olive' => '808000', 'lime' => '00ff00', 'aqua' => '00ffff', 'cyan' => '00ffff', 'fuchsia' => 'ff00ff',
    'magenta' => 'ff00ff', 'silver' => 'c0c0c0', 'pink' => 'ffc0cb', 'brown' => 'a52a2a', 'gold' => 'ffd700', 'skyblue' => '87ceeb',
);

/** 붙여 넣거나 저장한 HTML → 안전하고 깔끔한 HTML. $allowData: 붙여 넣는 중에만 data: 사진을 잠깐 남김(바로 파일로 바꿈) */
function rich_clean_html($html, $allowData = false)
{
    $html = trim(str_replace(array("\xE2\x80\x8B", "\xEF\xBB\xBF"), '', (string) $html));
    if ($html === '') {
        return '';
    }
    // 워드 · 한글에서 복사할 때 붙는 조건부 주석 등
    $html = preg_replace('/<!--.*?-->/s', '', $html);
    $doc = dom_from_html($html);
    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }
    rich_clean_children($doc, $root, $allowData);
    rich_wrap_inline_runs($doc, $root);
    rich_tidy_paragraphs($root);
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim(str_replace("\xC2\xA0", '&nbsp;', $out));
}

function rich_clean_children(DOMDocument $doc, DOMNode $parent, $allowData)
{
    $nodes = array();
    foreach ($parent->childNodes as $c) {
        $nodes[] = $c;
    }
    foreach ($nodes as $node) {
        if ($node instanceof DOMText) {
            // 네이버 에디터가 빈 줄에 넣는 보이지 않는 글자(&#8203;) 지우기
            $text = str_replace(array("\xE2\x80\x8B", "\xEF\xBB\xBF"), '', $node->nodeValue);
            if ($text === '') {
                $parent->removeChild($node);
            } elseif ($text !== $node->nodeValue) {
                $node->nodeValue = $text;
            }
            continue;
        }
        if (!($node instanceof DOMElement)) {
            $parent->removeChild($node);
            continue;
        }
        $tag = strtolower($node->localName ?: $node->nodeName);
        if (in_array($tag, RICH_DROP, true)) {
            $parent->removeChild($node);
            continue;
        }
        $props = rich_props($node, $tag);
        // <b style="font-weight:normal"> (구글 문서가 글 전체를 감싸는 방식)은 굵게가 아님
        if (($tag === 'b' || $tag === 'strong') && !empty($props['normal'])) {
            $tag = 'span';
        }
        $tag = RICH_RENAME[$tag] ?? $tag;
        rich_clean_children($doc, $node, $allowData);
        if (in_array($tag, RICH_BOXES, true)) {
            rich_unbox($doc, $node, $props);
            continue;
        }
        if (!in_array($tag, RICH_TAGS, true)) {
            unwrap_element($node);
            continue;
        }
        if ($tag !== strtolower($node->nodeName)) {
            $node = rename_element($doc, $node, $tag);
        }
        rich_set_attributes($doc, $node, $tag, $props, $allowData);
    }
}

/** 요소의 모양(스타일 · 예전 속성 · 네이버 에디터 클래스)에서 남길 것만 뽑기 */
function rich_props(DOMElement $el, $tag)
{
    $p = array();
    foreach (explode(';', (string) $el->getAttribute('style')) as $decl) {
        $kv = explode(':', $decl, 2);
        if (count($kv) < 2) {
            continue;
        }
        $k = strtolower(trim($kv[0]));
        $v = strtolower(trim(str_replace('!important', '', $kv[1])));
        if ($k === 'color') {
            $p['color'] = rich_color($v, 'fg');
        } elseif ($k === 'background-color' || $k === 'background') {
            $c = rich_color($v, 'bg');
            if ($c !== '') {
                $p['bg'] = $c;
            }
        } elseif ($k === 'font-weight') {
            if ($v === 'bold' || $v === 'bolder' || (ctype_digit($v) && (int) $v >= 600)) {
                $p['bold'] = true;
            } elseif ($v === 'normal' || $v === 'lighter' || (ctype_digit($v) && (int) $v < 600)) {
                $p['normal'] = true;
            }
        } elseif ($k === 'font-style' && ($v === 'italic' || $v === 'oblique')) {
            $p['italic'] = true;
        } elseif ($k === 'text-decoration' || $k === 'text-decoration-line') {
            if (strpos($v, 'underline') !== false) {
                $p['underline'] = true;
            }
            if (strpos($v, 'line-through') !== false) {
                $p['strike'] = true;
            }
        } elseif ($k === 'font-size') {
            $p['size'] = rich_size($v);
        } elseif ($k === 'text-align' && in_array($v, array('center', 'right', 'justify'), true)) {
            $p['align'] = $v;
        }
    }
    $class = (string) $el->getAttribute('class');
    if (preg_match('/align-(center|right|justify)\b/', $class, $m)) {
        $p['align'] = $m[1];
    }
    if (preg_match('/\bse-fs-fs(\d+)\b/', $class, $m)) {
        $p['size'] = rich_size($m[1] . 'px');
    }
    // 이미 정리된 글(저장된 본문)을 다시 정리할 때 우리 크기 표시 유지
    if (preg_match('/(?:^|\s)fs-(sm|lg|xl)(?:\s|$)/', $class, $m)) {
        $p['size'] = $m[1];
    }
    $align = strtolower((string) $el->getAttribute('align'));
    if (in_array($align, array('center', 'right', 'justify'), true)) {
        $p['align'] = $align;
    }
    if ($tag === 'font') {
        if ($el->hasAttribute('color')) {
            $p['color'] = rich_color(strtolower($el->getAttribute('color')), 'fg');
        }
        $size = (int) $el->getAttribute('size');
        if ($size > 0) {
            $p['size'] = $size <= 2 ? 'sm' : ($size >= 5 ? 'xl' : ($size === 4 ? 'lg' : ''));
        }
    } elseif ($tag === 'big') {
        $p['size'] = 'lg';
    } elseif ($tag === 'small') {
        $p['size'] = 'sm';
    } elseif ($tag === 'mark' && empty($p['bg'])) {
        $p['bg'] = '#fff59d';
    } elseif ($tag === 'center') {
        $p['align'] = 'center';
    }
    return array_filter($p);
}

/**
 * 색 값 → #rrggbb. 글자색은 기본 글자색(검정 · 진한 회색)이면 버리고, 배경색은 흰색 · 투명이면 버립니다.
 * (브라우저가 복사할 때 모든 글자에 기본 색을 붙여 넣기 때문)
 */
function rich_color($v, $kind)
{
    $v = trim($v);
    $rgb = null;
    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $v, $m)) {
        $h = strlen($m[1]) === 3 ? $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2] : $m[1];
        $rgb = array(hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)));
    } elseif (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*([\d.]+%?)\s*)?\)$/', $v, $m)) {
        if (isset($m[4]) && $m[4] !== '' && (float) $m[4] < 0.3) {
            return '';
        }
        $rgb = array(min(255, (int) $m[1]), min(255, (int) $m[2]), min(255, (int) $m[3]));
    } elseif (isset(RICH_NAMED_COLORS[$v])) {
        $h = RICH_NAMED_COLORS[$v];
        $rgb = array(hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)));
    }
    if (!$rgb) {
        return '';
    }
    $max = max($rgb);
    $min = min($rgb);
    if ($kind === 'fg' && $max - $min <= 24 && $max <= 110) {
        return '';
    }
    if ($kind === 'bg' && $min >= 245) {
        return '';
    }
    return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
}

/** 글자 크기 → sm | lg | xl | '' (보통). 네이버 블로그 기본 크기(15~16px) 기준 */
function rich_size($v)
{
    $words = array('xx-small' => 'sm', 'x-small' => 'sm', 'small' => 'sm', 'large' => 'lg', 'x-large' => 'xl', 'xx-large' => 'xl', 'xxx-large' => 'xl');
    if (isset($words[$v])) {
        return $words[$v];
    }
    if (!preg_match('/^([\d.]+)(px|pt|em|rem|%)$/', $v, $m)) {
        return '';
    }
    $n = (float) $m[1];
    $px = $m[2] === 'pt' ? $n * 4 / 3 : ($m[2] === 'em' || $m[2] === 'rem' ? $n * 16 : ($m[2] === '%' ? $n * 0.16 : $n));
    if ($px >= 24) {
        return 'xl';
    }
    if ($px >= 19) {
        return 'lg';
    }
    return $px > 0 && $px <= 12.5 ? 'sm' : '';
}

/** 링크 주소: http(s) · mailto · tel · 사이트 안 주소만 */
function rich_safe_href($href)
{
    $href = trim(html_entity_decode((string) $href, ENT_QUOTES, 'UTF-8'));
    return preg_match('~^(https?://[^\s"<>]+|mailto:[^\s"<>]+|tel:[0-9+\-() ]+|/(?!/)[^\s"<>]*|#[\w\-]+)$~i', $href) ? $href : '';
}

/** 사진 주소: http(s) · 우리 사이트 /uploads/ (붙여 넣는 중엔 data: 사진도 잠깐) */
function rich_safe_src($src, $allowData)
{
    $src = trim(html_entity_decode((string) $src, ENT_QUOTES, 'UTF-8'));
    if ($allowData && preg_match('~^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=\s]+$~i', $src)) {
        return $src;
    }
    return preg_match('~^(https?://[^\s"<>]+|/uploads/[^\s"<>]+)$~i', $src) ? $src : '';
}

function rich_set_attributes(DOMDocument $doc, DOMElement $el, $tag, $props, $allowData)
{
    $keep = array();
    if ($tag === 'a') {
        $href = rich_safe_href($el->getAttribute('href'));
        if ($href === '') {
            unwrap_element($el);
            return;
        }
        $keep['href'] = $href;
        if (preg_match('~^https?://~i', $href) && parse_url($href, PHP_URL_HOST) !== parse_url(base_url(), PHP_URL_HOST)) {
            $keep['target'] = '_blank';
            $keep['rel'] = 'noopener';
        }
    } elseif ($tag === 'img') {
        // 네이버 블로그는 처음엔 흐린 작은 사진을 보여 주고 진짜 주소를 data-lazy-src 에 둡니다.
        $src = '';
        foreach (array('data-lazy-src', 'data-src', 'src') as $attr) {
            $src = rich_safe_src($el->getAttribute($attr), $allowData);
            if ($src !== '') {
                break;
            }
        }
        if ($src === '') {
            $el->parentNode->removeChild($el);
            return;
        }
        $keep['src'] = $src;
        $keep['alt'] = str_cut(trim(preg_replace('/\s+/u', ' ', $el->getAttribute('alt'))), 200, '');
    }
    while ($el->attributes->length) {
        $el->removeAttribute($el->attributes->item(0)->nodeName);
    }
    foreach ($keep as $k => $v) {
        $el->setAttribute($k, $v);
    }
    if (in_array($tag, array('img', 'br', 'hr'), true)) {
        return;
    }
    if (!empty($props['align']) && in_array($tag, RICH_ALIGN, true)) {
        $el->setAttribute('style', 'text-align:' . $props['align']);
    }
    // 글자색 · 형광펜 · 크기: span 이면 그대로, 다른 태그면 안쪽을 span 으로 한 번 감쌉니다.
    $style = (!empty($props['color']) ? 'color:' . $props['color'] . ';' : '') . (!empty($props['bg']) ? 'background-color:' . $props['bg'] . ';' : '');
    $size = !empty($props['size']) && !in_array($tag, array('h2', 'h3'), true) ? 'fs-' . $props['size'] : '';
    $target = $el;
    if ($style !== '' || $size !== '') {
        if ($tag !== 'span') {
            $target = rich_wrap_inside($doc, $el, 'span');
        }
        if ($style !== '') {
            $target->setAttribute('style', rtrim($style, ';'));
        }
        if ($size !== '') {
            $target->setAttribute('class', $size);
        }
    }
    // 스타일로 준 굵게 · 기울임 · 밑줄 · 취소선은 태그로 바꿉니다.
    foreach (array('bold' => 'strong', 'italic' => 'em', 'underline' => 'u', 'strike' => 's') as $k => $wrap) {
        if (!empty($props[$k]) && $tag !== $wrap && !($wrap === 'strong' && in_array($tag, array('h2', 'h3'), true))) {
            rich_wrap_inside($doc, $target, $wrap);
        }
    }
    // 꾸밈이 없는 span, 글자도 사진도 없는 꾸밈 태그는 벗깁니다.
    if ($tag === 'span' && !$el->hasAttributes()) {
        unwrap_element($el);
    } elseif (in_array($tag, RICH_INLINE_WRAP, true) && $tag !== 'a' && !rich_has_content($el)) {
        if (trim($el->textContent) === '' && $el->getElementsByTagName('img')->length === 0 && $el->getElementsByTagName('br')->length === 0) {
            unwrap_element($el);
        }
    }
}

/** 안쪽 내용을 새 태그로 감싸고 그 태그를 돌려줌: <p>글</p> → <p><span>글</span></p> */
function rich_wrap_inside(DOMDocument $doc, DOMElement $el, $tag)
{
    $wrap = $doc->createElement($tag);
    while ($el->firstChild) {
        $wrap->appendChild($el->firstChild);
    }
    $el->appendChild($wrap);
    return $wrap;
}

function rich_has_content(DOMElement $el)
{
    return trim(str_replace("\xC2\xA0", ' ', $el->textContent)) !== '' || $el->getElementsByTagName('img')->length > 0;
}

/** div 같은 틀 벗기기: 글만 있으면 문단으로, 문단이 섞여 있으면 글 덩어리를 문단으로 묶고 틀만 벗김 */
function rich_unbox(DOMDocument $doc, DOMElement $el, $props)
{
    $hasBlock = false;
    foreach ($el->childNodes as $c) {
        if ($c instanceof DOMElement && in_array(strtolower($c->nodeName), RICH_BLOCK, true)) {
            $hasBlock = true;
            break;
        }
    }
    if (!$hasBlock) {
        if (!rich_has_content($el) && $el->getElementsByTagName('br')->length === 0) {
            $el->parentNode->removeChild($el);
            return;
        }
        $p = rename_element($doc, $el, 'p');
        rich_set_attributes($doc, $p, 'p', $props, false);
        return;
    }
    rich_wrap_inline_runs($doc, $el, $props);
    unwrap_element($el);
}

/** 문단 밖에 흩어진 글 · 꾸밈 태그를 이어지는 것끼리 문단(p)으로 묶기 */
function rich_wrap_inline_runs(DOMDocument $doc, DOMNode $box, $props = array())
{
    $run = array();
    $flush = function ($before) use (&$run, $doc, $box, $props) {
        $text = '';
        foreach ($run as $n) {
            $text .= $n->textContent;
        }
        $hasImg = false;
        foreach ($run as $n) {
            if ($n instanceof DOMElement && ($n->nodeName === 'img' || $n->getElementsByTagName('img')->length)) {
                $hasImg = true;
            }
        }
        if (trim(str_replace("\xC2\xA0", ' ', $text)) === '' && !$hasImg) {
            foreach ($run as $n) {
                $box->removeChild($n);
            }
        } else {
            $p = $doc->createElement('p');
            $box->insertBefore($p, $before);
            foreach ($run as $n) {
                $p->appendChild($n);
            }
            if (!empty($props['align'])) {
                $p->setAttribute('style', 'text-align:' . $props['align']);
            }
        }
        $run = array();
    };
    $nodes = array();
    foreach ($box->childNodes as $c) {
        $nodes[] = $c;
    }
    foreach ($nodes as $n) {
        $block = $n instanceof DOMElement && in_array(strtolower($n->nodeName), RICH_BLOCK, true);
        if ($block) {
            if ($run) {
                $flush($n);
            }
        } elseif ($n instanceof DOMElement && $n->nodeName === 'br' && $run === array()) {
            $box->removeChild($n);
        } else {
            $run[] = $n;
        }
    }
    if ($run) {
        $flush(null);
    }
}

/** 빈 문단 정리: 줄 띄움용 빈 문단은 한 개까지만, 맨 앞 · 맨 뒤 빈 문단은 지움 */
function rich_tidy_paragraphs(DOMNode $root)
{
    $prevEmpty = true;
    $nodes = array();
    foreach ($root->childNodes as $c) {
        $nodes[] = $c;
    }
    foreach ($nodes as $n) {
        if (!($n instanceof DOMElement)) {
            if (trim($n->textContent) === '') {
                $root->removeChild($n);
            }
            continue;
        }
        $empty = in_array($n->nodeName, array('p', 'h2', 'h3'), true) && !rich_has_content($n);
        if ($empty && $prevEmpty) {
            $root->removeChild($n);
            continue;
        }
        if ($empty) {
            while ($n->firstChild) {
                $n->removeChild($n->firstChild);
            }
            $n->appendChild($n->ownerDocument->createElement('br'));
        }
        $prevEmpty = $empty;
    }
    $last = $root->lastChild;
    while ($last instanceof DOMElement && $last->nodeName === 'p' && !rich_has_content($last)) {
        $root->removeChild($last);
        $last = $root->lastChild;
    }
}

/** 글자만(검색 설명 · 글자 수 · 읽는 시간용) */
function rich_text($html)
{
    $t = preg_replace('~<(br|/p|/h[23]|/li|/blockquote|/figcaption)\b[^>]*>~i', ' ', (string) $html);
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = str_replace(array("\xE2\x80\x8B", "\xEF\xBB\xBF"), '', $t);
    return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $t));
}

/* ───────── 붙여 넣은 사진 가져오기 ───────── */

const RICH_IMAGE_MAX = 10485760; // 10MB
const RICH_IMPORT_SECONDS = 25;  // 한 번 붙여 넣을 때 사진 가져오기에 쓰는 시간 한도

/**
 * 붙여 넣은 글 속 다른 사이트 사진과 data: 사진을 우리 서버(/uploads/blog)로 가져와 주소를 바꿉니다.
 * 다른 사이트에서 사진 퍼가기를 막아 두면 나중에 안 보이기 때문입니다. 반환: [HTML, 가져온 수, 못 가져온 주소들]
 */
function rich_import_images($html)
{
    if ($html === '' || stripos($html, '<img') === false) {
        return array($html, 0, array());
    }
    $doc = dom_from_html($html);
    $root = $doc->getElementsByTagName('div')->item(0);
    $ourHost = parse_url(base_url(), PHP_URL_HOST);
    $deadline = microtime(true) + RICH_IMPORT_SECONDS;
    $done = array();
    $imported = 0;
    $failed = array();
    $imgs = array();
    foreach ($root->getElementsByTagName('img') as $img) {
        $imgs[] = $img;
    }
    foreach ($imgs as $img) {
        $src = $img->getAttribute('src');
        if (strncmp($src, '/uploads/', 9) === 0) {
            continue;
        }
        if (preg_match('~^https?://~i', $src) && parse_url($src, PHP_URL_HOST) === $ourHost) {
            $path = (string) parse_url($src, PHP_URL_PATH);
            if (strncmp($path, '/uploads/', 9) === 0) {
                $img->setAttribute('src', $path);
            }
            continue;
        }
        if (!isset($done[$src])) {
            $bytes = '';
            if (strncmp($src, 'data:', 5) === 0) {
                $bytes = (string) base64_decode(preg_replace('/\s+/', '', substr($src, strpos($src, ',') + 1)), true);
            } elseif (microtime(true) < $deadline) {
                $bytes = rich_fetch_image($src, $deadline);
            }
            $done[$src] = $bytes !== '' ? rich_store_image_bytes($bytes) : '';
        }
        if ($done[$src] !== '') {
            $img->setAttribute('src', $done[$src]);
            $imported++;
        } elseif (strncmp($src, 'data:', 5) !== 0) {
            $failed[] = $src;
        }
    }
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return array($out, $imported, array_values(array_unique($failed)));
}

/** 사진 내려받기(공개 인터넷 주소만, 넘겨주기 3번까지, 10MB까지). 실패하면 '' */
function rich_fetch_image($url, $deadline)
{
    if (!function_exists('curl_init')) {
        return '';
    }
    for ($hop = 0; $hop < 4; $hop++) {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';
        if (!in_array($scheme, array('http', 'https'), true) || $host === '') {
            return '';
        }
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? array($host) : (gethostbynamel($host) ?: array());
        if (!$ips) {
            return '';
        }
        foreach ($ips as $ip) {
            // 내부망 · 내 컴퓨터 주소로는 요청하지 않음
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return '';
            }
        }
        $left = $deadline - microtime(true);
        if ($left < 1) {
            return '';
        }
        $body = '';
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RESOLVE => array($host . ':' . $port . ':' . $ips[0]),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => (int) max(1, min(10, $left)),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; GreenCleaningBlog/1.0)',
            CURLOPT_HTTPHEADER => array('Accept: image/avif,image/webp,image/png,image/jpeg,image/gif,*/*;q=0.5'),
            CURLOPT_HEADER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$body) {
                $body .= $chunk;
                return strlen($body) > RICH_IMAGE_MAX ? 0 : strlen($chunk);
            },
        ));
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $location = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        if (in_array($code, array(301, 302, 303, 307, 308), true) && $location !== '') {
            $url = $location;
            continue;
        }
        return $code === 200 && strlen($body) <= RICH_IMAGE_MAX ? $body : '';
    }
    return '';
}

/** 내려받은 사진 저장(JPG · PNG · GIF · WEBP만). 반환: /uploads/blog/…, 사진이 아니면 '' */
function rich_store_image_bytes($bytes)
{
    if ($bytes === '' || strlen($bytes) > RICH_IMAGE_MAX) {
        return '';
    }
    $info = @getimagesizefromstring($bytes);
    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp');
    if (!$info || !isset($types[$info[2]])) {
        return '';
    }
    $dir = UPLOAD_DIR . '/blog';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = pathinfo(random_name('x'), PATHINFO_FILENAME) . '.' . $types[$info[2]];
    return file_put_contents($dir . '/' . $name, $bytes) === strlen($bytes) ? '/uploads/blog/' . $name : '';
}
