<?php
/**
 * 나의 마켓: 전자책 마켓 솔루션 판매(한 번 결제, 서버호스팅·도메인 별도), 추천인(승인제, 결제금액의 10% 수익), 수익 출금 신청.
 * 결제는 스토어와 같은 무통장 입금이고, 관리자가 입금을 확인하면 결제 완료가 되고 추천 수익이 쌓여요.
 */

// 판매하는 솔루션. 가격은 한 번 결제 금액이에요(서버호스팅·도메인 별도).
const MARKET_PRODUCTS = array(
    'basic' => array(
        'name' => '전자책 마켓',
        'price' => 99000,
        'summary' => '지금 보고 계신 전자책 판매 사이트 그대로',
        'features' => array('전자책 등록(표지·EPUB·PDF)과 무료 미리보기', '무통장 입금 주문·입금 확인', '구매자 리뷰·평점', 'PC·태블릿·모바일 맞춤 뷰어', '관리자 화면·설치 도구·업데이트'),
    ),
    'affiliate' => array(
        'name' => '전자책 마켓 + 제휴 프로그램',
        'price' => 199000,
        'summary' => '전자책 마켓에 제휴(추천인) 프로그램을 더한 형태',
        'features' => array('전자책 마켓 기능 모두', '제휴 파트너(추천인) 신청·승인', '파트너별 홍보 링크와 추천 가입 내역', '추천 수익 적립', '출금 계좌·출금 신청 관리'),
    ),
    'openmarket' => array(
        'name' => '전자책 마켓 + 제휴 프로그램 + 오픈마켓',
        'price' => 399000,
        'summary' => '다른 사람이 자기 전자책을 올려 팔고, 판매 수수료를 받는 형태',
        'features' => array('전자책 마켓·제휴 프로그램 기능 모두', '회원이 직접 전자책 등록(판매자 입점)', '관리자 승인 후 판매 시작', '판매 수수료율 관리자 설정', '판매자 정산(출금 신청) 관리'),
    ),
);
const REFERRAL_RATE = 10;                                            // 추천 수익(결제금액의 %)
const WITHDRAW_MIN = 10000;                                          // 최소 출금 금액
const REFERRAL_COOKIE = 'mk_ref';

const MARKET_STATUS = array('pending' => '입금 대기', 'paid' => '결제 완료', 'cancelled' => '취소');
const REFERRER_STATUS = array('pending' => '승인 대기', 'approved' => '활동 중', 'rejected' => '반려');
const WITHDRAW_STATUS = array('requested' => '출금 신청', 'paid' => '지급 완료', 'rejected' => '반려');

/** 신청한 상품 이름(예전 기간제 신청은 "마켓 운영 N개월") */
function market_product_name($app)
{
    $product = (string) ($app['product'] ?? '');
    if (array_key_exists($product, MARKET_PRODUCTS)) {
        return MARKET_PRODUCTS[$product]['name'];
    }
    return '마켓 운영 ' . (int) $app['months'] . '개월';
}

function random_code($length, $prefix = '')
{
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code = $prefix;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/* ───────── 추천 링크(?ref=코드) 기억하기 ───────── */

/** 방문 주소에 ?ref=코드 가 있으면 30일 동안 기억해 신청서에 자동으로 넣어 줍니다. */
function capture_referral()
{
    $code = strtoupper(str_in_get('ref'));
    if ($code === '' || !preg_match('/^[A-Z0-9]{4,20}$/', $code)) {
        return;
    }
    setcookie(REFERRAL_COOKIE, $code, array(
        'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax',
    ));
    $_COOKIE[REFERRAL_COOKIE] = $code;
}

function str_in_get($key)
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? trim($_GET[$key]) : '';
}

function remembered_referral()
{
    $code = isset($_COOKIE[REFERRAL_COOKIE]) && is_string($_COOKIE[REFERRAL_COOKIE]) ? strtoupper($_COOKIE[REFERRAL_COOKIE]) : '';
    return preg_match('/^[A-Z0-9]{4,20}$/', $code) ? $code : '';
}

/* ───────── 마켓 운영 신청 ───────── */

function create_market_application($user, $productKey, $form, $referrer)
{
    $product = MARKET_PRODUCTS[$productKey];
    do {
        $no = 'M' . date('ymd') . '-' . random_code(5);
    } while (q_value('SELECT id FROM market_applications WHERE app_no = ?', array($no)));
    $id = q_insert('market_applications', array(
        'app_no' => $no,
        'user_id' => (int) $user['id'],
        'product' => $productKey,
        'months' => 0,
        'monthly_price' => 0,
        'discount' => 0,
        'total' => $product['price'],
        'domain' => $form['domain'],
        'market_name' => $form['market_name'],
        'phone' => $form['phone'],
        'depositor' => $form['depositor'],
        'referrer_user_id' => $referrer ? (int) $referrer['user_id'] : null,
        'referral_code' => $referrer ? $referrer['code'] : '',
        'commission' => $referrer ? intdiv($product['price'] * REFERRAL_RATE, 100) : 0,
        'status' => 'pending',
        'bank_name' => setting('bank_name'),
        'bank_account' => setting('bank_account'),
        'bank_holder' => setting('bank_holder'),
        'admin_note' => '',
        'hosting_url' => $form['hosting_url'],
        'hosting_id' => $form['hosting_id'],
        'hosting_pw' => $form['hosting_pw'],
        'created_at' => now(),
    ));
    return q_one('SELECT * FROM market_applications WHERE id = ?', array($id));
}

function user_market_applications($userId)
{
    return q_all('SELECT * FROM market_applications WHERE user_id = ? ORDER BY id DESC', array((int) $userId));
}

/** 신청 상태 바꾸기. 예전 기간제 신청은 입금 확인(paid)한 날부터 운영 기간을 셉니다. */
function set_market_status($app, $status)
{
    $row = array('status' => $status);
    if ($status === 'paid') {
        $row['paid_at'] = now();
        $row['cancelled_at'] = null;
        $months = (int) $app['months'];
        $row['starts_at'] = $months ? date('Y-m-d') : null;
        $row['ends_at'] = $months ? date('Y-m-d', strtotime('+' . $months . ' months -1 day')) : null;
    } elseif ($status === 'cancelled') {
        $row['cancelled_at'] = now();
        $row['paid_at'] = null;
        $row['starts_at'] = null;
        $row['ends_at'] = null;
    } else {
        $row['paid_at'] = null;
        $row['cancelled_at'] = null;
        $row['starts_at'] = null;
        $row['ends_at'] = null;
    }
    q_update('market_applications', (int) $app['id'], $row);
}

/* ───────── 서버호스팅 정보 (설치용, 암호화 저장) ───────── */

/** storage/secret.key 에 둔 암호화 키(처음 쓸 때 만듭니다). DB만 새어 나가도 비밀번호는 읽을 수 없어요. */
function secret_key()
{
    $file = STORAGE_DIR . '/secret.key';
    if (!is_file($file)) {
        @file_put_contents($file, bin2hex(random_bytes(32)), LOCK_EX);
        @chmod($file, 0600);
    }
    $hex = trim((string) @file_get_contents($file));
    if (strlen($hex) !== 64) {
        throw new RuntimeException('암호화 키를 읽을 수 없어요. storage 폴더 권한을 확인해 주세요.');
    }
    return hex2bin($hex);
}

function encrypt_secret($plain)
{
    if ($plain === '') {
        return '';
    }
    $key = secret_key();
    if (function_exists('sodium_crypto_secretbox')) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 's1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $key));
    }
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return 'o1:' . base64_encode($iv . $tag . $cipher);
}

function decrypt_secret($stored)
{
    $stored = (string) $stored;
    if ($stored === '') {
        return '';
    }
    $raw = base64_decode(substr($stored, 3), true);
    if ($raw === false) {
        return '';
    }
    $key = secret_key();
    if (strncmp($stored, 's1:', 3) === 0 && function_exists('sodium_crypto_secretbox_open')) {
        $n = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        $plain = sodium_crypto_secretbox_open(substr($raw, $n), substr($raw, 0, $n), $key);
        return $plain === false ? '' : $plain;
    }
    if (strncmp($stored, 'o1:', 3) === 0) {
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? '' : $plain;
    }
    return '';
}

/** 신청서·나의 마켓에서 받은 호스팅 정보. 비밀번호를 비워 보내면 기존 비밀번호를 그대로 둡니다. */
function hosting_fields_from_request($keepPassword = null)
{
    $url = str_cut(input('hosting_url'), 255, '');
    $id = str_cut(input('hosting_id'), 100, '');
    $pw = input_raw('hosting_pw');
    $row = array('hosting_url' => $url, 'hosting_id' => $id);
    if ($pw !== '') {
        $row['hosting_pw'] = encrypt_secret(str_cut($pw, 200, ''));
    } elseif ($keepPassword === null) {
        $row['hosting_pw'] = '';
    }
    return $row;
}

function has_hosting_info($app)
{
    return trim((string) ($app['hosting_url'] ?? '')) !== '' || trim((string) ($app['hosting_id'] ?? '')) !== '' || (string) ($app['hosting_pw'] ?? '') !== '';
}

/** 관리자가 제휴코드 칸에 제휴 링크(https://...)를 넣었으면 그 링크, 아니면 '' */
function cafe24_affiliate_link()
{
    $code = trim(setting('cafe24_code'));
    return preg_match('~^https?://[^\s]+$~i', $code) ? $code : '';
}

/** 링크에 보여 줄 짧은 주소(예: https://hosting.cafe24.com?r_id=... → hosting.cafe24.com) */
function link_host($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    return $host ? strtolower($host) : $url;
}

/** 카페24로 가는 주소: 제휴 링크 → 관리자 설정의 카페24 링크 → 카페24 호스팅 첫 화면 */
function cafe24_url()
{
    $link = cafe24_affiliate_link();
    if ($link !== '') {
        return $link;
    }
    $url = trim(setting('cafe24_url'));
    return preg_match('~^https?://~i', $url) ? $url : 'https://hosting.cafe24.com/';
}

/* ───────── 추천인 ───────── */

function referrer_of($userId)
{
    return q_one('SELECT * FROM referrers WHERE user_id = ?', array((int) $userId));
}

/** 활동 중(승인된) 추천인 코드 찾기 */
function find_referrer_by_code($code)
{
    $code = strtoupper(trim((string) $code));
    return $code === '' ? null : q_one("SELECT * FROM referrers WHERE code = ? AND status = 'approved'", array($code));
}

function apply_referrer($userId, $intro)
{
    if (referrer_of($userId)) {
        q("UPDATE referrers SET status = 'pending', intro = ?, admin_memo = '', decided_at = NULL WHERE user_id = ?", array($intro, (int) $userId));
    } else {
        q_insert('referrers', array(
            'user_id' => (int) $userId, 'code' => null, 'status' => 'pending', 'intro' => $intro,
            'admin_memo' => '', 'created_at' => now(),
        ));
    }
}

/** 승인하면 추천인 코드를 만들어 줍니다(한 번 만든 코드는 그대로 유지). */
function decide_referrer($userId, $approve, $memo)
{
    $row = referrer_of($userId);
    if (!$row) {
        return;
    }
    $code = $row['code'];
    if ($approve && !$code) {
        do {
            $code = random_code(6);
        } while (q_value('SELECT user_id FROM referrers WHERE code = ?', array($code)));
    }
    q('UPDATE referrers SET status = ?, code = ?, admin_memo = ?, decided_at = ? WHERE user_id = ?',
        array($approve ? 'approved' : 'rejected', $code, $memo, now(), (int) $userId));
}

/** 추천으로 들어온 신청(상품) 목록 */
function referred_applications($userId)
{
    return q_all('SELECT a.*, u.name AS user_name FROM market_applications a LEFT JOIN users u ON u.id = a.user_id
        WHERE a.referrer_user_id = ? ORDER BY a.id DESC', array((int) $userId));
}

/** 추천 수익: 결제 완료된 신청의 수익 합계에서 지급됐거나 신청 중인 출금을 뺀 금액이 출금 가능 금액이에요. */
function referral_balance($userId)
{
    $earned = (int) q_value("SELECT COALESCE(SUM(commission), 0) FROM market_applications WHERE referrer_user_id = ? AND status = 'paid'", array((int) $userId));
    $expected = (int) q_value("SELECT COALESCE(SUM(commission), 0) FROM market_applications WHERE referrer_user_id = ? AND status = 'pending'", array((int) $userId));
    $paid = (int) q_value("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND kind = 'referral' AND status = 'paid'", array((int) $userId));
    $requested = (int) q_value("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND kind = 'referral' AND status = 'requested'", array((int) $userId));
    return array(
        'earned' => $earned, 'expected' => $expected, 'paid' => $paid, 'requested' => $requested,
        'available' => max(0, $earned - $paid - $requested),
    );
}

/** $kind: referral(추천 수익) | seller(판매 정산) */
function user_withdrawals($userId, $kind = 'referral')
{
    return q_all('SELECT * FROM withdrawals WHERE user_id = ? AND kind = ? ORDER BY id DESC', array((int) $userId, $kind));
}

const WITHDRAW_KIND = array('referral' => '추천 수익', 'seller' => '판매 정산');

/* ───────── 오픈마켓: 판매자 ───────── */

function seller_account($userId)
{
    return q_one('SELECT * FROM sellers WHERE user_id = ?', array((int) $userId));
}

function seller_books($userId)
{
    return q_all(book_select_sql() . ' WHERE b.seller_user_id = ? ORDER BY b.id DESC', array((int) $userId));
}

/** 판매 내역(입금 대기·결제 완료 주문의 내 책) */
function seller_sales($userId)
{
    return q_all("SELECT oi.*, o.order_no, o.status AS order_status, o.created_at AS ordered_at, o.paid_at
        FROM order_items oi JOIN orders o ON o.id = oi.order_id
        WHERE oi.seller_user_id = ? AND o.status IN ('pending', 'paid') ORDER BY oi.id DESC LIMIT 200", array((int) $userId));
}

/** 판매 정산: 결제 완료된 판매의 판매자 몫에서 지급됐거나 신청 중인 정산을 뺀 금액이 출금 가능 금액이에요. */
function seller_balance($userId)
{
    $earned = (int) q_value("SELECT COALESCE(SUM(oi.seller_amount), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
        WHERE oi.seller_user_id = ? AND o.status = 'paid'", array((int) $userId));
    $expected = (int) q_value("SELECT COALESCE(SUM(oi.seller_amount), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
        WHERE oi.seller_user_id = ? AND o.status = 'pending'", array((int) $userId));
    $paid = (int) q_value("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND kind = 'seller' AND status = 'paid'", array((int) $userId));
    $requested = (int) q_value("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND kind = 'seller' AND status = 'requested'", array((int) $userId));
    return array(
        'earned' => $earned, 'expected' => $expected, 'paid' => $paid, 'requested' => $requested,
        'available' => max(0, $earned - $paid - $requested),
    );
}

/** 관리자 메뉴에 표시할 처리 대기 건수 */
function market_pending_counts()
{
    return array(
        'applications' => (int) q_value("SELECT COUNT(*) FROM market_applications WHERE status = 'pending'"),
        'referrers' => (int) q_value("SELECT COUNT(*) FROM referrers WHERE status = 'pending'"),
        'withdrawals' => (int) q_value("SELECT COUNT(*) FROM withdrawals WHERE status = 'requested'"),
        'reviews' => (int) q_value("SELECT COUNT(*) FROM books WHERE status = 'review'"),
    );
}
