<?php
/**
 * 모든 요청의 진입점. (.htaccess 가 실제 파일이 아닌 주소를 여기로 보냅니다)
 * 서버에서는 이 폴더(public)의 내용이 웹 루트(www)에, app 폴더는 그 바로 위에 놓입니다.
 * 로컬 실행: php -S localhost:8000 -t public public/index.php
 */
if (PHP_SAPI === 'cli-server') {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $file = realpath(__DIR__ . rawurldecode($path));
    if ($file && is_file($file) && strpos($file, __DIR__) === 0 && basename($file) !== 'index.php' && basename($file)[0] !== '.') {
        return false;
    }
}

define('PUBLIC_DIR', __DIR__);
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_DIR . '/routes.php';

dispatch();
