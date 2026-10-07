<?php
/**
 * 그린청소 관리자 › 인력 배치: 목록 · 찾기, 새 사람 추가 · 고치기 · 지우기.
 */

function admin_workers()
{
    require_admin();
    $q = str_cut(input('q'), 60, '');
    $method = array_key_exists(input('method'), WORKER_METHODS) ? input('method') : '';
    $region = in_array(input('region'), worker_region_options(), true) ? input('region') : '';
    $team = array_key_exists(input('team'), WORKER_TEAMS) ? input('team') : '';
    render_admin('workers', array(
        'title' => '인력 배치', 'nav' => 'workers',
        'rows' => workers_search($q, $method, $region, $team),
        'q' => $q, 'method' => $method, 'region' => $region, 'team' => $team,
        'counts' => worker_counts(),
        'assigned' => worker_assignments(),
        'openContracts' => contracts_open(),
        'total' => (int) q_value('SELECT COUNT(*) FROM workers'),
    ));
}

function admin_worker_form($id = null)
{
    require_admin();
    $worker = $id !== null ? find_worker($id) : null;
    if ($id !== null && !$worker) {
        not_found();
    }
    $form = $worker ?: array('name' => '', 'phone' => '', 'regions' => '', 'method' => '', 'memo' => '', 'team' => '', 'team_note' => '');
    $other = '';
    $errors = array();
    $here = $worker ? '/admin/workers/' . (int) $worker['id'] . '/edit' : '/admin/workers/new';
    if (is_post()) {
        require_csrf($here);
        $picked = isset($_POST['regions']) && is_array($_POST['regions']) ? $_POST['regions'] : array();
        $other = str_cut(trim(input('regions_other')), 300, '');
        $form = array_merge($form, array(
            'name' => str_cut(trim(preg_replace('/\s+/u', ' ', input('name'))), 60, ''),
            'phone' => str_cut(trim(input('phone')), 40, ''),
            'regions' => worker_regions_text($picked, $other),
            // 둘 다 고를 수 있음(methods[]). 예전 방식(method 하나)도 받습니다.
            'method' => worker_method_value(isset($_POST['methods']) && is_array($_POST['methods']) ? $_POST['methods'] : array(input('method'))),
            'memo' => str_cut(str_replace("\r\n", "\n", trim((string) input('memo'))), 1000, ''),
            'team' => array_key_exists(input('team'), WORKER_TEAMS) ? input('team') : '',
            'team_note' => input('team') === 'other' ? str_cut(trim(preg_replace('/\s+/u', ' ', input('team_note'))), 30, '') : '',
        ));
        if ($form['name'] === '') {
            $errors[] = '이름을 적어 주세요.';
        }
        if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s().]{9,20}$/', $form['phone'])) {
            $errors[] = '연락처는 숫자로 적어 주세요(예: 010-1234-5678).';
        }
        if ($form['regions'] === '') {
            $errors[] = '커버 가능한 지역을 하나 이상 골라 주세요.';
        }
        if ($form['team'] === 'other' && $form['team_note'] === '') {
            $errors[] = '구성을 ‘기타’로 골랐다면 어떤 관계인지 적어 주세요(예: 자매, 이웃, 부자).';
        }
        if ($form['method'] === '') {
            $errors[] = '원하는 방식(수수료 방식 / 인수해서 직접)을 하나 이상 골라 주세요. 둘 다 골라도 돼요.';
        }
        if (!$errors) {
            $data = array_intersect_key($form, array_flip(array('name', 'phone', 'regions', 'method', 'memo', 'team', 'team_note')));
            $data['updated_at'] = now();
            if ($worker) {
                q_update('workers', (int) $worker['id'], $data);
                // 정기청소 정산의 청소 담당 파트너로 이어져 있으면 이름 · 연락처도 같이 바꿈
                q('UPDATE partners SET name = ?, phone = ?, updated_at = ? WHERE worker_id = ?', array($form['name'], $form['phone'], now(), (int) $worker['id']));
                flash('‘' . $form['name'] . '’ 정보를 저장했어요.');
            } else {
                $data['created_at'] = now();
                q_insert('workers', $data);
                flash('‘' . $form['name'] . '’ 님을 추가했어요.');
            }
            redirect('/admin/workers');
        }
    }
    // 고른 지역과 ‘기타 지역’ 칸으로 나눠 보여 주기
    $options = worker_region_options();
    $saved = worker_regions($form['regions']);
    if (!is_post()) {
        $other = implode(', ', array_diff($saved, $options));
    }
    render_admin('worker_form', array(
        'title' => $worker ? '인력 정보 고치기' : '새 사람 추가', 'nav' => 'workers',
        'worker' => $worker, 'form' => $form, 'errors' => $errors,
        'picked' => array_intersect($saved, $options), 'other' => $other,
    ));
}

function admin_worker_delete($id)
{
    require_admin();
    require_csrf('/admin/workers');
    $worker = find_worker($id);
    if (!$worker) {
        not_found();
    }
    // 끝나지 않은 정기청소의 청소 담당에서도 빼고, 정산 기록이 없으면 청소 담당 파트너 정보도 지움
    $freed = worker_release($worker);
    q('DELETE FROM workers WHERE id = ?', array((int) $worker['id']));
    flash('‘' . $worker['name'] . '’ 님 정보를 지웠어요.'
        . ($freed ? ' 맡고 있던 정기청소(' . implode(', ', $freed) . ')의 청소 담당도 비웠어요. 새 청소 담당을 배치해 주세요.' : ''));
    redirect('/admin/workers');
}

/** 인력 배치 목록에서 바로 청소에 배치 */
function admin_worker_assign($id)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/workers');
    require_csrf($back);
    $worker = find_worker($id);
    if (!$worker) {
        not_found();
    }
    $contract = find_contract(input_int('contract_id'));
    if (!$contract) {
        flash('배치할 청소를 골라 주세요.', 'error');
        redirect($back);
    }
    list($ok, $message) = assign_cleaner($contract, 'w:' . (int) $worker['id']);
    flash($message, $ok ? 'ok' : 'error');
    redirect($back);
}
