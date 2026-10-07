<?php
/**
 * 그린청소 검색어 페이지: 검색어(지역 · 업종)마다 따로 있는 소개 페이지. 주소는 /음성공장청소 처럼 검색어 그대로입니다.
 * 한 페이지가 한 검색어만 깊게 다뤄야 그 검색어에서 잘 나오므로, 첫 화면 한 장에 몰아 두지 않고 나눕니다.
 * 처음 열릴 때 기본 페이지(app/drafts.php)를 한 번 넣어 두고, 그 뒤로는 관리자 › 검색어 페이지에서 고칩니다.
 * 본문 쓰는 법은 블로그와 같습니다(## 소제목, - 목록, **굵게** …).
 */

const LANDING_STATUS = array('published' => '공개', 'hidden' => '숨김');
// 다른 화면이 쓰는 주소는 검색어 페이지 주소로 쓸 수 없습니다.
const LANDING_RESERVED = array('admin', 'blog', 'privacy', 'health', 'inquiry', 'uploads', 'assets', 'icons', 'robots', 'sitemap', 'rss', 'favicon', 'index');

/** 처음 한 번 기본 검색어 페이지를 넣습니다(관리자가 다 지워도 다시 넣지 않음). */
function landing_seed()
{
    if (gc('landing_seeded') === '1') {
        return;
    }
    save_settings(array('gc_landing_seeded' => '1'));
    if ((int) q_value('SELECT COUNT(*) FROM landing_pages') > 0) {
        return;
    }
    foreach (landing_defaults() as $i => $p) {
        try {
            q_insert('landing_pages', $p + array('cover' => '', 'seo_title' => '', 'sort' => $i + 1, 'status' => 'published', 'created_at' => now(), 'updated_at' => now()));
        } catch (PDOException $e) {
            // 같은 주소가 이미 있으면(동시에 들어온 요청) 건너뜁니다.
        }
    }
}

/** 공개된 검색어 페이지(순서대로) */
function landing_public()
{
    static $list = null;
    if ($list === null) {
        landing_seed();
        $list = q_all("SELECT * FROM landing_pages WHERE status = 'published' ORDER BY sort, id");
    }
    return $list;
}

function find_landing($id)
{
    return q_one('SELECT * FROM landing_pages WHERE id = ?', array((int) $id));
}

function find_landing_by_slug($slug)
{
    return q_one('SELECT * FROM landing_pages WHERE slug = ?', array((string) $slug));
}

/** 주소: /음성공장청소 */
function landing_url($page, $absolute = false)
{
    return ($absolute ? base_url() : '') . '/' . rawurlencode($page['slug']);
}

/** 주소에 쓸 이름: 띄어쓰기는 붙이고, 한글 · 영문 · 숫자 말고는 - 로 */
function landing_slugify($text)
{
    $s = function_exists('mb_strtolower') ? mb_strtolower((string) $text, 'UTF-8') : strtolower((string) $text);
    $s = preg_replace('/\s+/u', '', $s);
    $s = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $s), '-');
    return trim(mb_substr($s, 0, 60), '-');
}

/** 주소로 쓸 수 있는지: 비어 있지 않고, 숫자만은 아니고, 다른 화면 주소와 겹치지 않음 */
function landing_slug_ok($slug)
{
    return $slug !== '' && !ctype_digit($slug) && !in_array(strtolower($slug), LANDING_RESERVED, true);
}
