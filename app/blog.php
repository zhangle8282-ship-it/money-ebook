<?php
/**
 * 그린청소 블로그: 관리자가 쓴 글을 검색에 잘 나오도록(제목·설명·대표 주소·구조화 데이터·사이트맵·RSS) 보여 줍니다.
 *
 * 본문 쓰는 법(간단한 표시):
 *   ## 소제목          ### 작은 제목        - 목록         > 인용
 *   **굵게**           [글자](https://주소)   ![사진 설명](/uploads/blog/사진.jpg)
 *   빈 줄로 문단을 나누고, 한 줄 바꿈은 그대로 줄바꿈이 됩니다.
 */

const BLOG_PER_PAGE = 9;
const BLOG_STATUS = array('published' => '공개', 'draft' => '임시저장');

/** 공개된 글 조건(예약 글은 그 시각부터) */
function blog_public_sql()
{
    return "status = 'published' AND published_at IS NOT NULL AND published_at <= ?";
}

function blog_has_posts()
{
    static $has = null;
    if ($has === null) {
        $has = (int) q_value('SELECT COUNT(*) FROM blog_posts WHERE ' . blog_public_sql(), array(now())) > 0;
    }
    return $has;
}

function blog_latest($limit, $exceptId = 0)
{
    return q_all('SELECT * FROM blog_posts WHERE ' . blog_public_sql() . ' AND id <> ? ORDER BY published_at DESC, id DESC LIMIT ' . (int) $limit, array(now(), (int) $exceptId));
}

/** 블로그 목록 한 쪽. 반환: [글들, 전체 수] */
function blog_page($page)
{
    $total = (int) q_value('SELECT COUNT(*) FROM blog_posts WHERE ' . blog_public_sql(), array(now()));
    $posts = q_all('SELECT * FROM blog_posts WHERE ' . blog_public_sql() . ' ORDER BY published_at DESC, id DESC LIMIT ' . BLOG_PER_PAGE . ' OFFSET ' . (max(1, (int) $page) - 1) * BLOG_PER_PAGE, array(now()));
    return array($posts, $total);
}

function find_blog_post($id)
{
    return q_one('SELECT * FROM blog_posts WHERE id = ?', array((int) $id));
}

function blog_is_public($post)
{
    return $post && $post['status'] === 'published' && $post['published_at'] !== null && $post['published_at'] <= now();
}

/** 주소에 쓸 글 이름: 한글·영문·숫자만 남기고 나머지는 - 로 */
function blog_slugify($text)
{
    $s = function_exists('mb_strtolower') ? mb_strtolower((string) $text, 'UTF-8') : strtolower((string) $text);
    $s = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $s), '-');
    return trim(mb_substr($s, 0, 60), '-');
}

/** 글 주소: /blog/번호-글이름 */
function blog_url($post, $absolute = false)
{
    $path = '/blog/' . (int) $post['id'] . ($post['slug'] !== '' ? '-' . rawurlencode($post['slug']) : '');
    return ($absolute ? base_url() : '') . $path;
}

/** 표시(##, **, [ ]( ) 등)를 뺀 글자만 */
function blog_plain($text)
{
    $t = preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '', (string) $text);
    $t = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $t);
    $t = preg_replace('/^\s*(#{2,3}|[-*]|>)\s+/mu', '', $t);
    $t = str_replace('**', '', $t);
    return trim(preg_replace('/\s+/u', ' ', $t));
}

/** 검색 설명: 직접 쓴 요약, 없으면 본문 앞부분 */
function blog_desc($post, $len = 150)
{
    $s = trim((string) $post['summary']);
    return str_cut($s !== '' ? $s : blog_plain($post['body']), $len);
}

/** 대표 사진: 직접 올린 것, 없으면 본문 첫 사진 */
function blog_image($post)
{
    if ($post['cover'] !== '') {
        return $post['cover'];
    }
    return preg_match('/!\[[^\]]*\]\(([^)\s]+)\)/u', (string) $post['body'], $m) && blog_safe_url($m[1]) ? $m[1] : '';
}

function blog_reading_minutes($text)
{
    return max(1, (int) ceil(str_len(blog_plain($text)) / 500));
}

function blog_safe_url($url)
{
    return (bool) preg_match('~^(https?://[^\s"<>]+|/[^\s"<>]*)$~i', $url);
}

/** 글 안의 굵게 · 링크(이미 HTML 이스케이프된 글자에 적용) */
function blog_inline($escaped)
{
    $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped);
    return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
        $url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
        if (!blog_safe_url($url)) {
            return $m[0];
        }
        $external = preg_match('~^https?://~i', $url) && strpos($url, base_url()) !== 0;
        return '<a href="' . e($url) . '"' . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
    }, $s);
}

/** 본문 → HTML(안전한 표시만 허용, 나머지 글자는 모두 그대로 보이게 이스케이프) */
function blog_render($text)
{
    $html = '';
    $para = array();
    $list = false;
    $flush = function () use (&$para, &$html) {
        if ($para) {
            $html .= '<p>' . implode('<br>', array_map('blog_inline', $para)) . "</p>\n";
            $para = array();
        }
    };
    $closeList = function () use (&$list, &$html) {
        if ($list) {
            $html .= "</ul>\n";
            $list = false;
        }
    };
    foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $raw) {
        $line = trim($raw);
        $esc = e($line);
        if ($line === '') {
            $flush();
            $closeList();
        } elseif (preg_match('/^###\s+(.+)$/u', $line, $m)) {
            $flush();
            $closeList();
            $html .= '<h3>' . blog_inline(e($m[1])) . "</h3>\n";
        } elseif (preg_match('/^##\s+(.+)$/u', $line, $m)) {
            $flush();
            $closeList();
            $html .= '<h2>' . blog_inline(e($m[1])) . "</h2>\n";
        } elseif (preg_match('/^[-*]\s+(.+)$/u', $line, $m)) {
            $flush();
            if (!$list) {
                $html .= "<ul>\n";
                $list = true;
            }
            $html .= '<li>' . blog_inline(e($m[1])) . "</li>\n";
        } elseif (preg_match('/^>\s?(.+)$/u', $line, $m)) {
            $flush();
            $closeList();
            $html .= '<blockquote>' . blog_inline(e($m[1])) . "</blockquote>\n";
        } elseif (preg_match('/^!\[([^\]]*)\]\(([^)\s]+)\)$/u', $line, $m) && blog_safe_url($m[2])) {
            $flush();
            $closeList();
            $html .= '<figure><img src="' . e($m[2]) . '" alt="' . e($m[1]) . '" loading="lazy">' . ($m[1] !== '' ? '<figcaption>' . e($m[1]) . '</figcaption>' : '') . "</figure>\n";
        } else {
            $closeList();
            $para[] = $esc;
        }
    }
    $flush();
    $closeList();
    return $html;
}

/** 본문의 소제목 목록(글 위 목차) */
function blog_headings($text)
{
    preg_match_all('/^##\s+(.+)$/mu', (string) $text, $m);
    return array_map(function ($h) {
        return blog_plain($h);
    }, $m[1]);
}
