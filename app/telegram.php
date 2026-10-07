<?php
/**
 * 텔레그램 알림: 새 견적 문의가 들어오면 관리자가 만든 텔레그램 봇이 정해 둔 대화방(개인 · 단체)으로 알려 줍니다.
 *
 * 연결 순서(관리자 › 홈페이지 관리 › 텔레그램 알림)
 *   1. 텔레그램 @BotFather 에게 /newbot → 봇 토큰을 받음(비밀번호처럼 다룸)
 *   2. 만든 봇에게 아무 말이나 한 번 보내거나, 단체방에 봇을 초대
 *   3. 토큰을 넣고 ‘연결 확인’ → 봇이 받은 메시지에서 대화방을 찾아 저장하고 시험 메시지를 보냄
 * 토큰은 화면에 다시 보여 주지 않고(앞뒤 몇 글자만), 오류 안내에도 넣지 않습니다.
 */

const TELEGRAM_API = 'https://api.telegram.org';

function telegram_api_base()
{
    // 시험할 때만 config.local.php 의 telegram_api 로 가짜 서버를 쓸 수 있습니다.
    return rtrim((string) (config('telegram_api') ?: TELEGRAM_API), '/');
}

function telegram_token_ok($token)
{
    return (bool) preg_match('/^\d{5,15}:[A-Za-z0-9_-]{30,60}$/', (string) $token);
}

/** 화면에 보여 줄 토큰(앞 6자 … 뒤 4자) */
function telegram_token_masked($token)
{
    return $token === '' ? '' : substr($token, 0, 6) . '…' . substr($token, -4);
}

/**
 * 텔레그램 API 부르기. 반환: ['ok' => bool, 'result' => …, 'code' => HTTP 코드(0 = 연결 안 됨), 'description' => 텔레그램 설명]
 */
function telegram_call($token, $method, $params = array())
{
    $url = telegram_api_base() . '/bot' . $token . '/' . $method;
    $body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $raw = '';
    $code = 0;
    $net = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
        ));
        $raw = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $net = $code === 0 ? telegram_net_reason(curl_errno($ch)) : '';
    } else {
        $ctx = stream_context_create(array('http' => array(
            'method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $body, 'timeout' => 8, 'ignore_errors' => true,
        )));
        $raw = (string) @file_get_contents($url, false, $ctx);
        if ($raw !== '') {
            $head = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : null;
            $code = $head && preg_match('~^HTTP/\S+\s+(\d{3})~', $head[0], $m) ? (int) $m[1] : 200;
        } elseif (!ini_get('allow_url_fopen')) {
            $net = '서버에 curl도 없고 바깥 주소 열기(allow_url_fopen)도 꺼져 있어요';
        }
    }
    $data = json_decode($raw, true);
    return array(
        'ok' => is_array($data) && !empty($data['ok']),
        'result' => is_array($data) ? ($data['result'] ?? null) : null,
        'code' => $code,
        'description' => is_array($data) ? (string) ($data['description'] ?? '') : '',
        'net' => $net,
    );
}

/** curl 오류 번호 → 이유(주소 · 토큰은 넣지 않음) */
function telegram_net_reason($errno)
{
    $reasons = array(
        6 => '텔레그램 주소(api.telegram.org)를 찾지 못했어요(DNS)',
        7 => '텔레그램 서버로 연결이 막혔어요(방화벽 · 바깥 연결 차단)',
        28 => '시간 안에 텔레그램이 답하지 않았어요(연결 지연 · 차단)',
        35 => '보안 연결(SSL)을 맺지 못했어요',
        51 => '보안 인증서를 확인하지 못했어요(SSL)',
        60 => '서버의 보안 인증서 목록이 오래돼 텔레그램 인증서를 확인하지 못했어요(SSL)',
        77 => '서버의 보안 인증서 목록을 읽지 못했어요(SSL)',
    );
    return $reasons[(int) $errno] ?? ('연결 오류 ' . (int) $errno);
}

/** 실패 결과 → 알아듣기 쉬운 안내(토큰은 넣지 않음) */
function telegram_error_text($r)
{
    if ($r['code'] === 0) {
        return '이 서버에서 텔레그램으로 연결하지 못했어요' . (!empty($r['net']) ? ' — ' . $r['net'] : '') . '. 잠시 뒤 다시 해 보고, 계속 안 되면 ‘서버 연결 점검’ 결과를 알려 주세요.';
    }
    if ($r['code'] === 401 || $r['code'] === 404) {
        return '봇 토큰이 맞지 않아요. @BotFather 가 준 토큰을 다시 붙여 넣어 주세요.';
    }
    if ($r['code'] === 403) {
        return '봇이 그 대화방에 메시지를 보낼 수 없어요. 봇을 차단했거나 단체방에서 내보냈는지 확인하고, 봇에게 다시 말을 건 뒤 ‘연결 확인’을 눌러 주세요.';
    }
    if ($r['code'] === 400 && stripos($r['description'], 'chat not found') !== false) {
        return '대화방을 찾지 못했어요. 봇에게 먼저 말을 건 뒤 ‘연결 확인’을 눌러 주세요.';
    }
    if ($r['code'] === 409) {
        return '이 봇은 다른 곳(웹훅)에 연결돼 있어요. 새 봇을 만들거나 웹훅을 지운 뒤 다시 해 주세요.';
    }
    return '텔레그램이 요청을 받지 않았어요(' . $r['code'] . ($r['description'] !== '' ? ' · ' . str_cut($r['description'], 80) : '') . ').';
}

/**
 * 봇이 받은 메시지에서 가장 최근 대화방 찾기(개인 대화 · 단체방). 반환: [대화방 번호, 이름] 또는 null
 */
function telegram_find_chat($token)
{
    $r = telegram_call($token, 'getUpdates', array('limit' => 100, 'allowed_updates' => array('message', 'my_chat_member', 'channel_post')));
    if (!$r['ok']) {
        return array(null, $r);
    }
    $found = null;
    foreach ((array) $r['result'] as $u) {
        foreach (array('message', 'my_chat_member', 'channel_post', 'edited_message') as $k) {
            if (isset($u[$k]['chat']['id'])) {
                $found = $u[$k]['chat'];
            }
        }
    }
    if (!$found) {
        return array(null, $r);
    }
    $title = $found['title'] ?? trim(($found['first_name'] ?? '') . ' ' . ($found['last_name'] ?? ''));
    if ($title === '' && isset($found['username'])) {
        $title = '@' . $found['username'];
    }
    return array(array((string) $found['id'], $title !== '' ? $title : '대화방'), $r);
}

function telegram_send($text, $token = null, $chat = null)
{
    $token = $token ?? gc('tg_token');
    $chat = $chat ?? gc('tg_chat');
    return telegram_call($token, 'sendMessage', array('chat_id' => $chat, 'text' => $text, 'disable_web_page_preview' => true));
}

function telegram_ready()
{
    return gc('tg_on') === '1' && telegram_token_ok(gc('tg_token')) && gc('tg_chat') !== '';
}

/** 마지막으로 보낸 결과를 남겨 관리자 화면에 보여 줌 */
function telegram_remember($r, $what)
{
    save_settings(array('gc_tg_last' => json_encode(array(
        'at' => now(), 'what' => $what, 'ok' => $r['ok'], 'error' => $r['ok'] ? '' : telegram_error_text($r),
    ), JSON_UNESCAPED_UNICODE)));
}

function telegram_last()
{
    $last = json_decode(gc('tg_last'), true);
    return is_array($last) ? $last : null;
}

/** 새 견적 문의 알림 글 */
function telegram_inquiry_text($v)
{
    return '🧹 새 견적 문의 · ' . gc('name') . "\n\n"
        . '종류: ' . (CLEANING_KINDS[$v['kind']] ?? $v['kind']) . " 정기청소\n"
        . '업체 / 담당: ' . $v['name'] . "\n"
        . '연락처: ' . $v['phone'] . "\n"
        . '주소 · 면적: ' . ($v['address'] !== '' ? $v['address'] : '적지 않음') . "\n"
        . '접수: ' . date('Y.m.d H:i') . "\n\n"
        . '관리자 화면: ' . site_base_url() . '/admin/inquiries';
}

/**
 * 새 견적 문의를 텔레그램으로 알림. 문의한 사람이 기다리지 않도록 화면을 다 보낸 뒤에 보냅니다.
 */
function telegram_notify_inquiry($v)
{
    if (!telegram_ready()) {
        return;
    }
    $text = telegram_inquiry_text($v);
    register_shutdown_function(function () use ($text) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        try {
            telegram_remember(telegram_send($text), '새 견적 문의');
        } catch (Throwable $e) {
            error_log('telegram: ' . $e->getMessage());
        }
    });
}

/** 서버에서 텔레그램까지 연결되는지 점검(토큰 없이 주소만 열어 봄). 반환: 한 줄씩 결과 */
function telegram_server_check()
{
    $lines = array('PHP ' . PHP_VERSION);
    $lines[] = function_exists('curl_init') ? 'curl 있음' . (function_exists('curl_version') ? ' (' . (curl_version()['ssl_version'] ?? '') . ')' : '') : 'curl 없음' . (ini_get('allow_url_fopen') ? ' · 바깥 주소 열기 가능' : ' · 바깥 주소 열기도 꺼짐');
    $host = parse_url(telegram_api_base(), PHP_URL_HOST);
    $ip = gethostbyname($host);
    $lines[] = filter_var($host, FILTER_VALIDATE_IP) || $ip !== $host ? '텔레그램 주소 찾음' : '텔레그램 주소를 찾지 못함(DNS)';
    $r = telegram_call('0:check', 'getMe');
    $lines[] = $r['code'] > 0 ? '텔레그램 서버와 연결됨' : '텔레그램 서버로 연결 안 됨' . ($r['net'] !== '' ? ' — ' . $r['net'] : '');
    if (telegram_token_ok(gc('tg_token'))) {
        $me = telegram_call(gc('tg_token'), 'getMe');
        $lines[] = $me['ok'] ? '저장된 봇 토큰 확인(@' . ($me['result']['username'] ?? '') . ')' : '저장된 봇 토큰: ' . telegram_error_text($me);
    }
    return $lines;
}
