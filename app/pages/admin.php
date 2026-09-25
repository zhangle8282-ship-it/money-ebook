<?php
/**
 * 관리자: 로그인(처음이면 계정 만들기), 대시보드, 전자책 목록, 주문(입금 확인), 리뷰, 설정.
 * 전자책 등록·수정은 admin_books.php.
 */

function render_admin($view, $vars)
{
    $vars['admin'] = current_admin();
    render('admin/' . $view, $vars, 'admin/layout');
}

function admin_login()
{
    $next = safe_back(input('next'), '/admin');
    if (strncmp($next, '/admin', 6) !== 0) {
        $next = '/admin';
    }
    // 비밀번호를 잊었을 때: FTP로 storage 폴더에 reset-admin 이라는 빈 파일을 올리면
    // 관리자 계정을 지우고 계정 만들기 화면을 다시 보여 줍니다(FTP 권한이 있는 사람만 가능).
    $resetFile = STORAGE_DIR . '/reset-admin';
    if (is_file($resetFile) && @unlink($resetFile)) {
        q('DELETE FROM admins');
    }
    if (current_admin()) {
        redirect($next);
    }
    $setup = !q_value('SELECT COUNT(*) FROM admins');
    $error = '';
    $username = input('username');
    if (is_post()) {
        if (!csrf_valid()) {
            $error = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        } elseif ($setup) {
            // 첫 실행: 관리자 계정을 만듭니다.
            $password = input_raw('password');
            if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
                $error = '아이디는 영문·숫자 3~30자로 정해 주세요.';
            } elseif (strlen($password) < 10) {
                $error = '비밀번호는 10자 이상으로 정해 주세요.';
            } elseif ($password !== input_raw('password2')) {
                $error = '비밀번호 확인이 일치하지 않아요.';
            } else {
                $id = q_insert('admins', array(
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'created_at' => now(),
                ));
                login_admin(q_one('SELECT * FROM admins WHERE id = ?', array($id)));
                flash('관리자 계정을 만들었어요. 먼저 설정에서 입금 계좌를 등록해 주세요.');
                redirect('/admin/settings');
            }
        } else {
            $key = throttle_key('admin', $username);
            $wait = throttle_locked($key);
            $row = q_one('SELECT * FROM admins WHERE username = ?', array($username));
            if ($wait) {
                $error = '로그인 시도가 너무 많아요. ' . ceil($wait / 60) . '분 뒤에 다시 시도해 주세요.';
            } elseif ($row && password_verify(input_raw('password'), $row['password_hash'])) {
                throttle_clear($key);
                login_admin($row);
                redirect($next);
            } else {
                throttle_fail($key);
                usleep(300000);
                $error = '아이디 또는 비밀번호가 맞지 않아요.';
            }
        }
    }
    render('admin/login', array(
        'title' => $setup ? '관리자 계정 만들기' : '관리자 로그인',
        'setup' => $setup, 'error' => $error, 'username' => $username, 'next' => $next,
    ), null);
}

function admin_logout()
{
    require_csrf('/admin');
    logout_admin();
    redirect('/admin/login');
}

function admin_dashboard()
{
    require_admin();
    // 설치 도구가 남아 있으면 지웁니다(못 지우면 대시보드에 안내).
    $installer = PUBLIC_DIR . '/install.php';
    if (is_file($installer)) {
        @unlink($installer);
    }
    $monthStart = date('Y-m-01 00:00:00');
    render_admin('dashboard', array(
        'title' => '대시보드',
        'nav' => 'dashboard',
        'pendingCount' => (int) q_value("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
        'monthSales' => (int) q_value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'paid' AND paid_at >= ?", array($monthStart)),
        'bookCount' => (int) q_value("SELECT COUNT(*) FROM books WHERE status = 'on_sale'"),
        'reviewCount' => (int) q_value("SELECT COUNT(*) FROM reviews WHERE created_at >= ?", array(date('Y-m-d H:i:s', strtotime('-7 days')))),
        'pending' => admin_order_rows("o.status = 'pending'", array(), 8),
        'bankReady' => bank_ready(),
        'bizReady' => setting('biz_name') !== '' && setting('biz_number') !== '',
        'installerLeft' => is_file($installer),
        'marketCounts' => market_pending_counts(),
    ));
}

function admin_books()
{
    require_admin();
    $status = input('status');
    $where = array_key_exists($status, BOOK_STATUS) ? ' WHERE b.status = ?' : '';
    $books = q_all(str_replace('SELECT b.*,', 'SELECT b.*, (SELECT name FROM users WHERE id = b.seller_user_id) AS seller_name,', book_select_sql())
        . $where . ' ORDER BY b.id DESC', $where ? array($status) : array());
    render_admin('books', array('title' => '전자책 관리', 'nav' => 'books', 'books' => $books, 'status' => $status));
}

function admin_book_delete($id)
{
    require_admin();
    require_csrf('/admin/books');
    $book = q_one('SELECT * FROM books WHERE id = ?', array((int) $id));
    if (!$book) {
        not_found();
    }
    if (q_value('SELECT COUNT(*) FROM order_items WHERE book_id = ?', array($book['id']))) {
        flash('주문 기록이 있는 책은 삭제할 수 없어요. 대신 비공개로 바꿔 주세요.', 'error');
        redirect('/admin/books/' . $book['id'] . '/edit');
    }
    q('DELETE FROM review_votes WHERE review_id IN (SELECT id FROM reviews WHERE book_id = ?)', array($book['id']));
    q('DELETE FROM reviews WHERE book_id = ?', array($book['id']));
    q('DELETE FROM books WHERE id = ?', array($book['id']));
    if (book_file_path($book) !== '') {
        @unlink(book_file_path($book));
    }
    delete_public_file($book['cover_path']);
    delete_preview_images($book['id']);
    flash('‘' . $book['title'] . '’을(를) 삭제했어요.');
    redirect('/admin/books');
}

/** 관리자 화면에서 PDF 미리보기를 다시 만들 때 원본을 읽어 갑니다. */
function admin_book_file($id)
{
    require_admin();
    $book = q_one('SELECT * FROM books WHERE id = ?', array((int) $id));
    if (!$book || book_file_path($book) === '' || !is_file(book_file_path($book))) {
        not_found();
    }
    header('Content-Type: ' . ($book['file_format'] === 'PDF' ? 'application/pdf' : 'application/epub+zip'));
    header('Content-Length: ' . filesize(book_file_path($book)));
    header('Cache-Control: private, no-store');
    readfile(book_file_path($book));
    exit;
}

/* ───────── 주문 ───────── */

function admin_order_rows($where, $params, $limit = 200)
{
    $rows = q_all('SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o
        LEFT JOIN users u ON u.id = o.user_id WHERE ' . $where . ' ORDER BY o.id DESC LIMIT ' . (int) $limit, $params);
    foreach ($rows as &$r) {
        $r['items'] = order_items($r['id']);
    }
    unset($r);
    return $rows;
}

function admin_orders()
{
    require_admin();
    $status = input('status');
    $q = input('q');
    $where = array('1 = 1');
    $params = array();
    if (array_key_exists($status, ORDER_STATUS)) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $where[] = '(o.order_no LIKE ? OR o.depositor LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
        for ($i = 0; $i < 4; $i++) {
            $params[] = '%' . $q . '%';
        }
    }
    $counts = array();
    foreach (q_all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    render_admin('orders', array(
        'title' => '주문 내역',
        'nav' => 'orders',
        'orders' => admin_order_rows(implode(' AND ', $where), $params),
        'status' => $status,
        'q' => $q,
        'counts' => $counts,
    ));
}

function admin_order_status($id)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/orders');
    require_csrf($back);
    $order = q_one('SELECT * FROM orders WHERE id = ?', array((int) $id));
    $to = input('to');
    $allowed = array('pending' => array('paid', 'cancelled'), 'paid' => array('pending'), 'cancelled' => array('pending'));
    if (!$order || !in_array($to, $allowed[$order['status']] ?? array(), true)) {
        flash('바꿀 수 없는 상태예요.', 'error');
        redirect($back);
    }
    set_order_status($order['id'], $to);
    $messages = array(
        'paid' => '입금을 확인했어요. 주문자가 바로 내려받을 수 있어요.',
        'cancelled' => '주문을 취소했어요.',
        'pending' => '입금 대기로 되돌렸어요.',
    );
    flash($order['order_no'] . ' · ' . $messages[$to]);
    redirect($back);
}

/* ───────── 리뷰 ───────── */

function admin_reviews()
{
    require_admin();
    $status = input('status');
    $where = in_array($status, array('visible', 'hidden'), true) ? ' WHERE r.status = ?' : '';
    $reviews = q_all('SELECT r.*, u.name AS user_name, u.email AS user_email, b.title AS book_title
        FROM reviews r LEFT JOIN users u ON u.id = r.user_id LEFT JOIN books b ON b.id = r.book_id'
        . $where . ' ORDER BY r.id DESC LIMIT 300', $where ? array($status) : array());
    render_admin('reviews', array('title' => '리뷰 관리', 'nav' => 'reviews', 'reviews' => $reviews, 'status' => $status));
}

function admin_review_action($id)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/reviews');
    require_csrf($back);
    $review = q_one('SELECT * FROM reviews WHERE id = ?', array((int) $id));
    if (!$review) {
        not_found();
    }
    $action = input('action');
    if ($action === 'hide' || $action === 'show') {
        q('UPDATE reviews SET status = ? WHERE id = ?', array($action === 'hide' ? 'hidden' : 'visible', $review['id']));
        flash($action === 'hide' ? '리뷰를 숨겼어요. 스토어에 보이지 않아요.' : '리뷰를 다시 보이게 했어요.');
    } elseif ($action === 'delete') {
        q('DELETE FROM review_votes WHERE review_id = ?', array($review['id']));
        q('DELETE FROM reviews WHERE id = ?', array($review['id']));
        flash('리뷰를 삭제했어요.');
    }
    redirect($back);
}

/* ───────── 프로그램 업데이트 (새 버전 zip 올리기) ───────── */

function admin_update()
{
    require_admin();
    $back = '/admin/settings#update';
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('올린 파일이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요.', 'error');
        redirect($back);
    }
    require_csrf($back);
    if (!has_upload('package') || upload_error($_FILES['package']) !== '') {
        flash(has_upload('package') ? upload_error($_FILES['package']) : '설치 파일(zip)을 선택해 주세요.', 'error');
        redirect($back);
    }
    $before = app_version();
    $error = '';
    $version = pkg_install($_FILES['package']['tmp_name'], ROOT_DIR, PUBLIC_DIR, $error);
    if ($version === null) {
        flash($error, 'error');
    } else {
        flash('버전 ' . $before . ' → ' . $version . ' 로 업데이트했어요. 이전 프로그램은 서버의 app.bak 폴더에 보관돼요.');
    }
    redirect($back);
}

/* ───────── 설정 ───────── */

const SETTING_FIELDS = array(
    'store_name', 'hero_title', 'hero_text', 'categories',
    'bank_name', 'bank_account', 'bank_holder', 'deposit_days', 'allow_download', 'cafe24_url', 'cafe24_code', 'seller_enabled', 'seller_commission',
    'biz_name', 'biz_owner', 'biz_number', 'biz_mail_order', 'biz_address', 'biz_phone', 'biz_email',
    'terms_text', 'privacy_text',
);

function admin_settings()
{
    $admin = require_admin();
    $errors = array();
    if (is_post() && input('form') === 'password') {
        $row = q_one('SELECT * FROM admins WHERE id = ?', array($admin['id']));
        $new = input_raw('new_password');
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        } elseif (!password_verify(input_raw('current_password'), $row['password_hash'])) {
            $errors[] = '지금 비밀번호가 맞지 않아요.';
        } elseif (strlen($new) < 10) {
            $errors[] = '새 비밀번호는 10자 이상으로 정해 주세요.';
        } elseif ($new !== input_raw('new_password2')) {
            $errors[] = '새 비밀번호 확인이 일치하지 않아요.';
        } else {
            q('UPDATE admins SET password_hash = ? WHERE id = ?', array(password_hash($new, PASSWORD_DEFAULT), $admin['id']));
            login_admin(q_one('SELECT * FROM admins WHERE id = ?', array($admin['id'])));
            flash('비밀번호를 바꿨어요.');
            redirect('/admin/settings');
        }
    } elseif (is_post()) {
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        }
        $values = array();
        foreach (SETTING_FIELDS as $key) {
            $values[$key] = str_replace("\r\n", "\n", input($key));
        }
        $values['deposit_days'] = (string) max(1, min(14, (int) $values['deposit_days']));
        $values['allow_download'] = $values['allow_download'] === '1' ? '1' : '0';
        $values['seller_enabled'] = $values['seller_enabled'] === '1' ? '1' : '0';
        $values['seller_commission'] = (string) max(0, min(90, (int) $values['seller_commission']));
        $values['cafe24_code'] = str_cut(preg_replace('/\s+/', '', $values['cafe24_code']), 50, '');
        if ($values['cafe24_url'] !== '' && !preg_match('~^https?://[^\s]+$~i', $values['cafe24_url'])) {
            $errors[] = '카페24 링크는 https:// 로 시작하는 주소로 넣어 주세요.';
        }
        if ($values['store_name'] === '') {
            $errors[] = '스토어 이름을 입력해 주세요.';
        }
        if (!$errors) {
            save_settings($values);
            flash('설정을 저장했어요.');
            redirect('/admin/settings');
        }
    }
    render_admin('settings', array(
        'title' => '설정',
        'nav' => 'settings',
        'values' => is_post() && input('form') !== 'password' ? array_merge(settings(), $_POST) : settings(),
        'errors' => $errors,
    ));
}
