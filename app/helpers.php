<?php
/**
 * 공용 함수: 출력 이스케이프, 화면 렌더링, 요청·응답, 표시 형식.
 */

function app_version()
{
    return trim((string) @file_get_contents(APP_DIR . '/VERSION'));
}

function config($key)
{
    return $GLOBALS['config'][$key] ?? null;
}

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 요청 값에서 문자열만 꺼냅니다(배열 등은 빈 문자열). */
function input($key, $default = '')
{
    $v = $_POST[$key] ?? ($_GET[$key] ?? $default);
    return is_string($v) ? trim($v) : $default;
}

/** 비밀번호처럼 앞뒤 공백도 그대로 받아야 하는 값. */
function input_raw($key)
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? $v : '';
}

function input_int($key, $default = 0)
{
    $v = preg_replace('/[^0-9]/', '', input($key));
    return $v === '' ? $default : (int) $v;
}

function is_post()
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect($path)
{
    header('Location: ' . $path, true, 303);
    exit;
}

/** 돌아갈 주소: 같은 사이트 안의 경로만 허용. */
function safe_back($path, $fallback = '/')
{
    return is_string($path) && preg_match('~^/(?![/\\\\])[^\s]*$~', $path) ? $path : $fallback;
}

function is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function current_path()
{
    return (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
}

/** 현재 주소의 쿼리 일부를 바꾼 주소. 값이 null 이면 그 키를 뺍니다. */
function url_with($changes, $path = null)
{
    $q = array_merge($_GET, $changes);
    foreach ($q as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        }
    }
    return ($path ?? current_path()) . ($q ? '?' . http_build_query($q) : '');
}

/* ───────── 화면 ───────── */

function view($name, $vars = array())
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_DIR . '/views/' . $name . '.php';
    return ob_get_clean();
}

/** 화면을 레이아웃(공개: layout, 관리자: admin/layout)으로 감싸 출력합니다. */
function render($name, $vars = array(), $layout = 'layout')
{
    $content = view($name, $vars);
    if ($layout === null) {
        echo $content;
        return;
    }
    $vars['content'] = $content;
    echo view($layout, $vars);
}

function not_found()
{
    http_response_code(404);
    render('not_found', array('title' => '페이지를 찾을 수 없어요'));
    exit;
}

function flash($message = null, $type = 'ok')
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'] = array('message' => $message, 'type' => $type);
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function json_out($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ───────── 표시 형식 ───────── */

function won($n)
{
    return number_format((int) $n) . '원';
}

function now()
{
    return date('Y-m-d H:i:s');
}

function fmt_date($s, $format = 'Y.m.d')
{
    $t = $s ? strtotime($s) : false;
    return $t ? date($format, $t) : '';
}

function fmt_month($s)
{
    $t = $s ? strtotime($s) : false;
    return $t ? date('Y', $t) . '년 ' . date('n', $t) . '월' : '';
}

function fmt_bytes($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . 'MB';
    }
    return max(1, round($bytes / 1024)) . 'KB';
}

function str_len($s)
{
    return function_exists('mb_strlen') ? mb_strlen((string) $s, 'UTF-8') : preg_match_all('/./us', (string) $s);
}

function str_cut($s, $max, $suffix = '…')
{
    if (str_len($s) <= $max) {
        return $s;
    }
    $cut = function_exists('mb_substr') ? mb_substr($s, 0, $max, 'UTF-8')
        : implode('', array_slice(preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY), 0, $max));
    return rtrim($cut) . $suffix;
}

/** 리뷰 작성자 이름 가리기: 김민지 → 김** */
function mask_name($name)
{
    $name = trim((string) $name);
    return $name === '' ? '익명' : str_cut($name, 1, '') . '**';
}

/** 별점(0~5, 소수 가능)을 채워진 별로 그립니다. */
function stars_html($rating, $class = 'stars')
{
    $pct = max(0, min(100, $rating / 5 * 100));
    return '<span class="' . e($class) . '" role="img" aria-label="5점 만점에 ' . e(number_format($rating, 1)) . '점">'
        . '<span class="stars-base" aria-hidden="true">★★★★★</span>'
        . '<span class="stars-fill" aria-hidden="true" style="width:' . round($pct, 1) . '%">★★★★★</span></span>';
}

/** 일반 텍스트 → 문단 HTML(빈 줄로 문단 구분). */
function paragraphs($text)
{
    $text = trim(str_replace("\r\n", "\n", (string) $text));
    if ($text === '') {
        return '';
    }
    $out = '';
    foreach (preg_split("/\n\s*\n/", $text) as $para) {
        $out .= '<p>' . nl2br(e(trim($para)), false) . "</p>\n";
    }
    return $out;
}

/** ★★★★☆ 처럼 정수 별점을 글자로. */
function stars_text($n)
{
    $n = max(0, min(5, (int) $n));
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

/** 표지: 올린 이미지가 있으면 이미지, 없으면 디자인의 글자 표지($size: card|hero|thumb). */
function cover_html($book, $size = 'card')
{
    if (!empty($book['cover_path'])) {
        return '<div class="cover cover-' . $size . ' has-image"><img src="' . e($book['cover_path']) . '" alt="' . e($book['title']) . ' 표지"'
            . ($size === 'hero' ? '' : ' loading="lazy"') . '></div>';
    }
    list($bg, $fg) = cover_colors($book);
    $out = '<div class="cover cover-' . $size . '" style="background:' . $bg . ';color:' . $fg . '" role="img" aria-label="' . e($book['title']) . ' 표지">';
    if ($size === 'thumb') {
        return $out . '<span class="cover-title">' . e(str_cut($book['title'], 12)) . '</span></div>';
    }
    return $out . '<span class="cover-cat">' . e($book['category']) . '</span>'
        . '<span class="cover-title">' . e($book['title']) . '</span>'
        . '<span class="cover-author">' . e($book['author']) . '</span></div>';
}

/** 받침에 맞는 조사: josa('전자책 마켓', '과', '와') → '전자책 마켓과' */
function josa($word, $withFinal, $withoutFinal)
{
    $word = (string) $word;
    $last = function_exists('mb_substr') ? mb_substr($word, -1, 1, 'UTF-8') : '';
    $code = $last !== '' && function_exists('mb_ord') ? mb_ord($last, 'UTF-8') : 0;
    $hasFinal = $code >= 0xAC00 && $code <= 0xD7A3 ? ($code - 0xAC00) % 28 > 0 : false;
    return $word . ($hasFinal ? $withFinal : $withoutFinal);
}
