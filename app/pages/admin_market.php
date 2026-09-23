<?php
/**
 * 관리자 › 마켓 운영: 마켓 운영 신청(입금 확인), 추천인 승인, 출금 신청 처리.
 */

function admin_market()
{
    require_admin();
    $status = input('status');
    $where = array_key_exists($status, MARKET_STATUS) ? ' WHERE a.status = ?' : '';
    $apps = q_all('SELECT a.*, u.name AS user_name, u.email AS user_email, r.name AS referrer_name
        FROM market_applications a LEFT JOIN users u ON u.id = a.user_id LEFT JOIN users r ON r.id = a.referrer_user_id'
        . $where . ' ORDER BY a.id DESC LIMIT 300', $where ? array($status) : array());
    render_admin('market', array('title' => '마켓 운영 신청', 'nav' => 'market', 'tab' => 'apps', 'apps' => $apps, 'status' => $status));
}

function admin_market_action($id)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/market');
    require_csrf($back);
    $app = q_one('SELECT * FROM market_applications WHERE id = ?', array((int) $id));
    if (!$app) {
        not_found();
    }
    $action = input('action');
    $allowed = array('pending' => array('paid', 'cancelled'), 'paid' => array('pending'), 'cancelled' => array('pending'));
    if ($action === 'note') {
        q_update('market_applications', (int) $app['id'], array('admin_note' => str_replace("\r\n", "\n", input('admin_note'))));
        flash($app['app_no'] . ' 안내 메모를 저장했어요. 신청자의 나의 마켓 화면에 보여요.');
    } elseif (in_array($action, $allowed[$app['status']] ?? array(), true)) {
        set_market_status($app, $action);
        $messages = array('paid' => '입금을 확인했어요. 오늘부터 운영 기간이 시작돼요.', 'cancelled' => '신청을 취소했어요.', 'pending' => '입금 대기로 되돌렸어요.');
        flash($app['app_no'] . ' · ' . $messages[$action]);
    } else {
        flash('바꿀 수 없는 상태예요.', 'error');
    }
    redirect($back);
}

function admin_referrers()
{
    require_admin();
    $status = input('status');
    $where = array_key_exists($status, REFERRER_STATUS) ? ' WHERE r.status = ?' : '';
    $rows = q_all("SELECT r.*, u.name AS user_name, u.email AS user_email,
            (SELECT COALESCE(SUM(commission), 0) FROM market_applications a WHERE a.referrer_user_id = r.user_id AND a.status = 'paid') AS earned,
            (SELECT COUNT(*) FROM market_applications a WHERE a.referrer_user_id = r.user_id) AS referred
        FROM referrers r LEFT JOIN users u ON u.id = r.user_id" . $where . ' ORDER BY r.created_at DESC', $where ? array($status) : array());
    render_admin('market_referrers', array('title' => '추천인 관리', 'nav' => 'market', 'tab' => 'referrers', 'rows' => $rows, 'status' => $status));
}

function admin_referrer_action($userId)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/market/referrers');
    require_csrf($back);
    $action = input('action');
    if (!referrer_of($userId) || !in_array($action, array('approve', 'reject'), true)) {
        not_found();
    }
    decide_referrer($userId, $action === 'approve', str_cut(input('admin_memo'), 500, ''));
    flash($action === 'approve' ? '추천인을 승인했어요. 추천인 코드가 만들어졌어요.' : '추천인 신청을 반려했어요.');
    redirect($back);
}

function admin_withdrawals()
{
    require_admin();
    $status = input('status');
    $where = array_key_exists($status, WITHDRAW_STATUS) ? ' WHERE w.status = ?' : '';
    $rows = q_all('SELECT w.*, u.name AS user_name, u.email AS user_email, r.code FROM withdrawals w
        LEFT JOIN users u ON u.id = w.user_id LEFT JOIN referrers r ON r.user_id = w.user_id'
        . $where . ' ORDER BY w.id DESC LIMIT 300', $where ? array($status) : array());
    render_admin('market_withdrawals', array('title' => '출금 신청', 'nav' => 'market', 'tab' => 'withdrawals', 'rows' => $rows, 'status' => $status));
}

function admin_withdrawal_action($id)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/market/withdrawals');
    require_csrf($back);
    $row = q_one('SELECT * FROM withdrawals WHERE id = ?', array((int) $id));
    $action = input('action');
    if (!$row || $row['status'] !== 'requested' || !in_array($action, array('paid', 'rejected'), true)) {
        flash('처리할 수 없는 출금 신청이에요.', 'error');
        redirect($back);
    }
    q_update('withdrawals', (int) $row['id'], array(
        'status' => $action, 'processed_at' => now(), 'admin_memo' => str_cut(input('admin_memo'), 500, ''),
    ));
    flash($action === 'paid' ? won($row['amount']) . ' 지급 완료로 처리했어요.' : '출금 신청을 반려했어요. 금액은 출금 가능 금액으로 돌아가요.');
    redirect($back);
}
