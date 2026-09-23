<?php
/**
 * 앱 시작점: 경로 상수, 설정, DB, 세션, 공용 함수를 불러옵니다.
 * PHP 7.4 이상에서 동작하도록 PHP 8 전용 문법은 쓰지 않습니다.
 */
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('STORAGE_DIR', ROOT_DIR . '/storage');
// 웹 루트: 로컬은 public/, 서버는 www/ (index.php 가 알려 줍니다)
if (!defined('PUBLIC_DIR')) {
    define('PUBLIC_DIR', ROOT_DIR . '/public');
}
define('UPLOAD_DIR', PUBLIC_DIR . '/uploads');

date_default_timezone_set('Asia/Seoul');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

$config = require APP_DIR . '/config.php';
if (is_file(APP_DIR . '/config.local.php')) {
    $config = array_replace_recursive($config, require APP_DIR . '/config.local.php');
}
$GLOBALS['config'] = $config;

ini_set('display_errors', $config['debug'] ? '1' : '0');
error_reporting(E_ALL);

// 예상하지 못한 오류: 방문자에게는 안내 화면만 보이고, 자세한 내용은 storage/error.log 에 남깁니다.
set_exception_handler(function ($e) {
    @file_put_contents(STORAGE_DIR . '/error.log', '[' . date('Y-m-d H:i:s') . '] ' . $e . "\n\n", FILE_APPEND);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>잠시 문제가 생겼어요</title><body style="font-family:sans-serif;background:#F6F4EF;color:#1D1C1A;padding:64px 20px;text-align:center">'
        . '<h1 style="font-size:22px">잠시 문제가 생겼어요</h1><p style="color:#5F5B53">잠시 뒤에 다시 시도해 주세요.</p></body>';
});

require APP_DIR . '/helpers.php';
require APP_DIR . '/db.php';
require APP_DIR . '/auth.php';
require APP_DIR . '/store.php';
require APP_DIR . '/sanitize.php';
require APP_DIR . '/files.php';
require APP_DIR . '/epub.php';
// 설치 도구(install.php)에는 같은 코드가 들어 있어 이미 불러왔을 수 있습니다.
if (!function_exists('pkg_install')) {
    require APP_DIR . '/package.php';
}

ensure_storage();
