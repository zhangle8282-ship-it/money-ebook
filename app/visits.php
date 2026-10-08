<?php
/**
 * 그린청소 유입 경로: 어느 검색 사이트에서 어떤 검색어로 들어왔는지 하루 단위 숫자로만 셉니다.
 * - 사이트 밖에서 처음 들어온 순간에만 셉니다(사이트 안에서 옮겨 다니는 건 세지 않음).
 * - 같은 사람이 30분 안에 다시 들어오면 세지 않고, 검색 로봇 · 관리자도 세지 않습니다.
 * - IP · 브라우저 정보 같은 개인을 알아볼 수 있는 것은 남기지 않고, 날짜 · 출처 · 검색어 · 횟수만 남깁니다.
 */

const VISIT_COOKIE = 'gc_in';
const VISIT_KEEP_DAYS = 400;
const VISIT_PERIODS = array(1 => '오늘', 7 => '최근 7일', 30 => '최근 30일', 90 => '최근 90일');
// 출처: [이름, 검색 사이트인지]
const VISIT_SOURCES = array(
    'naver' => array('네이버 검색', true),
    'google' => array('구글 검색', true),
    'daum' => array('다음 검색', true),
    'bing' => array('빙 검색', true),
    'zum' => array('줌 검색', true),
    'yahoo' => array('야후 검색', true),
    'ddg' => array('덕덕고 검색', true),
    'naver_blog' => array('네이버 블로그', false),
    'naver_place' => array('네이버 지도 · 플레이스', false),
    'naver_cafe' => array('네이버 카페', false),
    'naver_etc' => array('네이버 (그 밖)', false),
    'ai' => array('AI 검색 (ChatGPT 등)', false),
    'instagram' => array('인스타그램', false),
    'facebook' => array('페이스북', false),
    'youtube' => array('유튜브', false),
    'kakao' => array('카카오 · 티스토리', false),
    'daangn' => array('당근', false),
    'link' => array('표시한 링크 (utm)', false),
    'other' => array('다른 사이트', false),
    'direct' => array('직접 방문 · 앱 · 즐겨찾기', false),
);
// [주소 이름 규칙, 출처, 검색어가 든 칸] — 위에서부터 먼저 맞는 것
const VISIT_RULES = array(
    array('~(^|\.)search\.naver\.com$~', 'naver', 'query'),
    array('~(^|\.)blog\.naver\.com$~', 'naver_blog', ''),
    array('~(^|\.)(map\.naver\.com|place\.naver\.com|naver\.me)$~', 'naver_place', ''),
    array('~(^|\.)cafe\.naver\.com$~', 'naver_cafe', ''),
    array('~(^|\.)naver\.com$~', 'naver_etc', ''),
    array('~(^|\.)(chatgpt\.com|openai\.com|perplexity\.ai|copilot\.microsoft\.com|gemini\.google\.com|claude\.ai|wrtn\.ai)$~', 'ai', ''),
    array('~(^|\.)google\.[a-z.]+$~', 'google', 'q'),
    array('~(^|\.)bing\.com$~', 'bing', 'q'),
    array('~(^|\.)search\.daum\.net$~', 'daum', 'q'),
    array('~(^|\.)search\.zum\.com$~', 'zum', 'query'),
    array('~(^|\.)search\.yahoo\.com$~', 'yahoo', 'p'),
    array('~(^|\.)duckduckgo\.com$~', 'ddg', 'q'),
    array('~(^|\.)instagram\.com$~', 'instagram', ''),
    array('~(^|\.)(facebook\.com|fb\.me)$~', 'facebook', ''),
    array('~(^|\.)(youtube\.com|youtu\.be)$~', 'youtube', ''),
    array('~(^|\.)(kakao\.com|daum\.net|tistory\.com)$~', 'kakao', ''),
    array('~(^|\.)daangn\.com$~', 'daangn', ''),
);
const VISIT_BOT_RE = '~bot|crawl|spider|slurp|yeti|daum(oa)?[ /]|bingpreview|facebookexternalhit|embedly|preview|headless|lighthouse|python|curl|wget|httpclient|okhttp|java/|go-http|monitor|uptime|scan~i';

/** 공개 화면이 열릴 때(화면을 보내기 전에) 부릅니다. 바깥에서 처음 들어온 것만 하루 숫자에 1을 더합니다. */
function visit_track()
{
    if (SITE_MODE !== 'cleaning' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || !empty($_COOKIE[VISIT_COOKIE])) {
        return;
    }
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match(VISIT_BOT_RE, $ua)) {
        return;
    }
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $refHost = visit_host($ref);
    if ($refHost !== '' && in_array($refHost, array(visit_host(base_url()), preg_replace('/^www\./', '', strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''))))), true)) {
        return; // 사이트 안에서 옮겨 다닌 것
    }
    if (current_admin()) {
        return;
    }
    list($source, $keyword) = visit_classify($ref, $_GET);
    try {
        visit_add(date('Y-m-d'), $source, $keyword);
    } catch (Throwable $e) {
        error_log('visit: ' . $e->getMessage());
        return;
    }
    if (!headers_sent()) {
        setcookie(VISIT_COOKIE, '1', array('expires' => time() + 1800, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax'));
    }
}

/** 주소의 호스트(소문자, www. 뺌) */
function visit_host($url)
{
    return preg_replace('/^www\./', '', strtolower((string) parse_url((string) $url, PHP_URL_HOST)));
}

/** 들어온 곳 → [출처, 검색어(없으면 '')]. utm_source 를 붙인 링크는 그 이름으로 */
function visit_classify($ref, $get)
{
    $utm = visit_word((string) ($get['utm_source'] ?? ''), 30);
    if ($utm !== '') {
        return array('link', $utm);
    }
    $host = visit_host($ref);
    if ($host === '') {
        return array('direct', '');
    }
    parse_str((string) parse_url($ref, PHP_URL_QUERY), $q);
    foreach (VISIT_RULES as $r) {
        if (preg_match($r[0], $host)) {
            $word = $r[2] !== '' && isset($q[$r[2]]) && is_string($q[$r[2]]) ? visit_word($q[$r[2]]) : '';
            return array($r[1], $r[1] === 'ai' ? $host : $word);
        }
    }
    return array('other', str_cut($host, 60, ''));
}

/** 검색어 다듬기: 글자 깨짐(옛 EUC-KR) 고치고, 띄어쓰기 하나로, 길이 제한 */
function visit_word($s, $max = 60)
{
    $s = (string) $s;
    if ($s !== '' && !mb_check_encoding($s, 'UTF-8')) {
        $s = (string) @iconv('CP949', 'UTF-8//IGNORE', $s);
    }
    $s = trim(preg_replace('/\s+/u', ' ', $s));
    return str_cut(mb_strtolower($s, 'UTF-8'), $max, '');
}

/** 그날 · 출처 · 검색어 칸에 1 더하기(없으면 새로). 가끔 오래된 기록을 정리 */
function visit_add($day, $source, $keyword)
{
    if (q('UPDATE visit_sources SET hits = hits + 1 WHERE day = ? AND source = ? AND keyword = ?', array($day, $source, $keyword))->rowCount()) {
        return;
    }
    try {
        q('INSERT INTO visit_sources (day, source, keyword, hits) VALUES (?, ?, ?, 1)', array($day, $source, $keyword));
    } catch (PDOException $e) {
        // 같은 순간에 같은 칸을 만든 요청이 있으면 더하기만
        q('UPDATE visit_sources SET hits = hits + 1 WHERE day = ? AND source = ? AND keyword = ?', array($day, $source, $keyword));
        return;
    }
    if (mt_rand(1, 50) === 1) {
        q('DELETE FROM visit_sources WHERE day < ?', array(date('Y-m-d', strtotime('-' . VISIT_KEEP_DAYS . ' days'))));
    }
}

/** 관리자 › 유입 경로: 기간 안의 출처별 · 검색어별 숫자 */
function visit_report($days)
{
    $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
    $search = array_keys(array_filter(VISIT_SOURCES, function ($s) {
        return $s[1];
    }));
    $in = implode(',', array_fill(0, count($search), '?'));
    $sources = array();
    foreach (q_all('SELECT source, SUM(hits) AS n FROM visit_sources WHERE day >= ? GROUP BY source ORDER BY n DESC', array($from)) as $r) {
        $sources[$r['source']] = (int) $r['n'];
    }
    $total = array_sum($sources);
    $searchTotal = array_sum(array_intersect_key($sources, array_flip($search)));
    return array(
        'from' => $from,
        'total' => $total,
        'search' => $searchTotal,
        'naver' => array_sum(array_intersect_key($sources, array_flip(array('naver', 'naver_blog', 'naver_place', 'naver_cafe', 'naver_etc')))),
        'direct' => $sources['direct'] ?? 0,
        'sources' => $sources,
        // 검색어 순위(검색 사이트에서 들어온 것만, 검색어를 알려 준 것)
        'keywords' => q_all("SELECT keyword, source, SUM(hits) AS n FROM visit_sources WHERE day >= ? AND source IN ($in) AND keyword <> ''
            GROUP BY keyword, source ORDER BY n DESC, keyword LIMIT 50", array_merge(array($from), $search)),
        // 검색어를 알려 주지 않은 검색(구글 등): 출처 => 횟수
        'hidden' => array_column(q_all("SELECT source, SUM(hits) AS n FROM visit_sources WHERE day >= ? AND source IN ($in) AND keyword = '' GROUP BY source", array_merge(array($from), $search)), 'n', 'source'),
        // 다른 사이트 · AI · 표시한 링크: 어디인지
        'sites' => q_all("SELECT source, keyword, SUM(hits) AS n FROM visit_sources WHERE day >= ? AND source IN ('other', 'ai', 'link') GROUP BY source, keyword ORDER BY n DESC LIMIT 30", array($from)),
        'days' => q_all('SELECT day, SUM(hits) AS n FROM visit_sources WHERE day >= ? GROUP BY day ORDER BY day', array($from)),
    );
}
