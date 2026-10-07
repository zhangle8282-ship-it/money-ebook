<?php
/**
 * 그린청소 검색 등록: 사이트맵 · RSS를 검색 사이트가 읽어 가게 하고, 바뀐 주소는 IndexNow로 바로 알립니다.
 *
 * IndexNow: 주소가 새로 생기거나 바뀌거나 지워졌을 때 검색 사이트에 "이 주소 다시 읽어 가세요"라고 알리는 공용 방식.
 *   네이버 · 빙(Bing) · 얀덱스 등이 함께 쓰고, 참여한 검색 사이트끼리 받은 주소를 나눠 씁니다.
 *   구글은 받지 않으므로 구글 서치 콘솔에 사이트맵을 한 번 제출해 두면 알아서 다시 읽어 갑니다.
 *   주인 확인: 사이트 맨 위의 /{열쇠}.txt(내용 = 열쇠)를 검색 사이트가 열어 봅니다(이 앱이 그 주소를 만들어 줌).
 */

const INDEXNOW_ENDPOINTS = array(
    'naver' => array('네이버', 'https://searchadvisor.naver.com/indexnow'),
    'indexnow' => array('빙 · 기타', 'https://api.indexnow.org/indexnow'),
);
const INDEXNOW_LOG_MAX = 10;
const INDEXNOW_GAP = 600; // 같은 주소를 다시 알리기까지 기다리는 시간(초). ‘지금 모두 알리기’는 예외

/** 열쇠(32자리 16진수). 처음 쓸 때 만들어 저장합니다. */
function indexnow_key()
{
    $key = gc('indexnow_key');
    if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
        $key = bin2hex(random_bytes(16));
        save_settings(array('gc_indexnow_key' => $key));
    }
    return $key;
}

/** /{열쇠}.txt: 저장된 열쇠와 같을 때만 열쇠를 글자로 보여 줍니다. */
function indexnow_key_file($key)
{
    if ($key !== gc('indexnow_key')) {
        not_found();
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $key;
    exit;
}

/** 인터넷에서 열리는 주소인지(내 컴퓨터 · IP 주소 · 시험용 주소가 아닌지) */
function is_public_host($host)
{
    $host = strtolower(preg_replace('/:\d+$/', '', (string) $host));
    if ($host === '' || $host === 'localhost' || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) || strpos($host, '.') === false) {
        return false;
    }
    return !preg_match('/\.(localhost|test|local|invalid)$/', $host);
}

/** 지금 주소가 인터넷 주소인지(아니면 검색 사이트에 알리지 않음) */
function indexnow_public_host()
{
    return is_public_host(parse_url(base_url(), PHP_URL_HOST));
}

/**
 * 주소 하나로 모으기: http:// 와 www. 로 들어오면 https://(www 없는 주소)로 영구 이동(301)합니다.
 * 같은 홈페이지가 주소 4개로 따로 열리면 검색 점수가 나뉘기 때문입니다.
 * 내 컴퓨터 · IP 주소로 열 때와 보내는 요청(POST)은 그대로 둡니다. 관리자 › 검색 등록에서 끌 수 있습니다.
 */
function canonical_host_redirect()
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (($method !== 'GET' && $method !== 'HEAD') || gc('canonical_redirect') === '0') {
        return;
    }
    $host = strtolower(preg_replace('/[^A-Za-z0-9.:\-]/', '', $_SERVER['HTTP_HOST'] ?? ''));
    if (!is_public_host($host)) {
        return;
    }
    $host = preg_replace('/:\d+$/', '', $host);
    $target = preg_replace('/^www\./', '', $host);
    if ($target === $host && is_https()) {
        return;
    }
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: https://' . $target . ($uri !== '' && $uri[0] === '/' ? $uri : '/'), true, 301);
    exit;
}

/** 사이트맵에 담기는 주소 전부: [주소(‘/’로 시작), 마지막 수정 시각 또는 null] */
function sitemap_entries()
{
    $postsUpdated = q_value('SELECT MAX(updated_at) FROM blog_posts WHERE ' . blog_public_sql(), array(now()));
    $pages = landing_public();
    $pagesUpdated = $pages ? max(array_column($pages, 'updated_at')) : '';
    $home = max((string) gc('home_updated'), (string) $postsUpdated, (string) $pagesUpdated);
    $rows = array(array('/', $home !== '' ? $home : null));
    foreach ($pages as $p) {
        $rows[] = array(landing_url($p), $p['updated_at']);
    }
    $rows[] = array('/privacy', null);
    if ($postsUpdated) {
        $rows[] = array('/blog', $postsUpdated);
    }
    foreach (blog_latest(1000) as $p) {
        $rows[] = array(blog_url($p), $p['updated_at']);
    }
    return $rows;
}

/** 주소 묶음을 검색 사이트들에 함께 보냅니다. 반환: [검색 사이트 => 응답 코드(0 = 연결 안 됨)] */
function indexnow_send($urls, $endpoints)
{
    $base = base_url();
    $key = indexnow_key();
    $body = json_encode(array(
        'host' => parse_url($base, PHP_URL_HOST),
        'key' => $key,
        'keyLocation' => $base . '/' . $key . '.txt',
        'urlList' => array_values($urls),
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $headers = array('Content-Type: application/json; charset=utf-8', 'User-Agent: GreenCleaning-IndexNow/1.0');
    $codes = array();
    if (function_exists('curl_multi_init')) {
        $multi = curl_multi_init();
        $handles = array();
        foreach ($endpoints as $id => $ep) {
            $ch = curl_init($ep[1]);
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 6,
            ));
            curl_multi_add_handle($multi, $ch);
            $handles[$id] = $ch;
        }
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running && $status === CURLM_OK);
        foreach ($handles as $id => $ch) {
            $codes[$id] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($multi, $ch);
        }
        curl_multi_close($multi);
        return $codes;
    }
    foreach ($endpoints as $id => $ep) {
        $ctx = stream_context_create(array('http' => array(
            'method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => $body, 'timeout' => 6, 'ignore_errors' => true,
        )));
        $codes[$id] = 0;
        if (@file_get_contents($ep[1], false, $ctx) !== false && function_exists('http_get_last_response_headers')) {
            $head = http_get_last_response_headers();
            if ($head && preg_match('~^HTTP/\S+\s+(\d{3})~', $head[0], $m)) {
                $codes[$id] = (int) $m[1];
            }
        }
    }
    return $codes;
}

function indexnow_ok($code)
{
    return $code === 200 || $code === 202;
}

function indexnow_code_label($code)
{
    $labels = array(0 => '연결 안 됨', 200 => '접수', 202 => '접수(확인 중)', 400 => '형식 오류', 403 => '열쇠 확인 실패', 422 => '주소 오류', 429 => '너무 자주 보냄');
    return $labels[$code] ?? ('응답 ' . $code);
}

/** 최근 알림 기록(새것부터) */
function indexnow_log()
{
    $log = json_decode(gc('indexnow_log'), true);
    return is_array($log) ? $log : array();
}

/** 최근에 알린 주소 => 시각(초) */
function indexnow_sent()
{
    $sent = json_decode(gc('indexnow_sent'), true);
    return is_array($sent) ? $sent : array();
}

/**
 * 바뀐 주소를 검색 사이트에 알립니다.
 * $paths: ‘/’로 시작하는 주소. $force: 방금 알린 주소도 다시 보냄(‘지금 모두 알리기’).
 * 반환: 남긴 기록 한 줄(꺼져 있거나 보낼 주소가 없으면 null)
 */
function indexnow_ping($paths, $why, $force = false, $endpoints = null)
{
    if (SITE_MODE !== 'cleaning' || gc('indexnow_on') !== '1') {
        return null;
    }
    $base = base_url();
    $urls = array_keys(array_flip(array_map(function ($p) use ($base) {
        return $base . $p;
    }, $paths)));
    $entry = array('at' => now(), 'why' => $why);
    $save = array();
    if (!indexnow_public_host()) {
        $entry += array('count' => count($urls), 'urls' => array_slice($urls, 0, 5), 'skip' => 'local');
    } else {
        $sent = indexnow_sent();
        $time = time();
        if (!$force) {
            $urls = array_values(array_filter($urls, function ($u) use ($sent, $time) {
                return !isset($sent[$u]) || $time - (int) $sent[$u] >= INDEXNOW_GAP;
            }));
            if (!$urls) {
                return null;
            }
        }
        $urls = array_slice($urls, 0, 10000);
        $codes = indexnow_send($urls, $endpoints ?: INDEXNOW_ENDPOINTS);
        $entry += array('count' => count($urls), 'urls' => array_slice($urls, 0, 5), 'codes' => $codes);
        if (array_filter($codes, 'indexnow_ok')) {
            foreach ($urls as $u) {
                $sent[$u] = $time;
            }
            $save['gc_indexnow_sent'] = json_encode(array_filter($sent, function ($t) use ($time) {
                return $time - (int) $t < 86400;
            }), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
    $save['gc_indexnow_log'] = json_encode(array_slice(array_merge(array($entry), indexnow_log()), 0, INDEXNOW_LOG_MAX), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    save_settings($save);
    return $entry;
}

/**
 * 검색 등록 › 구글에 잘 나오게: 홈페이지 안에서 확인할 수 있는 것만 점검(바깥 연결 없음).
 * 반환: [[항목, 상태(ok | todo | warn | info), 설명]…]
 */
function google_seo_checks()
{
    $checks = array();
    $verified = verify_code(gc('google_verify')) !== '';
    $checks[] = array('구글 소유 확인', $verified ? 'ok' : 'todo', $verified
        ? '확인 코드가 홈페이지에 들어가 있어요. 서치 콘솔에서 ‘확인’까지 눌렀다면 끝이에요.'
        : '아직 코드가 없어요. 위 ‘구글 서치 콘솔’ 순서대로 코드를 넣어야 사이트맵을 내고 색인을 요청할 수 있어요.');
    $checks[] = array('주소 하나로 모으기(https)', gc('canonical_redirect') !== '0' ? 'ok' : 'warn', gc('canonical_redirect') !== '0'
        ? 'http · www 로 들어와도 한 주소로 모여서, 구글이 같은 페이지를 두 번 세지 않아요.'
        : '꺼져 있어요. 위 ‘주소 하나로 모으기’를 켜 두는 게 좋아요.');
    $checks[] = array('사이트맵', 'ok', '주소 ' . count(sitemap_entries()) . '개와 바뀐 날짜가 들어 있어요. 서치 콘솔 › Sitemaps 에 한 번만 내면 구글이 알아서 다시 읽어 가요.');
    $biz = gc('phone') !== '' && gc('address') !== '';
    $checks[] = array('업체 정보(구조화 데이터)', $biz ? 'ok' : 'warn', $biz
        ? '상호 · 전화 · 주소 · 서비스 지역 · 서비스 목록 · 자주 묻는 질문을 구글이 읽는 형식으로 넣었어요. 검색 결과 사이트 이름은 ‘' . gc('name') . '’으로 알려요.'
        : '홈페이지 관리에서 전화번호와 주소를 채워 주세요. 구글은 업체 정보에 주소가 있어야 제대로 읽어요.');
    $pages = count(landing_public());
    $checks[] = array('검색어 페이지', $pages >= 3 ? 'ok' : 'warn', '지역 · 업종 검색어마다 따로 된 페이지 ' . $pages . '개. 구글도 검색어 하나를 깊게 다룬 페이지를 위에 올려요.');
    $posts = q_all('SELECT * FROM blog_posts WHERE ' . blog_public_sql(), array(now()));
    $checks[] = array('블로그 글', count($posts) >= 5 ? 'ok' : 'warn', '공개 글 ' . count($posts) . '개. 구글은 새 글이 꾸준히 올라오는 사이트를 더 자주 읽어요' . (count($posts) >= 5 ? '.' : ' — 5개 이상, 주 1~2개씩 권해요.'));
    $noImg = count(array_filter($posts, function ($p) {
        return blog_image($p) === '';
    }));
    if ($posts) {
        $checks[] = array('글 대표 사진', $noImg ? 'warn' : 'ok', $noImg
            ? '사진 없는 글 ' . $noImg . '개. 글마다 사진을 1장 이상 넣으면 구글 검색 · 이미지 검색에 사진이 같이 나와요.'
            : '모든 글에 사진이 있어 구글 검색 결과에 사진이 같이 나올 수 있어요.');
    }
    $desc = gc('seo_desc');
    if (substr_count($desc, ',') >= 4 && !preg_match('/[다요]\.|[.!?]$/u', $desc)) {
        $checks[] = array('검색 설명 문구', 'info', '지금 설명은 검색어를 나열한 형태예요. 네이버용으로 정한 문구라 그대로 두어도 되고, 구글은 이럴 때 본문에서 문장을 골라 보여 주기도 해요.');
    }
    return $checks;
}

/** 구글 서치 콘솔 › URL 검사(색인 요청)로 바로 가는 주소. 속성은 ‘URL 접두어’(홈페이지 주소)로 등록했다고 봅니다. */
function google_inspect_url($url)
{
    return 'https://search.google.com/search-console/inspect?resource_id=' . rawurlencode(base_url() . '/') . '&id=' . rawurlencode($url);
}

/** 구글에 색인을 요청할 만한 주소: 첫 화면, 검색어 페이지, 최근 블로그 글 10개. 반환: [[이름, 전체 주소]…] */
function google_index_targets()
{
    $base = base_url();
    $out = array(array('첫 화면', $base . '/'));
    foreach (landing_public() as $p) {
        $out[] = array($p['title'], landing_url($p, true));
    }
    foreach (q_all('SELECT * FROM blog_posts WHERE ' . blog_public_sql() . ' ORDER BY published_at DESC, id DESC LIMIT 10', array(now())) as $p) {
        $out[] = array($p['title'], blog_url($p, true));
    }
    return $out;
}

/** 알림 결과를 한 문장으로(관리자 저장 메시지 뒤에 붙임) */
function indexnow_result_text($entry)
{
    if (!$entry) {
        return '';
    }
    if (isset($entry['skip'])) {
        return ' (지금은 내 컴퓨터 주소라서 검색 사이트에 알리지 않았어요)';
    }
    $ok = array();
    foreach ($entry['codes'] as $id => $code) {
        if (indexnow_ok((int) $code)) {
            $ok[] = INDEXNOW_ENDPOINTS[$id][0] ?? $id;
        }
    }
    return $ok ? ' ' . implode(' · ', $ok) . '에 바로 알렸어요.' : ' 검색 사이트 알림은 실패했어요. ‘검색 등록’ 메뉴에서 다시 보낼 수 있어요.';
}

/** 첫 화면 내용이 바뀌었을 때: 사이트맵의 수정 시각을 바꾸고 검색 사이트에 알립니다. */
function home_changed($why)
{
    save_settings(array('gc_home_updated' => now()));
    return indexnow_ping(array('/'), $why);
}

/**
 * 예약 글 중 가장 빠른 공개 시각을 적어 둡니다(블로그 글을 저장 · 삭제할 때마다).
 * 이미 공개 시각이 지났는데 아직 알리지 않은 예약 글이 있으면 그대로 두어 다음 방문 때 알리게 합니다.
 */
function indexnow_schedule($afterDue = false)
{
    $current = gc('indexnow_next');
    if (!$afterDue && $current !== '' && $current <= now()) {
        return;
    }
    $next = (string) q_value("SELECT MIN(published_at) FROM blog_posts WHERE status = 'published' AND published_at > ?", array(now()));
    if ($next !== gc('indexnow_next')) {
        save_settings(array('gc_indexnow_next' => $next));
    }
}

/**
 * 예약해 둔 블로그 글이 공개 시각을 지났으면 알립니다.
 * 공개 화면이 열릴 때 확인하고, 화면을 다 보낸 뒤에 알려서 방문자는 기다리지 않습니다.
 */
function indexnow_due()
{
    $next = gc('indexnow_next');
    if ($next === '' || $next > now() || gc('indexnow_on') !== '1') {
        return;
    }
    $posts = q_all('SELECT * FROM blog_posts WHERE ' . blog_public_sql() . ' AND published_at >= ?', array(now(), $next));
    indexnow_schedule(true); // 먼저 다음 시각으로 바꿔 두어 동시에 들어온 요청이 두 번 알리지 않게
    if (!$posts) {
        return;
    }
    indexnow_later(array_merge(array_map('blog_url', $posts), array('/blog', '/')), '예약 글 공개');
}

/** 화면을 방문자에게 다 보낸 뒤에 검색 사이트에 알립니다(방문자는 기다리지 않음). */
function indexnow_later($paths, $why)
{
    register_shutdown_function(function () use ($paths, $why) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        try {
            indexnow_ping($paths, $why);
        } catch (Throwable $e) {
            error_log('indexnow: ' . $e->getMessage());
        }
    });
}

/** 다음 웹마스터도구 robots.txt 인증 줄: 붙여 넣은 글에서 #DaumWebMasterTool:… 만 남깁니다. */
function daum_verify_line($raw)
{
    return preg_match('/DaumWebMasterTool:[A-Za-z0-9]+:[A-Za-z0-9+\/=]+/', (string) $raw, $m) ? '#' . $m[0] : '';
}

/** 화면에 보일 주소(한글 도메인은 한글로) */
function display_url($url)
{
    $host = (string) parse_url($url, PHP_URL_HOST);
    if (strpos($host, 'xn--') !== false && function_exists('idn_to_utf8')) {
        $utf = idn_to_utf8($host, 0, INTL_IDNA_VARIANT_UTS46);
        if ($utf) {
            return preg_replace('~^(https?://)' . preg_quote($host, '~') . '~i', '$1' . $utf, $url);
        }
    }
    return $url;
}
