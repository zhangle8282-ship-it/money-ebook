<?php
/**
 * 그린청소 관리자 › 도급 정산: 월별 정산표, 청소 목록, 새 청소 · 고치기.
 */

function admin_contracts_month()
{
    require_admin();
    $month = valid_month(input('month')) ? input('month') : date('Y-m');
    $rows = month_settlements($month);
    $sum = settlement_sum($rows);
    if (input('format') === 'csv') {
        while (ob_get_level()) {
            ob_end_clean();
        }
        $name = '도급정산-' . $month . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="contract-settlement-' . $month . '.csv"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Cache-Control: private, no-store');
        echo "\xEF\xBB\xBF" . contract_csv_line(array('정산 월', month_label($month), '계산', '세금 10% 뺀 금액을 청소 담당 파트너와 도급(대표·운영 파트너)이 나눔, 청소 담당 파트너는 원천징수 3.3%'));
        echo contract_csv_line(array('청소 이름', '거래처', '청소비용', '세금계산서', '세금', '세금 뺀 금액', '청소 담당 비율', '청소 담당 파트너', '청소 담당 몫', '원천징수', '청소 담당 실지급', '도급 비율', '도급 몫', '대표파트너 비율', '대표파트너', '대표파트너 금액', '운영파트너', '운영파트너 금액', '상태', '메모'));
        foreach ($rows as $r) {
            $c = $r['contract'];
            echo contract_csv_line(array($c['name'], $c['client'], $r['fee'], $r['invoice'] ? '발행' : '미발행', $r['tax'], $r['after_tax'],
                $r['byeong_rate'] . '%', $c['byeong_name'], $r['byeong_amount'], $r['withholding_amount'], $r['byeong_pay'],
                $r['contract_rate'] . '%', $r['contract_amount'], $r['gap_rate'] . '%', $c['gap_name'], $r['gap_amount'],
                $c['eul_name'], $r['eul_amount'], SETTLEMENT_STATUS[$r['status']] ?? $r['status'], $r['memo']));
        }
        echo contract_csv_line(array('합계', '', $sum['fee'], '', $sum['tax'], $sum['fee'] - $sum['tax'], '', '', $sum['byeong_amount'], $sum['withholding_amount'], $sum['byeong_pay'], '', $sum['contract_amount'], '', '', $sum['gap_amount'], '', $sum['eul_amount'], $sum['done'] . '/' . $sum['count'] . ' 완료', ''));
        exit;
    }
    $year = (int) substr($month, 0, 4);
    render_admin('contracts_month', array(
        'title' => '도급 정산', 'nav' => 'contracts', 'tab' => 'month', 'roles' => contract_roles(),
        'month' => $month, 'rows' => $rows, 'sum' => $sum,
        'year' => $year, 'yearSummary' => year_settlement_summary($year),
        'contractCount' => (int) q_value('SELECT COUNT(*) FROM contracts'),
    ));
}

/** 정산 처리: done(정산 완료) · undo(정산 전으로) · save(이번 달 금액·메모) · done_all(모두 정산 완료) */
function admin_contracts_settle()
{
    require_admin();
    $month = valid_month(input('month')) ? input('month') : date('Y-m');
    $back = '/admin/contracts?month=' . $month;
    require_csrf($back);
    $action = input('action');
    $by = current_admin()['username'] ?? '';
    // 모든 단계에 지금 시각을 넣고 정산 완료로
    $allSteps = function ($calc) use ($by) {
        $changes = array('status' => 'done', 'settled_at' => now(), 'settled_by' => $by);
        foreach (array_keys(settlement_steps($calc)) as $k) {
            if (empty($calc['step_' . $k])) {
                $changes['step_' . $k] = now();
            }
        }
        return $changes;
    };
    if ($action === 'done_all') {
        $n = 0;
        foreach (month_settlements($month) as $r) {
            if ($r['status'] !== 'done') {
                save_month_settlement($r['contract'], $month, $allSteps($r));
                $n++;
            }
        }
        flash(month_label($month) . ' 정산 ' . $n . '건을 완료로 처리했어요.');
        redirect($back);
    }
    $contract = find_contract(input_int('contract_id'));
    if (!$contract) {
        flash('청소를 찾을 수 없어요.', 'error');
        redirect($back);
    }
    $current = null;
    foreach (month_settlements($month) as $r) {
        if ((int) $r['contract']['id'] === (int) $contract['id']) {
            $current = $r;
        }
    }
    if ($action === 'step' && $current && array_key_exists(input('step'), SETTLEMENT_STEPS)) {
        // 단계 하나 체크/해제 → 필요한 단계가 모두 끝나면 정산 완료
        $step = input('step');
        $on = input('on') === '1';
        $current['step_' . $step] = $on ? now() : null;
        $changes = array('step_' . $step => $current['step_' . $step]);
        $done = true;
        foreach (array_keys(settlement_steps($current)) as $k) {
            if (empty($current['step_' . $k])) {
                $done = false;
            }
        }
        $changes += $done ? array('status' => 'done', 'settled_at' => now(), 'settled_by' => $by) : array('status' => 'pending', 'settled_at' => null, 'settled_by' => '');
        save_month_settlement($contract, $month, $changes);
        flash($contract['name'] . ' · ' . SETTLEMENT_STEPS[$step] . ($on ? ' 체크했어요.' : ' 체크를 풀었어요.') . ($done ? ' 모든 단계가 끝나 정산 완료로 기록했어요.' : ''));
    } elseif ($action === 'done' && $current) {
        save_month_settlement($contract, $month, $allSteps($current));
        flash($contract['name'] . ' · ' . month_label($month) . ' 정산을 완료했어요.');
    } elseif ($action === 'undo') {
        save_month_settlement($contract, $month, array('status' => 'pending', 'settled_at' => null, 'settled_by' => '',
            'step_received' => null, 'step_invoiced' => null, 'step_paid_byeong' => null, 'step_paid_eul' => null));
        flash($contract['name'] . ' · ' . month_label($month) . ' 정산을 정산 전으로 되돌렸어요.');
    } elseif ($action === 'save') {
        $fee = input_int('fee', -1);
        if ($fee < 0) {
            flash('이번 달 청소비용을 숫자로 넣어 주세요.', 'error');
            redirect($back);
        }
        save_month_settlement($contract, $month, array('fee' => $fee, 'memo' => str_cut(trim(input('memo')), 300, '')));
        flash($contract['name'] . ' · ' . month_label($month) . ' 금액을 ' . won($fee) . '으로 저장했어요.');
    } elseif ($action === 'reset') {
        q("DELETE FROM contract_settlements WHERE contract_id = ? AND month = ? AND status <> 'done'", array((int) $contract['id'], $month));
        flash($contract['name'] . ' · ' . month_label($month) . ' 금액을 계약 조건대로 되돌렸어요.');
    }
    redirect($back);
}

function admin_contracts_list()
{
    require_admin();
    $now = date('Y-m');
    $contracts = q_all('SELECT c.*, (SELECT COUNT(*) FROM contract_settlements s WHERE s.contract_id = c.id AND s.status = \'done\') AS done_count FROM contracts c ORDER BY c.name, c.id');
    foreach ($contracts as &$c) {
        $c['calc'] = contract_calc($c['monthly_fee'], (int) $c['invoice'], $c['contract_rate'], $c['gap_rate'], (int) $c['withholding']);
        $c['active'] = $c['start_month'] <= $now && ($c['end_month'] === null || $c['end_month'] === '' || $c['end_month'] >= $now);
        $c['upcoming'] = $c['start_month'] > $now;
    }
    unset($c);
    render_admin('contracts_list', array('title' => '청소 목록', 'nav' => 'contracts', 'tab' => 'list', 'contracts' => $contracts));
}

function admin_contract_form($id = null)
{
    require_admin();
    $contract = $id !== null ? find_contract($id) : null;
    if ($id !== null && !$contract) {
        not_found();
    }
    $form = $contract ?: array(
        'name' => '', 'client' => '', 'monthly_fee' => '', 'invoice' => 1, 'contract_rate' => 10, 'gap_rate' => CONTRACT_GAP_DEFAULT,
        'gap_name' => gc('name'), 'eul_name' => '', 'byeong_name' => '', 'withholding' => 1, 'start_month' => date('Y-m'), 'end_month' => '', 'memo' => '',
    );
    $errors = array();
    if (is_post()) {
        require_csrf($contract ? '/admin/contracts/' . (int) $contract['id'] . '/edit' : '/admin/contracts/new');
        $form = array(
            'name' => str_cut(trim(input('name')), 100, ''),
            'client' => str_cut(trim(input('client')), 100, ''),
            'monthly_fee' => input('monthly_fee') === '' ? '' : input_int('monthly_fee'),
            'invoice' => input('invoice') === '0' ? 0 : 1,
            'contract_rate' => input_int('contract_rate'),
            'gap_rate' => input('gap_rate') === '' ? -1 : input_int('gap_rate', -1),
            'gap_name' => str_cut(trim(input('gap_name')), 60, ''),
            'eul_name' => str_cut(trim(input('eul_name')), 60, ''),
            'byeong_name' => str_cut(trim(input('byeong_name')), 60, ''),
            'withholding' => input('withholding') === '0' ? 0 : 1,
            'start_month' => input('start_month'),
            'end_month' => input('end_month'),
            'memo' => str_cut(str_replace("\r\n", "\n", input('memo')), 1000, ''),
        );
        if ($form['name'] === '') {
            $errors[] = '청소 이름을 적어 주세요.';
        }
        if ($form['monthly_fee'] === '' || (int) $form['monthly_fee'] <= 0) {
            $errors[] = '월 청소비용을 적어 주세요.';
        }
        if (!in_array($form['contract_rate'], CONTRACT_RATES, true)) {
            $errors[] = '도급비용 비율을 10% · 20% · 30% 중에서 골라 주세요.';
        }
        if ($form['gap_rate'] < 0 || $form['gap_rate'] > 100) {
            $errors[] = '대표파트너 비율은 0~100% 사이로 정해 주세요.';
        }
        if (!valid_month($form['start_month'])) {
            $errors[] = '시작 월을 골라 주세요.';
        }
        if ($form['end_month'] !== '' && (!valid_month($form['end_month']) || $form['end_month'] < $form['start_month'])) {
            $errors[] = '끝난 월은 시작 월과 같거나 그 뒤로 골라 주세요.';
        }
        if (!$errors) {
            $data = $form;
            $data['monthly_fee'] = (int) $data['monthly_fee'];
            $data['end_month'] = $data['end_month'] !== '' ? $data['end_month'] : null;
            $data['updated_at'] = now();
            if ($contract) {
                q_update('contracts', (int) $contract['id'], $data);
                flash('‘' . $form['name'] . '’ 청소를 저장했어요. 이미 정산 완료한 달은 그대로이고, 정산 전인 달부터 새 조건으로 계산돼요.');
            } else {
                $data['created_at'] = now();
                q_insert('contracts', $data);
                flash('‘' . $form['name'] . '’ 청소를 추가했어요. ' . month_label($form['start_month']) . '부터 월별 정산에 나와요.');
            }
            redirect('/admin/contracts/list');
        }
    }
    render_admin('contract_form', array(
        'title' => $contract ? '청소 고치기' : '새 청소', 'nav' => 'contracts', 'tab' => $contract ? 'list' : 'new',
        'contract' => $contract, 'form' => $form, 'errors' => $errors,
    ));
}

/** 파트너(대표·운영·청소 담당)가 하는 일: 보기 · 더하기 · 빼기 · 순서 바꾸기 · 처음 목록으로 */
function admin_contract_roles()
{
    require_admin();
    $roles = contract_roles();
    if (is_post()) {
        require_csrf('/admin/contracts/roles');
        $side = array_key_exists(input('side'), CONTRACT_ROLE_SIDES) ? input('side') : null;
        $action = input('action');
        $index = input_int('index', -1);
        if ($action === 'reset') {
            save_settings(array('gc_roles' => ''));
            flash('파트너 역할을 처음 목록으로 되돌렸어요.');
            redirect('/admin/contracts/roles');
        }
        if ($side === null) {
            redirect('/admin/contracts/roles');
        }
        $label = CONTRACT_ROLE_SIDES[$side];
        if ($action === 'add') {
            $task = str_cut(trim(preg_replace('/\s+/u', ' ', input('task'))), 40, '');
            if ($task === '') {
                flash($label . '가 할 일을 적어 주세요.', 'error');
            } elseif (count($roles[$side]) >= CONTRACT_ROLE_MAX) {
                flash($label . '가 하는 일은 ' . CONTRACT_ROLE_MAX . '개까지 넣을 수 있어요.', 'error');
            } elseif (in_array($task, $roles[$side], true)) {
                flash('‘' . $task . '’은(는) 이미 ' . $label . '의 일에 있어요.', 'error');
            } else {
                $roles[$side][] = $task;
                save_contract_roles($roles);
                flash($label . '가 하는 일에 ‘' . $task . '’을(를) 더했어요.');
            }
        } elseif ($action === 'delete' && isset($roles[$side][$index])) {
            $task = $roles[$side][$index];
            array_splice($roles[$side], $index, 1);
            save_contract_roles($roles);
            flash($label . '가 하는 일에서 ‘' . $task . '’을(를) 뺐어요.');
        } elseif (($action === 'up' || $action === 'down') && isset($roles[$side][$index])) {
            $to = $action === 'up' ? $index - 1 : $index + 1;
            if (isset($roles[$side][$to])) {
                $tmp = $roles[$side][$to];
                $roles[$side][$to] = $roles[$side][$index];
                $roles[$side][$index] = $tmp;
                save_contract_roles($roles);
            }
        } elseif (preg_match('/^move_(gap|eul|byeong)$/', (string) $action, $mv) && $mv[1] !== $side && isset($roles[$side][$index])) {
            // 다른 사람에게 넘기기
            $other = $mv[1];
            $task = $roles[$side][$index];
            array_splice($roles[$side], $index, 1);
            if (!in_array($task, $roles[$other], true)) {
                $roles[$other][] = $task;
            }
            save_contract_roles($roles);
            flash('‘' . $task . '’을(를) ' . CONTRACT_ROLE_SIDES[$other] . '가 하는 일로 옮겼어요.');
        }
        redirect('/admin/contracts/roles');
    }
    render_admin('contracts_roles', array('title' => '파트너 역할', 'nav' => 'contracts', 'tab' => 'roles', 'roles' => $roles));
}

function admin_contract_delete($id)
{
    require_admin();
    require_csrf('/admin/contracts/list');
    $contract = find_contract($id);
    if (!$contract) {
        not_found();
    }
    if ((int) q_value("SELECT COUNT(*) FROM contract_settlements WHERE contract_id = ? AND status = 'done'", array((int) $contract['id']))) {
        flash('정산 완료한 달이 있는 청소는 지울 수 없어요. 대신 ‘끝난 월’을 정해 주세요.', 'error');
        redirect('/admin/contracts/' . (int) $contract['id'] . '/edit');
    }
    q('DELETE FROM contract_settlements WHERE contract_id = ?', array((int) $contract['id']));
    q('DELETE FROM contracts WHERE id = ?', array((int) $contract['id']));
    flash('‘' . $contract['name'] . '’ 청소를 지웠어요.');
    redirect('/admin/contracts/list');
}
