<?php
/**
 * 그린청소 관리자 › 일회성 정산: 목록(작업 월 · 정산 전 모두) · 일 추가/고치기 · 정산 단계 · 지우기.
 */

function admin_onetime_list()
{
    require_admin();
    $pendingOnly = input('status') === 'pending';
    $month = valid_month(input('month')) ? input('month') : date('Y-m');
    $rows = onetime_rows($month, $pendingOnly);
    render_admin('onetime_list', array(
        'title' => '일회성 정산', 'nav' => 'onetime',
        'month' => $month, 'pendingOnly' => $pendingOnly, 'rows' => $rows, 'sum' => settlement_sum($rows),
        'pendingCount' => onetime_pending_count(),
        'total' => (int) q_value('SELECT COUNT(*) FROM onetime_jobs'),
        'roles' => contract_roles('onetime'),
    ));
}

/** 청소 목록: 등록한 일회성 청소 전부(찾기 · 상태로 거르기, 바로 지우기) */
function admin_onetime_jobs()
{
    require_admin();
    $q = str_cut(trim(input('q')), 40, '');
    $status = in_array(input('status'), array('pending', 'done'), true) ? input('status') : '';
    $where = array();
    $params = array();
    if ($q !== '') {
        $like = worker_like($q);
        $where[] = "(j.name LIKE ? ESCAPE '!' OR j.client LIKE ? ESCAPE '!' OR j.byeong_name LIKE ? ESCAPE '!' OR p.name LIKE ? ESCAPE '!')";
        array_push($params, $like, $like, $like, $like);
    }
    if ($status !== '') {
        $where[] = $status === 'done' ? "j.status = 'done'" : "j.status <> 'done'";
    }
    $rows = q_all('SELECT j.* FROM onetime_jobs j LEFT JOIN partners p ON p.id = j.byeong_partner_id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY j.work_date DESC, j.id DESC', $params);
    render_admin('onetime_jobs', array(
        'title' => '일회성 정산 · 청소 목록', 'nav' => 'onetime',
        'rows' => array_map('onetime_row', $rows), 'q' => $q, 'status' => $status,
        'total' => (int) q_value('SELECT COUNT(*) FROM onetime_jobs'),
    ));
}

function admin_onetime_form($id = null)
{
    require_admin();
    $job = $id !== null ? find_onetime($id) : null;
    if ($id !== null && !$job) {
        not_found();
    }
    $form = $job ?: array(
        // 일회성은 보통 세금계산서를 발행하지 않아요(원하면 ‘발행’을 고름).
        'name' => '', 'client' => '', 'work_date' => date('Y-m-d'), 'fee' => '', 'invoice' => 0, 'contract_rate' => 20,
        'method' => 'commission', 'gap_rate' => ONETIME_GAP,
        'withholding' => 0, 'gap_partner_id' => null, 'eul_partner_id' => null, 'byeong_partner_id' => null,
        'gap_name' => gc('name'), 'eul_name' => '', 'byeong_name' => '', 'memo' => '', 'status' => 'pending',
    );
    $locked = $job && $job['status'] === 'done';
    $errors = array();
    $here = $job ? '/admin/onetime/' . (int) $job['id'] . '/edit' : '/admin/onetime/new';
    if (is_post()) {
        require_csrf($here);
        if ($locked) {
            flash('정산 완료한 일은 고칠 수 없어요. 목록에서 ‘되돌리기’를 누른 뒤 고쳐 주세요.', 'error');
            redirect($here);
        }
        $form = array_merge($form, partner_picks_from_request(), array(
            'name' => str_cut(trim(preg_replace('/\s+/u', ' ', input('name'))), 100, ''),
            'client' => str_cut(trim(input('client')), 100, ''),
            'work_date' => input('work_date'),
            'fee' => input('fee') === '' ? '' : (int) preg_replace('/[^0-9]/', '', input('fee')),
            'invoice' => input('invoice') === '1' ? 1 : 0, // 일회성은 기본 발행 안 함
            'contract_rate' => input_int('contract_rate'),
            'method' => input('method') === 'takeover' ? 'takeover' : 'commission',
            'gap_rate' => input('gap_rate') === '' ? -1 : input_int('gap_rate', -1),
            'withholding' => input('withholding') === '1' ? 1 : 0, // 일회성은 기본 안 뗌
            'gap_name' => str_cut(trim(input('gap_name')), 60, ''),
            'eul_name' => str_cut(trim(input('eul_name')), 60, ''),
            'byeong_name' => str_cut(trim(input('byeong_name')), 60, ''),
            'memo' => str_cut(str_replace("\r\n", "\n", input('memo')), 1000, ''),
        ));
        if ($form['name'] === '') {
            $errors[] = '일 이름을 적어 주세요(예: 금왕 ○○상가 입주청소).';
        }
        if (!valid_day($form['work_date'])) {
            $errors[] = '작업일을 골라 주세요.';
        }
        if ($form['fee'] === '' || (int) $form['fee'] <= 0) {
            $errors[] = '청소비용을 적어 주세요.';
        }
        if ($form['method'] === 'commission' && !in_array($form['contract_rate'], ONETIME_RATES, true)) {
            $errors[] = '수수료를 ' . implode('% · ', ONETIME_RATES) . '% 중에서 골라 주세요.';
        }
        $form['gap_rate'] = ONETIME_GAP; // 일회성 정산은 대표 · 운영 50:50으로 정해져 있음
        if ($form['gap_rate'] < 0 || $form['gap_rate'] > 100) {
            $errors[] = '대표파트너 비율은 0~100% 사이로 정해 주세요.';
        }
        if (!$errors) {
            $note = '';
            if ($form['byeong_worker_id']) {
                list($form['byeong_partner_id'], $created) = partner_from_worker(find_worker($form['byeong_worker_id']));
                $note = $created ? ' 인력 배치의 사람을 청소 담당 파트너로 등록했어요. 지급 계좌는 정기청소 정산 › 파트너 · 계좌에서 넣어 주세요.' : '';
            }
            $data = array_intersect_key($form, array_flip(array('name', 'client', 'work_date', 'fee', 'invoice', 'contract_rate', 'gap_rate', 'withholding',
                'method',
                'gap_partner_id', 'eul_partner_id', 'byeong_partner_id', 'gap_name', 'eul_name', 'byeong_name', 'memo')));
            $data['fee'] = (int) $data['fee'];
            $data['gap_rate'] = ONETIME_GAP; // 일회성 정산: 대표 · 운영 50:50
            // 인수 방식: 청소 금액 전체가 대표 · 운영 몫(100%), 청소 담당 몫 · 원천징수 없음
            if ($data['method'] === 'takeover') {
                $data['contract_rate'] = 100;
                $data['withholding'] = 0;
                $data['byeong_partner_id'] = null;
                $data['step_paid_byeong'] = null;
            }
            $data += onetime_amounts($data);
            if (!$data['invoice']) {
                $data['step_invoiced'] = null;
            }
            $data['updated_at'] = now();
            if ($job) {
                q_update('onetime_jobs', (int) $job['id'], $data);
                // 세금계산서를 빼서 남은 단계가 모두 끝났으면 정산 완료로 맞춤
                onetime_set_steps(find_onetime($job['id']), array(), current_admin()['username'] ?? '');
                flash('‘' . $form['name'] . '’을(를) 저장했어요.' . $note);
            } else {
                $data['created_at'] = now();
                $data['status'] = 'pending';
                q_insert('onetime_jobs', $data);
                flash('‘' . $form['name'] . '’ 일회성 정산을 추가했어요. 아래에서 정산 단계를 체크하면 돼요.' . $note);
            }
            redirect('/admin/onetime?month=' . substr($form['work_date'], 0, 7));
        }
    }
    render_admin('onetime_form', array(
        'title' => $job ? '일회성 정산 고치기' : '새 일회성 정산', 'nav' => 'onetime',
        'job' => $job, 'form' => $form, 'errors' => $errors, 'locked' => $locked,
    ));
}

/** 정산 단계 체크 · 한 번에 완료 · 되돌리기 */
function admin_onetime_settle($id)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/onetime');
    require_csrf($back);
    $job = find_onetime($id);
    if (!$job) {
        not_found();
    }
    $by = current_admin()['username'] ?? '';
    $action = input('action');
    $steps = settlement_steps(onetime_row($job));
    if ($action === 'step' && array_key_exists(input('step'), $steps)) {
        $step = input('step');
        $on = input('on') === '1';
        $done = onetime_set_steps($job, array('step_' . $step => $on ? now() : null), $by);
        flash($job['name'] . ' · ' . $steps[$step] . ($on ? ' 체크했어요.' : ' 체크를 풀었어요.') . ($done ? ' 모든 단계가 끝나 정산 완료로 기록했어요.' : ''));
    } elseif ($action === 'done') {
        $changes = array();
        foreach (array_keys($steps) as $k) {
            if (empty($job['step_' . $k])) {
                $changes['step_' . $k] = now();
            }
        }
        onetime_set_steps($job, $changes, $by);
        flash('‘' . $job['name'] . '’ 정산을 완료했어요.');
    } elseif ($action === 'undo') {
        q_update('onetime_jobs', (int) $job['id'], array('status' => 'pending', 'settled_at' => null, 'settled_by' => '',
            'step_received' => null, 'step_invoiced' => null, 'step_paid_byeong' => null, 'step_paid_eul' => null, 'updated_at' => now()));
        flash('‘' . $job['name'] . '’을(를) 정산 전으로 되돌렸어요.');
    }
    redirect($back);
}

/** 일 지우기(청소 목록 · 고치기 화면). 정산 완료한 일은 청소 목록의 확인 창에서 알린 뒤에만(with_done=1) 지움 */
function admin_onetime_delete($id)
{
    require_admin();
    $back = safe_back(input('back'), '');
    require_csrf($back !== '' ? $back : '/admin/onetime');
    $job = find_onetime($id);
    if (!$job) {
        not_found();
    }
    if ($job['status'] === 'done' && input('with_done') !== '1') {
        flash('정산 완료한 일은 지울 수 없어요. 먼저 ‘되돌리기’를 눌러 주세요.', 'error');
        redirect('/admin/onetime/' . (int) $job['id'] . '/edit');
    }
    q('DELETE FROM onetime_jobs WHERE id = ?', array((int) $job['id']));
    flash('‘' . $job['name'] . '’을(를) 지웠어요.' . ($job['status'] === 'done' ? ' 정산 완료 기록도 함께 지웠어요.' : ''));
    redirect($back !== '' && strpos($back, '/admin/onetime/' . (int) $job['id'] . '/') !== 0 ? $back : '/admin/onetime?month=' . substr($job['work_date'], 0, 7));
}

/** 일회성 정산 › 파트너 역할(정기청소 정산과 따로) */
function admin_onetime_roles()
{
    admin_contract_roles('onetime');
}
