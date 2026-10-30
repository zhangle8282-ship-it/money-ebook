<?php
/**
 * 그린청소 관리자 › 인력 배치: 목록 · 찾기, 새 사람(청소 담당 · 대표 · 운영팀) 추가 · 고치기 · 지우기.
 */

function admin_workers()
{
    require_admin();
    $q = str_cut(input('q'), 60, '');
    $method = array_key_exists(input('method'), WORKER_METHODS) ? input('method') : '';
    $region = in_array(input('region'), worker_region_options(), true) ? input('region') : '';
    $team = array_key_exists(input('team'), WORKER_TEAMS) ? input('team') : '';
    $role = array_key_exists(input('role'), WORKER_ROLES) ? input('role') : '';
    render_admin('workers', array(
        'title' => '인력 배치', 'nav' => 'workers',
        'rows' => workers_search($q, $method, $region, $team, $role),
        'q' => $q, 'method' => $method, 'region' => $region, 'team' => $team, 'role' => $role,
        'counts' => worker_counts(),
        'roleCounts' => worker_role_counts(),
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
    $form = $worker ?: array('role' => array_key_exists(input('role'), WORKER_ROLES) ? input('role') : 'byeong', 'name' => '', 'phone' => '', 'regions' => '', 'method' => '', 'memo' => '', 'team' => '', 'team_note' => '');
    $other = '';
    $errors = array();
    $here = $worker ? '/admin/workers/' . (int) $worker['id'] . '/edit' : '/admin/workers/new';
    if (is_post()) {
        require_csrf($here);
        $picked = isset($_POST['regions']) && is_array($_POST['regions']) ? $_POST['regions'] : array();
        $other = str_cut(trim(input('regions_other')), 300, '');
        $role = array_key_exists(input('role'), WORKER_ROLES) ? input('role') : 'byeong';
        $cleaner = $role === 'byeong';
        $form = array_merge($form, array(
            'role' => $role,
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
        // 지역 · 방식 · 구성은 청소 담당만(대표 · 운영팀은 비움)
        if ($cleaner) {
            if ($form['regions'] === '') {
                $errors[] = '커버 가능한 지역을 하나 이상 골라 주세요.';
            }
            if ($form['team'] === 'other' && $form['team_note'] === '') {
                $errors[] = '구성을 ‘기타’로 골랐다면 어떤 관계인지 적어 주세요(예: 자매, 이웃, 부자).';
            }
            if ($form['method'] === '') {
                $errors[] = '원하는 방식(수수료 방식 / 인수해서 직접)을 하나 이상 골라 주세요. 둘 다 골라도 돼요.';
            }
        } else {
            $form = array_merge($form, array('regions' => '', 'method' => '', 'team' => '', 'team_note' => ''));
        }
        // 맡은 일이 있는 사람은 역할을 바꾸기 전에 다른 사람으로 바꿔 둬야 함
        $roleChanged = $worker && worker_role($worker) !== $role;
        if ($roleChanged && ($jobs = worker_open_jobs($worker))) {
            $errors[] = '‘' . $worker['name'] . '’ 님이 ' . WORKER_ROLES_AS[worker_role($worker)] . ' 맡고 있는 일이 있어요(' . implode(', ', array_slice($jobs, 0, 5)) . (count($jobs) > 5 ? ' 외 ' . (count($jobs) - 5) . '건' : '') . '). 먼저 다른 사람으로 바꾼 뒤 역할을 바꿔 주세요.';
        }
        if (!$errors) {
            $data = array_intersect_key($form, array_flip(array('role', 'name', 'phone', 'regions', 'method', 'memo', 'team', 'team_note')));
            $data['updated_at'] = now();
            if ($worker) {
                if ($roleChanged) {
                    worker_release($worker); // 예전 역할의 파트너 연결 정리
                }
                q_update('workers', (int) $worker['id'], $data);
                // 파트너로 이어져 있으면 이름 · 연락처도 같이 바꿈(연락처를 비워 두면 파트너 쪽 연락처는 그대로)
                q("UPDATE partners SET name = ?, phone = CASE WHEN ? = '' THEN phone ELSE ? END, updated_at = ? WHERE worker_id = ?", array($form['name'], $form['phone'], $form['phone'], now(), (int) $worker['id']));
                $savedId = (int) $worker['id'];
            } else {
                $data['created_at'] = now();
                $savedId = (int) q_insert('workers', $data);
            }
            // 대표 · 운영팀은 바로 파트너로(정산의 대표 · 운영 칸에서 고를 수 있게)
            $note = '';
            if (!$cleaner) {
                list(, $created) = partner_from_worker(find_worker($savedId), $role);
                $note = ' 정기청소 · 일회성 정산에서 ' . WORKER_ROLES_AS[$role] . ' 고를 수 있어요.' . ($created ? ' 지급 계좌는 정기청소 정산 › 파트너 · 계좌에서 넣어 주세요.' : '');
            }
            flash(($worker ? '‘' . $form['name'] . '’ 정보를 저장했어요.' : '‘' . $form['name'] . '’ 님을 ' . WORKER_ROLES_AS[$role] . ' 추가했어요.') . $note);
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
    // 끝나지 않은 정기청소의 그 역할 칸에서도 빼고, 정산 기록이 없으면 파트너 정보도 지움
    $label = WORKER_ROLES[worker_role($worker)];
    $freed = worker_release($worker, $freedOnce);
    q('DELETE FROM workers WHERE id = ?', array((int) $worker['id']));
    flash('‘' . $worker['name'] . '’ 님 정보를 지웠어요.'
        . ($freed ? ' 맡고 있던 정기청소(' . implode(', ', $freed) . ')의 ' . $label . '도 비웠어요.' : '')
        . ($freedOnce ? ' 정산 전인 일회성 일(' . implode(', ', $freedOnce) . ')의 ' . $label . '도 비웠어요.' : '')
        . ($freed || $freedOnce ? ' 다른 사람을 배치해 주세요.' : ''));
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
    $role = worker_role($worker);
    list($ok, $message) = $role === 'byeong' ? assign_cleaner($contract, 'w:' . (int) $worker['id']) : assign_partner_role($contract, $worker, $role);
    flash($message, $ok ? 'ok' : 'error');
    redirect($back);
}
