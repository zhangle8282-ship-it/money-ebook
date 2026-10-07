<?php
/**
 * 일회성 정산(그린청소 관리자): 입주청소 · 대청소처럼 한 번 하는 일의 수익을 대표파트너 · 운영파트너 · 청소 담당이 나눕니다.
 * 계산은 정기청소 정산과 같고(contract_calc: 세금 10% → 도급 비율 → 대표:운영, 청소 담당 원천징수 3.3%), 달마다가 아니라 일마다 한 번 정산합니다.
 * 나눈 금액은 저장할 때 계산해 두어, 정산 완료 뒤에는 그대로 남습니다.
 */

const ONETIME_FILTERS = array('month' => '월별', 'pending' => '정산 전 모두');
// 일하는 방식
//  - 수수료 방식: 청소 담당에게 청소비용에서 수수료 10 · 20%를 빼고 줌(예: 50만원 · 20% → 청소 담당 40만원). 뺀 수수료는 대표 · 운영이 5:5
//    원천징수 3.3%는 기본으로 떼지 않음(필요하면 고를 수 있음)
//  - 인수 방식: 청소 담당 몫 없이 청소 금액 전체를 대표 · 운영이 나눔
const ONETIME_METHODS = array('commission' => '수수료 방식', 'takeover' => '인수 방식');
const ONETIME_RATES = array(10, 20);
// 일회성 정산은 회사 몫(수수료 · 인수 금액)을 대표파트너 · 운영파트너가 이 비율로 나눕니다(대표 50 : 운영 50).
const ONETIME_GAP = 50;

/** 일 하나의 금액 계산(방식에 따라) */
function onetime_calc($job)
{
    $gap = (int) $job['gap_rate'];
    $wh = (int) $job['withholding'];
    if (($job['method'] ?? 'commission') === 'takeover') {
        // 인수 방식: 세금 뺀 청소 금액 전체가 대표 · 운영 몫(청소 담당 몫 · 원천징수 없음)
        return contract_calc($job['fee'], (int) $job['invoice'], 100, $gap, 0);
    }
    return contract_calc($job['fee'], (int) $job['invoice'], (int) $job['contract_rate'], $gap, $wh);
}

/** 방식 한 줄: 수수료 20% / 인수 방식 · 대표 · 운영 50:50 */
function onetime_method_label($job)
{
    if (($job['method'] ?? 'commission') === 'takeover') {
        return '인수 방식 · 대표 · 운영 ' . ONETIME_GAP . ':' . (100 - ONETIME_GAP);
    }
    return '수수료 ' . (int) $job['contract_rate'] . '%';
}

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
    $c = onetime_calc($job);
    return array(
        'tax' => $c['tax'], 'contract_amount' => $c['contract_amount'], 'byeong_amount' => $c['byeong_amount'],
        'withholding_amount' => $c['withholding_amount'], 'byeong_pay' => $c['byeong_pay'], 'gap_amount' => $c['gap_amount'], 'eul_amount' => $c['eul_amount'],
    );
}

/** 화면에 쓸 한 줄: 저장된 일 + 계산 세부(소득세 · 지방세 · 비율) */
function onetime_row($job)
{
    $c = onetime_calc($job);
    return array_merge($c, $job, array('rate_now' => $c['contract_rate'], 'byeong_rate' => $c['byeong_rate'], 'income_tax' => $c['income_tax'], 'local_tax' => $c['local_tax']));
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
