<?php
/**
 * 나의 마켓(로그인한 회원): 마켓 운영 신청, 추천인 신청·수익·출금 신청.
 */

function base_url()
{
    $host = preg_replace('/[^A-Za-z0-9.:\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return (is_https() ? 'https' : 'http') . '://' . $host;
}

/** 도메인 입력 정리: https://, 경로, www 앞 공백 등을 떼고 소문자로 */
function clean_domain($value)
{
    $d = strtolower(trim($value));
    $d = preg_replace('~^[a-z]+://~', '', $d);
    $d = preg_replace('~[/?#].*$~', '', $d);
    return $d;
}

function page_market()
{
    $user = current_user();
    $plans = market_plans();
    $errors = array();
    $form = array(
        'months' => input_int('months', 12),
        'domain' => clean_domain(input('domain')),
        'market_name' => str_cut(input('market_name'), 100, ''),
        'phone' => str_cut(input('phone'), 40, ''),
        'depositor' => input('depositor', $user ? $user['name'] : ''),
    );

    if (is_post()) {
        $user = require_user('/market');
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        }
        if (!isset($plans[$form['months']])) {
            $errors[] = '운영 기간을 골라 주세요.';
        }
        if ($form['domain'] !== '' && !preg_match('/^([a-z0-9가-힣]([a-z0-9가-힣-]*[a-z0-9가-힣])?\.)+[a-z가-힣]{2,}$/u', $form['domain'])) {
            $errors[] = '희망 도메인을 example.com 처럼 적어 주세요.';
        }
        if ($form['depositor'] === '' || str_len($form['depositor']) > 30) {
            $errors[] = '입금자명을 30자 이내로 입력해 주세요.';
        }
        // 추천인은 홍보 링크(?ref=코드)로 들어온 경우에만 잡습니다. 활동 중이 아니거나 내 코드면 추천 없이 신청돼요.
        $referrer = find_referrer_by_code(remembered_referral());
        if ($referrer && (int) $referrer['user_id'] === (int) $user['id']) {
            $referrer = null;
        }
        if (!bank_ready()) {
            $errors[] = '입금 계좌가 아직 준비되지 않았어요. 잠시 뒤에 다시 시도해 주세요.';
        }
        if (input('agree') !== '1') {
            $errors[] = '신청 내용 확인에 동의해 주세요.';
        }
        if (!$errors) {
            $app = create_market_application($user, $plans[$form['months']], $form, $referrer);
            flash('신청이 접수됐어요. 아래 계좌로 ' . won($app['total']) . '을 입금해 주세요.');
            redirect('/market#my-apps');
        }
    }

    render('market', array(
        'title' => '나의 마켓',
        'nav' => 'market',
        'user' => $user,
        'plans' => $plans,
        'form' => $form,
        'errors' => $errors,
        'apps' => $user ? user_market_applications($user['id']) : array(),
    ));
}

function action_market_cancel($no)
{
    $user = require_user('/market');
    require_csrf('/market#my-apps');
    $app = q_one('SELECT * FROM market_applications WHERE app_no = ? AND user_id = ?', array($no, (int) $user['id']));
    if ($app && $app['status'] === 'pending') {
        set_market_status($app, 'cancelled');
        flash('신청을 취소했어요.');
    }
    redirect('/market#my-apps');
}

function page_market_referral()
{
    $user = require_user('/market/referral');
    $ref = referrer_of($user['id']);

    if (is_post()) {
        require_csrf('/market/referral');
        $action = input('action');
        if ($action === 'apply' && (!$ref || $ref['status'] === 'rejected')) {
            apply_referrer($user['id'], str_cut(str_replace("\r\n", "\n", input('intro')), 1000, ''));
            flash('추천인 신청을 접수했어요. 관리자가 승인하면 추천인 코드가 나와요.');
        } elseif ($action === 'bank' && $ref) {
            $bank = array('bank_name' => str_cut(input('bank_name'), 30, ''), 'bank_account' => str_cut(input('bank_account'), 40, ''), 'bank_holder' => str_cut(input('bank_holder'), 30, ''));
            if (in_array('', $bank, true)) {
                flash('은행, 계좌번호, 예금주를 모두 입력해 주세요.', 'error');
            } else {
                q('UPDATE referrers SET bank_name = ?, bank_account = ?, bank_holder = ? WHERE user_id = ?',
                    array($bank['bank_name'], $bank['bank_account'], $bank['bank_holder'], (int) $user['id']));
                flash('출금 계좌를 저장했어요.');
            }
        } elseif ($action === 'withdraw' && $ref && $ref['status'] === 'approved') {
            $amount = input_int('amount');
            $balance = referral_balance($user['id']);
            if ($ref['bank_name'] === '' || $ref['bank_account'] === '' || $ref['bank_holder'] === '') {
                flash('출금 계좌를 먼저 저장해 주세요.', 'error');
            } elseif ($amount < WITHDRAW_MIN) {
                flash('출금은 ' . won(WITHDRAW_MIN) . '부터 신청할 수 있어요.', 'error');
            } elseif ($amount > $balance['available']) {
                flash('출금 가능 금액(' . won($balance['available']) . ')보다 많아요.', 'error');
            } else {
                q_insert('withdrawals', array(
                    'user_id' => (int) $user['id'], 'amount' => $amount,
                    'bank_name' => $ref['bank_name'], 'bank_account' => $ref['bank_account'], 'bank_holder' => $ref['bank_holder'],
                    'status' => 'requested', 'admin_memo' => '', 'created_at' => now(),
                ));
                flash(won($amount) . ' 출금을 신청했어요. 관리자가 확인한 뒤 계좌로 보내 드려요.');
            }
        }
        redirect('/market/referral');
    }

    render('market_referral', array(
        'title' => '추천인 · 수익',
        'nav' => 'market',
        'user' => $user,
        'ref' => $ref,
        'balance' => $ref ? referral_balance($user['id']) : null,
        'referred' => $ref ? referred_applications($user['id']) : array(),
        'withdrawals' => $ref ? user_withdrawals($user['id']) : array(),
        'shareUrl' => $ref && $ref['code'] ? base_url() . '/market?ref=' . $ref['code'] : '',
    ));
}
