<?php
/**
 * 세션, CSRF, 회원 로그인, 관리자 로그인, 로그인 시도 제한.
 */

define('SESSION_NAME', 'ebook_sid');

function start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // 공용 호스팅의 기본 세션 폴더는 다른 사이트 설정으로 일찍 비워질 수 있어 전용 폴더를 씁니다.
    $dir = STORAGE_DIR . '/sessions';
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
        ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 7));
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
    }
    ini_set('session.use_strict_mode', '1');
    session_name(SESSION_NAME);
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        // PHP 7.3 미만은 SameSite 를 경로 뒤에 붙여 지정합니다.
        session_set_cookie_params(0, '/; samesite=Lax', '', is_https(), true);
    }
    session_start();
}

/** 세션 쿠키가 있는 방문자만 세션을 엽니다(처음 온 방문자에게 쿠키를 만들지 않음). */
function has_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    if (empty($_COOKIE[SESSION_NAME])) {
        return false;
    }
    start_session();
    return true;
}

/* ───────── CSRF ───────── */

function csrf_token()
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid()
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && $sent !== '' && has_session() && hash_equals(csrf_token(), $sent);
}

/** POST 요청의 CSRF 토큰을 검사하고, 틀리면 안내 후 이전 화면으로 돌려보냅니다. */
function require_csrf($back = '/')
{
    if (!csrf_valid()) {
        flash('보안 확인이 만료되었어요. 다시 시도해 주세요.', 'error');
        redirect($back);
    }
}

/* ───────── 회원 ───────── */

function current_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (has_session() && !empty($_SESSION['user_id'])) {
        $user = q_one('SELECT id, email, name, created_at FROM users WHERE id = ?', array((int) $_SESSION['user_id']));
        if (!$user) {
            unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function login_user($userId)
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $userId;
}

function logout_user()
{
    start_session();
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

/** 로그인이 필요하면 로그인 화면으로 보냅니다(다 끝나면 원래 화면으로 돌아옴). */
function require_user($next = null)
{
    $user = current_user();
    if (!$user) {
        // POST 주소로 돌아가면 열 수 없으므로, 폼 요청은 호출한 쪽이 돌아갈 화면을 넘깁니다.
        flash('로그인이 필요해요.', 'info');
        redirect('/login?next=' . rawurlencode($next ?? ($_SERVER['REQUEST_URI'] ?? '/')));
    }
    return $user;
}

/* ───────── 관리자 ───────── */

function current_admin()
{
    static $admin = false;
    if ($admin !== false) {
        return $admin;
    }
    $admin = null;
    if (has_session() && !empty($_SESSION['admin'])) {
        $row = q_one('SELECT id, username, password_hash FROM admins WHERE id = ?', array((int) $_SESSION['admin']['id']));
        // 비밀번호가 바뀌면 기존 관리자 세션은 모두 풀립니다.
        if ($row && hash_equals(admin_fingerprint($row), (string) $_SESSION['admin']['fp'])) {
            $admin = array('id' => (int) $row['id'], 'username' => $row['username']);
        } else {
            unset($_SESSION['admin']);
        }
    }
    return $admin;
}

function admin_fingerprint($row)
{
    return substr(hash('sha256', $row['id'] . '|' . $row['password_hash']), 0, 24);
}

function login_admin($row)
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = array('id' => (int) $row['id'], 'fp' => admin_fingerprint($row));
}

function logout_admin()
{
    start_session();
    unset($_SESSION['admin']);
    session_regenerate_id(true);
}

function require_admin()
{
    $admin = current_admin();
    if (!$admin) {
        redirect('/admin/login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/admin'));
    }
    return $admin;
}

/* ───────── 로그인 시도 제한: 15분 안에 5번 틀리면 15분 잠금 ───────── */

function throttle_key($scope, $name)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return substr(hash('sha256', $scope . '|' . $ip . '|' . strtolower($name)), 0, 40);
}

function throttle_locked($key)
{
    $row = q_one('SELECT locked_until FROM login_attempts WHERE k = ?', array($key));
    return $row && (int) $row['locked_until'] > time() ? (int) $row['locked_until'] - time() : 0;
}

function throttle_fail($key)
{
    $now = time();
    $row = q_one('SELECT * FROM login_attempts WHERE k = ?', array($key));
    if (!$row || (int) $row['first_at'] < $now - 900) {
        q('DELETE FROM login_attempts WHERE k = ?', array($key));
        q_insert('login_attempts', array('k' => $key, 'fails' => 1, 'first_at' => $now, 'locked_until' => 0));
        return;
    }
    $fails = (int) $row['fails'] + 1;
    $locked = $fails >= 5 ? $now + 900 : 0;
    q('UPDATE login_attempts SET fails = ?, locked_until = ? WHERE k = ?', array($locked ? 0 : $fails, $locked, $key));
    if ($locked) {
        q('UPDATE login_attempts SET first_at = ? WHERE k = ?', array($now, $key));
    }
}

function throttle_clear($key)
{
    q('DELETE FROM login_attempts WHERE k = ?', array($key));
}
