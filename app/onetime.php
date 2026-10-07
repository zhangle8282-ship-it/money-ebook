<?php
/**
 * 일회성 정산(그린청소 관리자): 입주청소 · 대청소처럼 한 번 하는 일의 수익을 대표파트너 · 운영파트너 · 청소 담당이 나눕니다.
 * 계산은 정기청소 정산과 같고(contract_calc: 세금 10% → 도급 비율 → 대표:운영, 청소 담당 원천징수 3.3%), 달마다가 아니라 일마다 한 번 정산합니다.
 * 나눈 금액은 저장할 때 계산해 두어, 정산 완료 뒤에는 그대로 남습니다.
 */

const ONETIME_FILTERS = array('month' => '월별', 'pending' => '정산 전 모두');

function find_onetime($id)
{
    return q_one('SELECT * FROM onetime_jobs WHERE id = ?', array((int) $id));
}

function valid_day($d)
{
    return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));
}

/** 저장할 나눈 금액(조건 → contract_calc) */
function onetime_amounts($job)
{
    $c = contract_calc($job['fee'], (int) $job['invoice'], $job['contract_rate'], $job['gap_rate'], (int) $job['withholding']);
    return array(
        'tax' => $c['tax'], 'contract_amount' => $c['contract_amount'], 'byeong_amount' => $c['byeong_amount'],
        'withholding_amount' => $c['withholding_amount'], 'byeong_pay' => $c['byeong_pay'], 'gap_amount' => $c['gap_amount'], 'eul_amount' => $c['eul_amount'],
    );
}

/** 화면에 쓸 한 줄: 저장된 일 + 계산 세부(소득세 · 지방세 · 비율) */
function onetime_row($job)
{
    $c = contract_calc($job['fee'], (int) $job['invoice'], $job['contract_rate'], $job['gap_rate'], (int) $job['withholding']);
    return array_merge($c, $job, array('byeong_rate' => 100 - (int) $job['contract_rate'], 'income_tax' => $c['income_tax'], 'local_tax' => $c['local_tax']));
}

/** 그 달(작업일 기준)의 일 / 정산 전인 일 전부 */
function onetime_rows($month, $pendingOnly = false)
{
    $rows = $pendingOnly
        ? q_all("SELECT * FROM onetime_jobs WHERE status <> 'done' ORDER BY work_date, id")
        : q_all('SELECT * FROM onetime_jobs WHERE work_date LIKE ? ORDER BY work_date, id', array($month . '-%'));
    return array_map('onetime_row', $rows);
}

function onetime_pending_count()
{
    return (int) q_value("SELECT COUNT(*) FROM onetime_jobs WHERE status <> 'done'");
}

/** 정산 단계 하나를 바꾸고, 필요한 단계가 모두 끝났으면 정산 완료로(되돌리면 정산 전으로) */
function onetime_set_steps($job, $changes, $by)
{
    $row = array_merge($job, $changes);
    $done = true;
    foreach (array_keys(settlement_steps($row)) as $k) {
        if (empty($row['step_' . $k])) {
            $done = false;
        }
    }
    $changes += $done
        ? array('status' => 'done', 'settled_at' => $job['status'] === 'done' ? $job['settled_at'] : now(), 'settled_by' => $job['status'] === 'done' ? $job['settled_by'] : $by)
        : array('status' => 'pending', 'settled_at' => null, 'settled_by' => '');
    q_update('onetime_jobs', (int) $job['id'], $changes + array('updated_at' => now()));
    return $done;
}

/**
 * 파트너 고르기 칸(정기청소 정산과 같은 모양) 읽기. 청소 담당은 ‘w:번호’(인력 배치 사람)도 받습니다.
 * 반환: [gap_partner_id, eul_partner_id, byeong_partner_id, byeong_worker_id] (역할이 맞지 않는 파트너는 null)
 */
function partner_picks_from_request()
{
    $all = partners_all();
    $out = array();
    foreach (array_keys(CONTRACT_ROLE_SIDES) as $role) {
        $pid = input_int($role . '_partner_id') ?: null;
        $out[$role . '_partner_id'] = $pid && isset($all[$pid]) && $all[$pid]['role'] === $role ? $pid : null;
    }
    $out['byeong_worker_id'] = preg_match('/^w:(\d+)$/', input('byeong_partner_id'), $m) && find_worker($m[1]) ? (int) $m[1] : null;
    return $out;
}
