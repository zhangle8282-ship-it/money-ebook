<?php
/**
 * 주소 → 처리 함수. 공개 화면은 pages/store·account·order·review, 관리자는 pages/admin*.
 */
require APP_DIR . '/pages/store.php';
require APP_DIR . '/pages/account.php';
require APP_DIR . '/pages/order.php';
require APP_DIR . '/pages/review.php';
require APP_DIR . '/pages/admin.php';
require APP_DIR . '/pages/admin_books.php';

function routes()
{
    return array(
        // 서버 점검(DB·폴더 권한). 값은 참/거짓만 보여 줍니다.
        array('GET', '~^/health$~', 'page_health'),
        // 스토어
        array('GET', '~^/$~', 'page_home'),
        array('GET', '~^/books/(\d+)$~', 'page_book'),
        array('GET', '~^/(terms|privacy)$~', 'page_static'),
        // 리뷰
        array('POST', '~^/books/(\d+)/reviews$~', 'action_review_create'),
        array('POST', '~^/reviews/(\d+)/helpful$~', 'action_review_helpful'),
        array('POST', '~^/reviews/(\d+)/delete$~', 'action_review_delete'),
        // 장바구니 · 주문
        array('GET', '~^/cart$~', 'page_cart'),
        array('POST', '~^/cart/add$~', 'action_cart_add'),
        array('POST', '~^/cart/remove$~', 'action_cart_remove'),
        array('GET|POST', '~^/checkout$~', 'page_checkout'),
        array('GET', '~^/orders/([0-9A-Z-]+)$~', 'page_order'),
        array('POST', '~^/orders/([0-9A-Z-]+)/cancel$~', 'action_order_cancel'),
        // 회원
        array('GET|POST', '~^/login$~', 'page_login'),
        array('GET|POST', '~^/signup$~', 'page_signup'),
        array('POST', '~^/logout$~', 'action_logout'),
        array('GET', '~^/library$~', 'page_library'),
        array('GET', '~^/download/(\d+)$~', 'action_download'),
        // 관리자
        array('GET|POST', '~^/admin/login$~', 'admin_login'),
        array('POST', '~^/admin/logout$~', 'admin_logout'),
        array('GET', '~^/admin$~', 'admin_dashboard'),
        array('GET', '~^/admin/books$~', 'admin_books'),
        array('GET|POST', '~^/admin/books/new$~', 'admin_book_form'),
        array('GET|POST', '~^/admin/books/(\d+)/edit$~', 'admin_book_form'),
        array('POST', '~^/admin/books/(\d+)/delete$~', 'admin_book_delete'),
        array('GET', '~^/admin/books/(\d+)/file$~', 'admin_book_file'),
        array('GET', '~^/admin/orders$~', 'admin_orders'),
        array('POST', '~^/admin/orders/(\d+)/status$~', 'admin_order_status'),
        array('GET', '~^/admin/reviews$~', 'admin_reviews'),
        array('POST', '~^/admin/reviews/(\d+)$~', 'admin_review_action'),
        array('GET|POST', '~^/admin/settings$~', 'admin_settings'),
    );
}

function page_health()
{
    $checks = array(
        'php' => PHP_VERSION_ID >= 70300,
        'pdo_sqlite' => extension_loaded('pdo_sqlite') || db_driver() !== 'sqlite',
        'zip' => class_exists('ZipArchive'),
        'dom' => class_exists('DOMDocument'),
        'storage' => is_dir(STORAGE_DIR . '/books') && is_writable(STORAGE_DIR . '/books'),
        'uploads' => is_dir(UPLOAD_DIR) && is_writable(UPLOAD_DIR),
    );
    try {
        db();
        $checks['db'] = true;
    } catch (Throwable $e) {
        $checks['db'] = false;
    }
    json_out(array('ok' => !in_array(false, $checks, true), 'checks' => $checks), in_array(false, $checks, true) ? 500 : 200);
}

function dispatch()
{
    $path = rawurldecode(current_path());
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $allowed = false;
    foreach (routes() as $route) {
        list($methods, $pattern, $handler) = $route;
        if (!preg_match($pattern, $path, $m)) {
            continue;
        }
        if (!in_array($method, explode('|', $methods), true)) {
            $allowed = true;
            continue;
        }
        array_shift($m);
        call_user_func_array($handler, $m);
        return;
    }
    if ($allowed) {
        http_response_code(405);
        echo '허용되지 않은 요청이에요.';
        return;
    }
    not_found();
}
