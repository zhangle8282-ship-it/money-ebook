<?php
/**
 * 그린청소 관리자 › 용량 · 트래픽.
 * 용량: 이 홈페이지가 서버에 쓰는 공간을 종류별로 정확히 셉니다(10분 동안 기억해 두고 다시 재지 않음).
 * 트래픽: 사진 · 디자인 파일은 웹서버가 바로 보내서 홈페이지가 셀 수 없으므로, 첫 화면 무게와 방문 수로 추정합니다.
 *         정확한 트래픽은 카페24 관리 화면에서 봅니다.
 */

const USAGE_CACHE_SECONDS = 600;
// 용량 종류: [이름, 색]
const USAGE_PARTS = array(
    'blog' => array('블로그 사진', '#1F7A4C'),
    'site' => array('홈페이지 사진', '#2B8C9A'),
    'uploads' => array('그 밖의 올린 파일', '#7FA23A'),
    'backup' => array('원본 보관 사진', '#C9A227'),
    'db' => array('데이터베이스 (문의 · 정산 · 글)', '#B45309'),
    'program' => array('홈페이지 프로그램 · 디자인', '#5B6CB0'),
    'other' => array('기타 파일', '#9AA0A6'),
);
// 첫 화면 무게 종류: [이름, 색]
const TRAFFIC_PARTS = array(
    'html' => array('페이지 글 (HTML)', '#5B6CB0'),
    'css' => array('디자인 (CSS)', '#2B8C9A'),
    'js' => array('동작 (JS)', '#7FA23A'),
    'icon' => array('아이콘', '#9AA0A6'),
    'img' => array('사진', '#1F7A4C'),
);

/** 폴더 안 파일 용량 · 개수(바로가기는 건너뜀, 읽을 수 없는 폴더는 넘어감). $skip: 빼고 셀 폴더들 */
function usage_dir($dir, $skip = array(), &$budget = null)
{
    $bytes = 0;
    $files = 0;
    if (!is_dir($dir)) {
        return array(0, 0);
    }
    $skip = array_filter(array_map('realpath', $skip));
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        foreach ($it as $f) {
            if ($budget !== null && --$budget < 0) {
                break;
            }
            if ($f->isLink() || !$f->isFile()) {
                continue;
            }
            $path = $f->getPathname();
            foreach ($skip as $s) {
                if (strpos($path, $s . DIRECTORY_SEPARATOR) === 0) {
                    continue 2;
                }
            }
            $bytes += $f->getSize();
            $files++;
        }
    } catch (Exception $e) {
        // 읽을 수 없는 곳은 건너뜀
    }
    return array($bytes, $files);
}

/** 종류별 용량을 셉니다. 반환: ['at', 'total', 'files', 'parts' => [키 => [용량, 개수]], 'partial'] */
function usage_scan()
{
    $budget = 300000; // 파일이 아주 많으면 여기까지만 셈
    $parts = array();
    $parts['blog'] = usage_dir(UPLOAD_DIR . '/blog', array(), $budget);
    $parts['site'] = usage_dir(UPLOAD_DIR . '/site', array(), $budget);
    $parts['uploads'] = usage_dir(UPLOAD_DIR, array(UPLOAD_DIR . '/blog', UPLOAD_DIR . '/site'), $budget);
    $parts['backup'] = usage_dir(STORAGE_DIR . '/photo-backup', array(), $budget);
    $db = array(0, 0);
    foreach ((array) glob(STORAGE_DIR . '/*.sqlite*') as $f) {
        if (is_file($f)) {
            $db[0] += filesize($f);
            $db[1]++;
        }
    }
    $parts['db'] = $db;
    $app = usage_dir(APP_DIR, array(), $budget);
    $pub = usage_dir(PUBLIC_DIR, array(UPLOAD_DIR), $budget);
    $parts['program'] = array($app[0] + $pub[0], $app[1] + $pub[1]);
    $store = usage_dir(STORAGE_DIR, array(STORAGE_DIR . '/photo-backup'), $budget);
    $root = usage_dir(ROOT_DIR, array(APP_DIR, PUBLIC_DIR, STORAGE_DIR), $budget);
    $parts['other'] = array(max(0, $store[0] - $db[0]) + $root[0], max(0, $store[1] - $db[1]) + $root[1]);
    return array(
        'at' => now(),
        'total' => array_sum(array_column($parts, 0)),
        'files' => array_sum(array_column($parts, 1)),
        'parts' => $parts,
        'partial' => $budget < 0,
    );
}

/** 10분 동안은 기억해 둔 값을 씀($fresh 면 새로 셈) */
function usage_cached($fresh = false)
{
    $c = json_decode((string) gc('usage_cache'), true);
    if (!$fresh && is_array($c) && isset($c['at']) && time() - strtotime($c['at']) < USAGE_CACHE_SECONDS) {
        return $c;
    }
    $c = usage_scan();
    save_settings(array('gc_usage_cache' => json_encode($c)));
    return $c;
}

/** 용량 글자(0이면 0KB) */
function usage_bytes($n)
{
    return $n > 0 ? fmt_bytes($n) : '0KB';
}

/** 압축해서 보내는 크기(웹서버가 gzip 으로 보냄) */
function usage_gz_size($text)
{
    return function_exists('gzencode') ? strlen(gzencode((string) $text, 6)) : strlen((string) $text);
}

/**
 * 첫 화면을 끝까지 한 번 볼 때 받는 양(추정). 반환: ['parts' => [키 => 바이트], 'total', 'images' => [[주소, 바이트]…]]
 * 글꼴은 무료 외부 서버(jsDelivr)에서 받아 우리 트래픽이 아니라 넣지 않습니다.
 */
function traffic_home_weight()
{
    $html = '';
    try {
        $html = view('cleaning/home', array('sent' => false, 'errors' => array(), 'photos' => cleaning_photos(),
            'form' => array('kind' => 'office', 'name' => '', 'phone' => '', 'address' => '')));
    } catch (Throwable $e) {
        $html = '';
    }
    $file = function ($rel) {
        $f = PUBLIC_DIR . $rel;
        return is_file($f) ? $f : '';
    };
    $css = $file('/assets/green.css');
    $js = $file('/assets/green.js');
    $icon = $file('/icons/green-icon-32.png');
    $images = array();
    preg_match_all('~(?:src|href)="(/uploads/[A-Za-z0-9/_.-]+\\.(?:jpe?g|png|webp|gif))"~i', $html, $m);
    foreach (array_unique($m[1]) as $u) {
        if (strpos($u, '..') === false && ($f = $file($u)) !== '') {
            $images[] = array($u, filesize($f));
        }
    }
    $parts = array(
        'html' => usage_gz_size($html),
        'css' => $css !== '' ? usage_gz_size(file_get_contents($css)) : 0,
        'js' => $js !== '' ? usage_gz_size(file_get_contents($js)) : 0,
        'icon' => $icon !== '' ? filesize($icon) : 0,
        'img' => array_sum(array_column($images, 1)),
    );
    usort($images, function ($a, $b) {
        return $b[1] - $a[1];
    });
    return array('parts' => $parts, 'total' => array_sum($parts), 'images' => $images);
}

/** 사이트에서 가장 무거운 공개 파일(사진 · 디자인) 10개. 반환: [[주소, 바이트, 종류 이름]…] */
function usage_heaviest($limit = 10)
{
    $out = array();
    $dirs = array('blog' => UPLOAD_DIR . '/blog', 'site' => UPLOAD_DIR . '/site', 'uploads' => UPLOAD_DIR, 'program' => PUBLIC_DIR . '/assets');
    $seen = array();
    foreach ($dirs as $key => $dir) {
        foreach ((array) glob($dir . '/*') as $f) {
            if (!is_file($f) || isset($seen[$f])) {
                continue;
            }
            $seen[$f] = true;
            $out[] = array(substr($f, strlen(PUBLIC_DIR)), filesize($f), USAGE_PARTS[$key][0]);
        }
    }
    usort($out, function ($a, $b) {
        return $b[1] - $a[1];
    });
    return array_slice($out, 0, $limit);
}

/* ───────── 서버 사용 통계: 요청마다 어느 곳 · 누가 · 얼마나 보냈는지 하루 단위로 ───────── */

// 곳: [이름, 색]
const STAT_SECTIONS = array(
    'home' => array('첫 화면', '#1F7A4C'),
    'blog' => array('블로그', '#2B8C9A'),
    'landing' => array('검색어 페이지', '#7FA23A'),
    'feeds' => array('사이트맵 · RSS · 로봇 안내', '#C9A227'),
    'inquiry' => array('견적 문의 보내기', '#B45309'),
    'admin' => array('관리자 화면', '#5B6CB0'),
    'other' => array('기타 (개인정보 · 없는 주소 · 점검)', '#9AA0A6'),
);
const STAT_HANDLERS = array(
    'page_cleaning_home' => 'home', 'page_blog_list' => 'blog', 'page_blog_post' => 'blog', 'page_landing' => 'landing',
    'cleaning_sitemap' => 'feeds', 'blog_rss' => 'feeds', 'cleaning_robots' => 'feeds', 'indexnow_key_file' => 'feeds',
    'action_cleaning_inquiry' => 'inquiry',
);

/** 요청 시작: 보내는 내용을 모아 두었다가 끝날 때 크기를 셉니다(그린청소만) */
function server_stat_begin()
{
    if (SITE_MODE !== 'cleaning' || PHP_SAPI === 'cli') {
        return;
    }
    $GLOBALS['stat_first_visit'] = empty($_COOKIE[VISIT_COOKIE]);
    ob_start();
    register_shutdown_function('server_stat_end');
}

/** 어느 곳인지: 처리한 함수 이름(없으면 주소)으로 */
function server_stat_section($handler, $path)
{
    if ($handler !== null && isset(STAT_HANDLERS[$handler])) {
        return $handler === 'page_landing' && http_response_code() !== 200 ? 'other' : STAT_HANDLERS[$handler];
    }
    if ($handler !== null && strpos($handler, 'admin_') === 0) {
        return 'admin';
    }
    return strpos($path, '/admin') === 0 ? 'admin' : 'other';
}

/** 요청 끝: 그날 · 곳 · 사람/로봇 칸에 요청 1, 페이지 크기(압축 후), 사진 · 디자인(추정)을 더합니다 */
function server_stat_end()
{
    try {
        $out = ob_get_level() > 0 ? (string) ob_get_contents() : '';
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $section = server_stat_section($GLOBALS['stat_handler'] ?? null, rawurldecode($path));
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $bot = $ua === '' || preg_match(VISIT_BOT_RE, $ua) ? 1 : 0;
        $page = $out === '' ? 0 : (strlen($out) < 2097152 && function_exists('gzencode') ? strlen(gzencode($out, 1)) : strlen($out));
        $extra = 0;
        // 사람이 본 페이지: 그 안의 사진(처음 받는다고 보고), 처음 온 사람이면 디자인 · 동작 파일도
        if (!$bot && $section !== 'admin' && $out !== '' && stripos($out, '<html') !== false) {
            preg_match_all('~(?:src|href)="(/uploads/[A-Za-z0-9/_.-]+\.(?:jpe?g|png|webp|gif))"~i', $out, $m);
            foreach (array_unique($m[1]) as $u) {
                if (strpos($u, '..') === false && is_file(PUBLIC_DIR . $u)) {
                    $extra += filesize(PUBLIC_DIR . $u);
                }
            }
            if (!empty($GLOBALS['stat_first_visit'])) {
                $extra += server_stat_assets();
            }
        }
        $day = date('Y-m-d');
        $p = array((int) $page, (int) $extra, $day, $section, $bot);
        if (!q('UPDATE server_stats SET hits = hits + 1, page_bytes = page_bytes + ?, file_bytes = file_bytes + ? WHERE day = ? AND section = ? AND bot = ?', $p)->rowCount()) {
            try {
                q('INSERT INTO server_stats (day, section, bot, hits, page_bytes, file_bytes) VALUES (?, ?, ?, 1, ?, ?)', array($day, $section, $bot, (int) $page, (int) $extra));
                if (mt_rand(1, 50) === 1) {
                    q('DELETE FROM server_stats WHERE day < ?', array(date('Y-m-d', strtotime('-' . VISIT_KEEP_DAYS . ' days'))));
                }
            } catch (PDOException $e) {
                q('UPDATE server_stats SET hits = hits + 1, page_bytes = page_bytes + ?, file_bytes = file_bytes + ? WHERE day = ? AND section = ? AND bot = ?', $p);
            }
        }
    } catch (Throwable $e) {
        error_log('server_stat: ' . $e->getMessage());
    }
}

/** 처음 온 손님이 받는 디자인 · 동작 · 아이콘 파일(압축 후) */
function server_stat_assets()
{
    static $size = null;
    if ($size === null) {
        $size = 0;
        foreach (array('/assets/green.css', '/assets/green.js') as $f) {
            if (is_file(PUBLIC_DIR . $f)) {
                $size += usage_gz_size(file_get_contents(PUBLIC_DIR . $f));
            }
        }
        if (is_file(PUBLIC_DIR . '/icons/green-icon-32.png')) {
            $size += filesize(PUBLIC_DIR . '/icons/green-icon-32.png');
        }
    }
    return $size;
}

/** 기간 통계. 반환: ['days' => [날짜 => [곳 => 바이트]], 'sections' => [곳 => [사람 요청, 로봇 요청, 페이지, 사진·디자인]], 'total', 'hits'] */
function server_stat_report($from, $to = null)
{
    $to = $to ?? date('Y-m-d');
    $rows = q_all('SELECT day, section, bot, hits, page_bytes, file_bytes FROM server_stats WHERE day >= ? AND day <= ? ORDER BY day', array($from, $to));
    $sections = array_fill_keys(array_keys(STAT_SECTIONS), array(0, 0, 0, 0));
    $days = array();
    for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
        $days[$d] = array_fill_keys(array_keys(STAT_SECTIONS), 0);
    }
    foreach ($rows as $r) {
        $k = isset($sections[$r['section']]) ? $r['section'] : 'other';
        $sections[$k][(int) $r['bot'] ? 1 : 0] += (int) $r['hits'];
        $sections[$k][2] += (int) $r['page_bytes'];
        $sections[$k][3] += (int) $r['file_bytes'];
        if (isset($days[$r['day']])) {
            $days[$r['day']][$k] += (int) $r['page_bytes'] + (int) $r['file_bytes'];
        }
    }
    $total = 0;
    $hits = 0;
    foreach ($sections as $s) {
        $total += $s[2] + $s[3];
        $hits += $s[0] + $s[1];
    }
    return array('days' => $days, 'sections' => $sections, 'total' => $total, 'hits' => $hits);
}
