<?php
/**
 * 그린청소 검색어 페이지: 검색어(지역 · 업종)마다 따로 있는 소개 페이지. 주소는 /음성공장청소 처럼 검색어 그대로입니다.
 * 한 페이지가 한 검색어만 깊게 다뤄야 그 검색어에서 잘 나오므로, 첫 화면 한 장에 몰아 두지 않고 나눕니다.
 * 기본 페이지(app/drafts.php)는 묶음마다 한 번만 넣어 두고, 그 뒤로는 관리자 › 검색어 페이지에서 고칩니다.
 * 본문 쓰는 법은 블로그와 같습니다(## 소제목, - 목록, **굵게** …).
 */

const LANDING_STATUS = array('published' => '공개', 'hidden' => '숨김');
// 다른 화면이 쓰는 주소는 검색어 페이지 주소로 쓸 수 없습니다.
const LANDING_RESERVED = array('admin', 'blog', 'privacy', 'health', 'inquiry', 'uploads', 'assets', 'icons', 'robots', 'sitemap', 'rss', 'favicon', 'index');

/**
 * 기본 검색어 페이지를 묶음마다 한 번씩 넣습니다(관리자가 지운 페이지는 다시 넣지 않음).
 * 1: 처음 다섯 페이지, 2: 청주 · 오창 · 경기 안성. 이미 같은 주소가 있으면 건너뜁니다.
 */
const LANDING_SEED = 2;

function landing_seed()
{
    $done = (int) gc('landing_seeded');
    if ($done >= LANDING_SEED) {
        return;
    }
    save_settings(array('gc_landing_seeded' => (string) LANDING_SEED));
    // 예전처럼 첫 묶음은 페이지가 하나도 없을 때만 넣습니다.
    if ($done < 1 && (int) q_value('SELECT COUNT(*) FROM landing_pages') > 0) {
        $done = 1;
    }
    $sets = array(1 => 'landing_defaults', 2 => 'landing_defaults_regions');
    $sort = (int) q_value('SELECT MAX(sort) FROM landing_pages');
    $added = array();
    foreach ($sets as $no => $fn) {
        if ($no <= $done) {
            continue;
        }
        foreach ($fn() as $p) {
            if (q_value('SELECT COUNT(*) FROM landing_pages WHERE slug = ?', array($p['slug']))) {
                continue;
            }
            try {
                q_insert('landing_pages', $p + array('cover' => '', 'seo_title' => '', 'sort' => ++$sort, 'status' => 'published', 'created_at' => now(), 'updated_at' => now()));
                $added[] = $p;
            } catch (PDOException $e) {
                // 같은 주소가 이미 있으면(동시에 들어온 요청) 건너뜁니다.
            }
        }
    }
    // 이미 열려 있던 사이트에 새로 더했으면 첫 화면도 바뀌었으니 검색 사이트에 알립니다(화면을 다 보낸 뒤).
    if ($added && $done >= 1) {
        save_settings(array('gc_home_updated' => now()));
        indexnow_later(array_merge(array_map('landing_url', $added), array('/')), '새 검색어 페이지');
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
