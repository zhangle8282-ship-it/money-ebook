<?php
/**
 * 회원: 로그인, 회원가입, 로그아웃, 내 서재, 전자책 다운로드.
 */

function page_login()
{
    $next = safe_back(input('next'), '/');
    if (current_user()) {
        redirect($next);
    }
    $error = '';
    $email = strtolower(input('email'));
    if (is_post()) {
        $key = throttle_key('user', $email);
        $wait = throttle_locked($key);
        if (!csrf_valid()) {
            $error = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        } elseif ($wait) {
            $error = '로그인 시도가 너무 많아요. ' . ceil($wait / 60) . '분 뒤에 다시 시도해 주세요.';
        } else {
            $row = q_one('SELECT * FROM users WHERE email = ?', array($email));
            if ($row && password_verify(input_raw('password'), $row['password_hash'])) {
                throttle_clear($key);
                login_user($row['id']);
                redirect($next);
            }
            throttle_fail($key);
            usleep(300000);
            $error = '이메일 또는 비밀번호가 맞지 않아요.';
        }
    }
    render('login', array('title' => '로그인', 'error' => $error, 'email' => $email, 'next' => $next));
}

function page_signup()
{
    $next = safe_back(input('next'), '/');
    if (current_user()) {
        redirect($next);
    }
    $errors = array();
    $form = array('name' => input('name'), 'email' => strtolower(input('email')));
    if (is_post()) {
        $password = input_raw('password');
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        }
        if ($form['name'] === '' || str_len($form['name']) > 30) {
            $errors[] = '이름을 30자 이내로 입력해 주세요.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 190) {
            $errors[] = '이메일 주소를 확인해 주세요.';
        } elseif (q_value('SELECT id FROM users WHERE email = ?', array($form['email']))) {
            $errors[] = '이미 가입된 이메일이에요. 로그인해 주세요.';
        }
        if (strlen($password) < 8) {
            $errors[] = '비밀번호는 8자 이상으로 정해 주세요.';
        } elseif ($password !== input_raw('password2')) {
            $errors[] = '비밀번호 확인이 일치하지 않아요.';
        }
        if (input('agree') !== '1') {
            $errors[] = '이용약관과 개인정보처리방침에 동의해 주세요.';
        }
        if (!$errors) {
            $id = q_insert('users', array(
                'email' => $form['email'],
                'name' => $form['name'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => now(),
            ));
            login_user($id);
            flash('가입을 환영해요, ' . $form['name'] . '님.');
            redirect($next);
        }
    }
    render('signup', array('title' => '회원가입', 'errors' => $errors, 'form' => $form, 'next' => $next));
}

function action_logout()
{
    require_csrf('/');
    logout_user();
    redirect('/');
}

function page_library()
{
    $user = require_user();
    $owned = array();
    foreach (owned_book_ids($user['id']) as $id) {
        $b = find_book($id);
        if ($b) {
            $owned[] = $b;
        }
    }
    // 무료로 받은 책은 내 서재에만 보이고 주문 내역에는 넣지 않습니다.
    $orders = array_values(array_filter(user_orders($user['id']), function ($o) {
        return !is_free_order($o);
    }));
    foreach ($orders as &$o) {
        $o['items'] = order_items($o['id']);
    }
    unset($o);
    render('library', array(
        'title' => '내 서재', 'user' => $user, 'owned' => $owned, 'orders' => $orders,
        'percents' => reading_percents($user['id']),
    ));
}

function action_download($bookId)
{
    $book = find_book($bookId);
    $admin = current_admin();
    if (!$admin && !downloads_allowed()) {
        flash('이 스토어의 전자책은 사이트 뷰어로 읽어요.', 'info');
        redirect('/read/' . (int) $bookId);
    }
    if (!$admin) {
        $user = require_user();
        if (!$book || !user_owns_book($user['id'], $book['id'])) {
            flash('입금이 확인된 책만 내려받을 수 있어요.', 'error');
            redirect('/library');
        }
    }
    if (!$book || book_file_path($book) === '' || !is_file(book_file_path($book))) {
        flash('파일을 찾을 수 없어요. 고객센터로 문의해 주세요.', 'error');
        redirect($admin ? '/admin/books' : '/library');
    }
    $ext = strtolower($book['file_format']);
    $name = trim(preg_replace('/[\\\\\/:*?"<>|]+/', ' ', $book['title'])) . '.' . $ext;
    $mime = $ext === 'pdf' ? 'application/pdf' : 'application/epub+zip';
    send_download(book_file_path($book), $name, $mime);
    exit;
}
