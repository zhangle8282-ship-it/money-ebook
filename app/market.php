<?php
/**
 * 나의 마켓: 마켓 운영 신청(기간별 요금), 추천인(승인제, 결제금액의 10% 수익), 수익 출금 신청.
 * 결제는 스토어와 같은 무통장 입금이고, 관리자가 입금을 확인하면 운영 기간이 시작되고 추천 수익이 쌓여요.
 */

const MARKET_MONTHLY = 50000;                                        // 1개월 요금
const MARKET_PLANS = array(3 => 0, 6 => 5, 12 => 10, 24 => 12, 36 => 15); // 기간(개월) => 월 요금 할인(%)
const REFERRAL_RATE = 10;                                            // 추천 수익(결제금액의 %)
const WITHDRAW_MIN = 10000;                                          // 최소 출금 금액
const REFERRAL_COOKIE = 'mk_ref';

const MARKET_STATUS = array('pending' => '입금 대기', 'paid' => '운영 중', 'cancelled' => '취소');
const REFERRER_STATUS = array('pending' => '승인 대기', 'approved' => '활동 중', 'rejected' => '반려');
const WITHDRAW_STATUS = array('requested' => '출금 신청', 'paid' => '지급 완료', 'rejected' => '반려');

/** 기간별 요금표: months => [months, discount, monthly, total, list(정가), saving] */
function market_plans()
{
    $plans = array();
    foreach (MARKET_PLANS as $months => $discount) {
        $monthly = (int) round(MARKET_MONTHLY * (100 - $discount) / 100);
        $list = MARKET_MONTHLY * $months;
        $total = $monthly * $months;
        $plans[$months] = array(
            'months' => $months, 'discount' => $discount, 'monthly' => $monthly,
            'total' => $total, 'list' => $list, 'saving' => $list - $total,
        );
    }
    return $plans;
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

function create_market_application($user, $plan, $form, $referrer)
{
    do {
        $no = 'M' . date('ymd') . '-' . random_code(5);
    } while (q_value('SELECT id FROM market_applications WHERE app_no = ?', array($no)));
    $id = q_insert('market_applications', array(
        'app_no' => $no,
        'user_id' => (int) $user['id'],
        'months' => $plan['months'],
        'monthly_price' => $plan['monthly'],
        'discount' => $plan['discount'],
        'total' => $plan['total'],
        'domain' => $form['domain'],
        'market_name' => $form['market_name'],
        'phone' => $form['phone'],
        'depositor' => $form['depositor'],
        'referrer_user_id' => $referrer ? (int) $referrer['user_id'] : null,
        'referral_code' => $referrer ? $referrer['code'] : '',
        'commission' => $referrer ? intdiv($plan['total'] * REFERRAL_RATE, 100) : 0,
        'status' => 'pending',
        'bank_name' => setting('bank_name'),
        'bank_account' => setting('bank_account'),
        'bank_holder' => setting('bank_holder'),
        'admin_note' => '',
        'created_at' => now(),
    ));
    return q_one('SELECT * FROM market_applications WHERE id = ?', array($id));
}

function user_market_applications($userId)
{
    return q_all('SELECT * FROM market_applications WHERE user_id = ? ORDER BY id DESC', array((int) $userId));
}

/** 신청 상태 바꾸기. 입금 확인(paid)이면 오늘부터 운영 기간을 셉니다. */
function set_market_status($app, $status)
{
    $row = array('status' => $status);
    if ($status === 'paid') {
        $row['paid_at'] = now();
        $row['cancelled_at'] = null;
        $row['starts_at'] = date('Y-m-d');
        $row['ends_at'] = date('Y-m-d', strtotime('+' . (int) $app['months'] . ' months -1 day'));
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
    $paid = (int) q_value("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'paid'", array((int) $userId));
    $requested = (int) q_value("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'requested'", array((int) $userId));
    return array(
        'earned' => $earned, 'expected' => $expected, 'paid' => $paid, 'requested' => $requested,
        'available' => max(0, $earned - $paid - $requested),
    );
}

function user_withdrawals($userId)
{
    return q_all('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC', array((int) $userId));
}

/** 관리자 메뉴에 표시할 처리 대기 건수 */
function market_pending_counts()
{
    return array(
        'applications' => (int) q_value("SELECT COUNT(*) FROM market_applications WHERE status = 'pending'"),
        'referrers' => (int) q_value("SELECT COUNT(*) FROM referrers WHERE status = 'pending'"),
        'withdrawals' => (int) q_value("SELECT COUNT(*) FROM withdrawals WHERE status = 'requested'"),
    );
}
