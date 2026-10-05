<?php
/**
 * 관리자 › 마켓 운영: 마켓 운영 신청(입금 확인), 추천인 승인, 추천 정산(추천인별 상품 내역·계산·지급), 출금 신청 처리.
 */

function admin_market()
{
    require_admin();
    $status = input('status');
    $where = array_key_exists($status, MARKET_STATUS) ? ' WHERE a.status = ?' : '';
    $apps = q_all('SELECT a.*, u.name AS user_name, u.email AS user_email, r.name AS referrer_name
        FROM market_applications a LEFT JOIN users u ON u.id = a.user_id LEFT JOIN users r ON r.id = a.referrer_user_id'
        . $where . ' ORDER BY a.id DESC LIMIT 300', $where ? array($status) : array());
    render_admin('market', array('title' => '솔루션 신청', 'nav' => 'market', 'tab' => 'apps', 'apps' => $apps, 'status' => $status));
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
    if ($action === 'clear_hosting') {
        q_update('market_applications', (int) $app['id'], array('hosting_url' => '', 'hosting_id' => '', 'hosting_pw' => ''));
        flash($app['app_no'] . ' 서버호스팅 정보를 지웠어요.');
    } elseif ($action === 'note') {
        q_update('market_applications', (int) $app['id'], array('admin_note' => str_replace("\r\n", "\n", input('admin_note'))));
        flash($app['app_no'] . ' 안내 메모를 저장했어요. 신청자의 나의 마켓 화면에 보여요.');
    } elseif (in_array($action, $allowed[$app['status']] ?? array(), true)) {
        set_market_status($app, $action);
        $messages = array('paid' => '입금을 확인했어요. 신청자에게 설치 안내를 해 주세요.', 'cancelled' => '신청을 취소했어요.', 'pending' => '입금 대기로 되돌렸어요.');
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
    if ($action === 'payout' && referrer_of($userId)) {
        // 신청 없이 관리자가 바로 지급(월 정산 등): 출금 가능 금액까지만
        $amount = input_int('amount');
        $balance = referral_balance($userId);
        if ($amount <= 0) {
            flash('지급할 금액을 입력해 주세요.', 'error');
        } elseif ($amount > $balance['available']) {
            flash('출금 가능 금액(' . won($balance['available']) . ')보다 많이 지급할 수 없어요.', 'error');
        } else {
            record_referral_payout($userId, $amount, str_cut(input('admin_memo'), 500, ''));
            flash(won($amount) . ' 지급 완료로 기록했어요.');
        }
        redirect($back);
    }
    if (!referrer_of($userId) || !in_array($action, array('approve', 'reject'), true)) {
        not_found();
    }
    decide_referrer($userId, $action === 'approve', str_cut(input('admin_memo'), 500, ''));
    flash($action === 'approve' ? '추천인을 승인했어요. 추천인 코드가 만들어졌어요.' : '추천인 신청을 반려했어요.');
    redirect($back);
}

/** 추천 정산: 추천인별 기간 실적·누적 잔액, CSV 내려받기 */
function admin_referral_settlement()
{
    require_admin();
    $period = settlement_period(input('month', date('Y-m')));
    $rows = referral_settlement($period);
    $totals = settlement_totals($rows);
    if (input('format') === 'csv') {
        while (ob_get_level()) {
            ob_end_clean();
        }
        $name = '추천정산-' . ($period[0] === 'all' ? '전체' : $period[0]) . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="referral-settlement-' . $period[0] . '.csv"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Cache-Control: private, no-store');
        echo "\xEF\xBB\xBF" . csv_line(array('정산 기간', $period[3], '수익 기준', '솔루션 입금 확인일', '추천 수익률', REFERRAL_RATE . '%'));
        echo csv_line(array('추천인', '이메일', '추천인 코드', '상태', '기간 결제 건수', '기간 결제금액', '기간 수익', '기간 지급액', '누적 수익', '누적 지급', '출금 신청 중', '출금 가능', '은행', '계좌번호', '예금주'));
        foreach ($rows as $r) {
            echo csv_line(array($r['user_name'] ?? '(탈퇴)', $r['user_email'] ?? '', $r['code'], REFERRER_STATUS[$r['status']] ?? $r['status'],
                $r['period_count'], $r['period_sales'], $r['period_commission'], $r['period_payout'],
                $r['balance']['earned'], $r['balance']['paid'], $r['balance']['requested'], $r['balance']['available'],
                $r['bank_name'], $r['bank_account'], $r['bank_holder']));
        }
        echo csv_line(array('합계', '', '', '', $totals['period_count'], $totals['period_sales'], $totals['period_commission'], $totals['period_payout'],
            $totals['earned'], $totals['paid'], $totals['requested'], $totals['available']));
        exit;
    }
    render_admin('market_settlement', array(
        'title' => '추천 정산', 'nav' => 'market', 'tab' => 'settlement',
        'period' => $period, 'months' => settlement_months(), 'rows' => $rows, 'totals' => $totals,
    ));
}

/** 추천인 한 명의 정산: 계산 요약, 추천 상품 내역, 월별 정산, 출금·지급 내역, 바로 지급 */
function admin_referrer_detail($userId)
{
    require_admin();
    $ref = q_one('SELECT r.*, u.name AS user_name, u.email AS user_email FROM referrers r LEFT JOIN users u ON u.id = r.user_id WHERE r.user_id = ?', array((int) $userId));
    if (!$ref) {
        not_found();
    }
    $items = referral_items($userId);
    render_admin('market_referrer', array(
        'title' => '추천인 정산 · ' . ($ref['user_name'] ?? '(탈퇴)'), 'nav' => 'market', 'tab' => 'settlement',
        'ref' => $ref,
        'balance' => referral_balance($userId),
        'items' => $items,
        'salesTotal' => array_sum(array_map(function ($a) {
            return $a['status'] === 'paid' ? (int) $a['total'] : 0;
        }, $items)),
        'paidCount' => count(array_filter($items, function ($a) {
            return $a['status'] === 'paid';
        })),
        'monthly' => referral_monthly($userId),
        'withdrawals' => user_withdrawals($userId, 'referral'),
    ));
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
    // 신청 뒤에 솔루션 결제가 취소되는 등 잔액이 모자라면 지급하지 않습니다.
    list(, $room) = withdrawal_room($row);
    if ($action === 'paid' && (int) $row['amount'] > $room) {
        flash('잔액(' . won(max(0, $room)) . ')보다 많은 신청이라 지급할 수 없어요. 반려해 주세요.', 'error');
        redirect($back);
    }
    q_update('withdrawals', (int) $row['id'], array(
        'status' => $action, 'processed_at' => now(), 'admin_memo' => str_cut(input('admin_memo'), 500, ''),
    ));
    flash($action === 'paid' ? won($row['amount']) . ' 지급 완료로 처리했어요.' : '출금 신청을 반려했어요. 금액은 출금 가능 금액으로 돌아가요.');
    redirect($back);
}
