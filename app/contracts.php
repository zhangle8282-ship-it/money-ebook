<?php
/**
 * 청소 정기청소 정산(그린청소 관리자): 대표파트너 · 운영파트너 · 청소 담당 파트너가 청소비용을 나눕니다. 매달 정산합니다.
 *
 * 계산(예: 청소비용 100만원, 도급 20%, 대표·운영 60:40):
 *   세금(세금계산서 발행 시) = 청소비용 × 10%                     → 100,000
 *   세금 뺀 금액            = 청소비용 − 세금                       → 900,000
 *   청소 담당 파트너 몫     = 세금 뺀 금액 × (100 − 도급비율)%      → 720,000 (80%)
 *     원천징수 3.3%        = 청소 담당 몫 × 3.3% (줄 때 떼어 세무서에 냄) → 23,760
 *     청소 담당 실지급     = 청소 담당 몫 − 원천징수               → 696,240
 *   도급 몫(대표·운영 수익) = 세금 뺀 금액 × 도급비율(10·20·30%)   → 180,000 (20%)
 *     대표파트너 = 도급 몫 × 대표 비율(기본 60%)                   → 108,000
 *     운영파트너 = 도급 몫 − 대표파트너                            →  72,000
 */

const CONTRACT_TAX_RATE = 10;
const CONTRACT_WITHHOLDING = 3.3; // 청소 담당 파트너에게 줄 때 떼는 원천징수(소득세 3% + 지방소득세 0.3%)
const CONTRACT_RATES = array(10, 20, 30);
const CONTRACT_GAP_DEFAULT = 60;
const SETTLEMENT_STATUS = array('preview' => '정산 전', 'pending' => '정산 전', 'done' => '정산 완료');
// 대표파트너가 달마다 하는 정산 단계(모두 끝나면 정산 완료). 세금계산서는 발행하는 청소만.
const SETTLEMENT_STEPS = array(
    'received' => '청소비용 입금 확인',
    'invoiced' => '세금계산서 발행',
    'paid_byeong' => '청소 담당 파트너 지급',
    'paid_eul' => '운영파트너 지급',
);

/** 이 정산에 필요한 단계 */
function settlement_steps($calc)
{
    $steps = SETTLEMENT_STEPS;
    if (!$calc['invoice']) {
        unset($steps['invoiced']);
    }
    return $steps;
}

// 파트너가 하는 일(처음 목록). 관리자 › 정기청소 정산 › 파트너 역할에서 더하고 뺄 수 있어요.
const CONTRACT_ROLE_DEFAULTS = array(
    'gap' => array('세금계산서 발행', '전화상담', '방문견적', '계약서 체결'),
    'eul' => array('홈페이지 관리', '홍보', '채널톡상담', '인원배치'),
    'byeong' => array('현장 청소 작업'),
);
// 일회성 정산(입주청소 등)의 처음 역할. 관리자 › 일회성 정산 › 파트너 역할에서 따로 고칩니다.
const ONETIME_ROLE_DEFAULTS = array(
    'gap' => array('전화상담', '인력배치'),
    'eul' => array('홈페이지 관리', '홍보', '채널톡상담'),
    'byeong' => array('현장 청소 업무'),
);
const CONTRACT_ROLE_SIDES = array('gap' => '대표파트너', 'eul' => '운영파트너', 'byeong' => '청소 담당 파트너');
const CONTRACT_ROLE_SHORT = array('gap' => '대표', 'eul' => '운영', 'byeong' => '청소');
const CONTRACT_ROLE_MAX = 20;

/* ───────── 파트너(사람)와 지급 계좌 ───────── */

const PARTNER_BANKS = array('국민은행', '신한은행', '우리은행', '하나은행', 'NH농협은행', '지역농협', 'IBK기업은행', '카카오뱅크', '토스뱅크', '케이뱅크', '새마을금고', '우체국', '신협', '수협', 'SC제일은행', '대구은행', '부산은행', '경남은행', '광주은행', '전북은행', '제주은행');

function partners_all()
{
    static $all = null;
    if ($all === null) {
        $all = array();
        foreach (q_all('SELECT * FROM partners ORDER BY name, id') as $p) {
            $all[(int) $p['id']] = $p;
        }
    }
    return $all;
}

/** 역할별 파트너: gap|eul|byeong => [파트너…] */
function partners_by_role()
{
    $out = array_fill_keys(array_keys(CONTRACT_ROLE_SIDES), array());
    foreach (partners_all() as $p) {
        if (isset($out[$p['role']])) {
            $out[$p['role']][] = $p;
        }
    }
    return $out;
}

/** 청소에 정한 그 역할의 파트너(없으면 null) */
function contract_partner($contract, $role)
{
    $id = (int) ($contract[$role . '_partner_id'] ?? 0);
    $all = partners_all();
    return $id && isset($all[$id]) ? $all[$id] : null;
}

/** 표시할 이름: 고른 파트너, 없으면 직접 적은 이름 */
function contract_partner_name($contract, $role)
{
    $p = contract_partner($contract, $role);
    return $p ? $p['name'] : (string) ($contract[$role . '_name'] ?? '');
}

/** 계좌 한 줄: 은행 계좌번호 (예금주) */
function partner_account($p)
{
    if (!$p || $p['bank_account'] === '') {
        return '';
    }
    return trim($p['bank_name'] . ' ' . $p['bank_account']) . ($p['bank_holder'] !== '' ? ' (' . $p['bank_holder'] . ')' : '');
}

/** 파트너가 하는 일 목록 */
/** 파트너가 하는 일. $scope: contract(정기청소 정산) | onetime(일회성 정산) — 따로 저장 */
function contract_roles($scope = 'contract')
{
    $saved = json_decode(gc($scope === 'onetime' ? 'onetime_roles' : 'roles'), true);
    $defaults = $scope === 'onetime' ? ONETIME_ROLE_DEFAULTS : CONTRACT_ROLE_DEFAULTS;
    $roles = array();
    foreach (CONTRACT_ROLE_SIDES as $side => $label) {
        $list = is_array($saved) && isset($saved[$side]) && is_array($saved[$side]) ? $saved[$side] : $defaults[$side];
        $roles[$side] = array_values(array_filter(array_map('strval', $list), 'strlen'));
    }
    return $roles;
}

function save_contract_roles($roles, $scope = 'contract')
{
    $out = array();
    foreach (array_keys(CONTRACT_ROLE_SIDES) as $side) {
        $out[$side] = array_values($roles[$side]);
    }
    save_settings(array(($scope === 'onetime' ? 'gc_onetime_roles' : 'gc_roles') => json_encode($out, JSON_UNESCAPED_UNICODE)));
}

/** 금액 계산. 반환: fee, tax, after_tax, byeong_amount, withholding_amount, byeong_pay, contract_amount, gap_amount, eul_amount (+ 비율) */
function contract_calc($fee, $invoice, $contractRate, $gapRate, $withholding = 1)
{
    $fee = max(0, (int) $fee);
    $tax = $invoice ? (int) round($fee * CONTRACT_TAX_RATE / 100) : 0;
    $afterTax = $fee - $tax;
    $contractAmount = (int) round($afterTax * (int) $contractRate / 100);
    $byeong = $afterTax - $contractAmount;
    // 원천징수 3.3% = 소득세 3% + 지방소득세(소득세의 10%), 각각 10원 아래는 버림
    $incomeTax = $withholding ? (int) (floor($byeong * 3 / 100 / 10) * 10) : 0;
    $localTax = $withholding ? (int) (floor($incomeTax / 10 / 10) * 10) : 0;
    $withheld = $incomeTax + $localTax;
    $gap = (int) round($contractAmount * (int) $gapRate / 100);
    return array(
        'fee' => $fee, 'invoice' => $invoice ? 1 : 0, 'tax' => $tax, 'after_tax' => $afterTax,
        'contract_rate' => (int) $contractRate, 'byeong_rate' => 100 - (int) $contractRate,
        'byeong_amount' => $byeong, 'withholding' => $withholding ? 1 : 0, 'withholding_amount' => $withheld, 'byeong_pay' => $byeong - $withheld,
        'income_tax' => $incomeTax, 'local_tax' => $localTax,
        'contract_amount' => $contractAmount, 'gap_rate' => (int) $gapRate, 'gap_amount' => $gap, 'eul_amount' => $contractAmount - $gap,
    );
}

function valid_month($m)
{
    return is_string($m) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m);
}

function month_label($m)
{
    return (int) substr($m, 0, 4) . '년 ' . (int) substr($m, 5, 2) . '월';
}

function month_shift($m, $n)
{
    return date('Y-m', strtotime($m . '-01 ' . ($n >= 0 ? '+' : '') . $n . ' month'));
}

function find_contract($id)
{
    return q_one('SELECT * FROM contracts WHERE id = ?', array((int) $id));
}

/** 그 달에 진행 중인 청소(시작 월 ≤ 달 ≤ 끝난 월) */
function contracts_in_month($month)
{
    return q_all("SELECT * FROM contracts WHERE start_month <= ? AND (end_month IS NULL OR end_month = '' OR end_month >= ?) ORDER BY name, id", array($month, $month));
}

/**
 * 한 달 정산표: 진행 중인 청소마다 그 달 정산(저장된 것이 있으면 그 금액, 없으면 지금 계약 조건으로 계산한 예정 금액).
 * 이미 정산한 청소가 나중에 끝나도(끝난 월을 당겨도) 저장된 정산은 함께 보여 줍니다.
 */
function month_settlements($month)
{
    $rows = array();
    $saved = array();
    foreach (q_all('SELECT * FROM contract_settlements WHERE month = ?', array($month)) as $s) {
        $saved[(int) $s['contract_id']] = $s;
    }
    $contracts = array();
    foreach (contracts_in_month($month) as $c) {
        $contracts[(int) $c['id']] = $c;
    }
    foreach (array_keys($saved) as $cid) {
        if (!isset($contracts[$cid]) && ($c = find_contract($cid))) {
            $contracts[$cid] = $c;
        }
    }
    foreach ($contracts as $cid => $c) {
        if (isset($saved[$cid])) {
            $s = $saved[$cid];
            $calc = contract_calc($s['fee'], (int) $s['invoice'], $s['contract_rate'], $s['gap_rate'], (int) $s['withholding']);
            $calc['status'] = $s['status'];
            $calc['memo'] = (string) $s['memo'];
            $calc['settled_at'] = $s['settled_at'];
            $calc['settled_by'] = (string) $s['settled_by'];
            foreach (array_keys(SETTLEMENT_STEPS) as $k) {
                $calc['step_' . $k] = $s['step_' . $k];
            }
            $calc['saved'] = true;
        } else {
            $calc = contract_calc($c['monthly_fee'], (int) $c['invoice'], $c['contract_rate'], $c['gap_rate'], (int) $c['withholding']);
            $calc['status'] = 'preview';
            $calc['memo'] = '';
            $calc['settled_at'] = null;
            $calc['settled_by'] = '';
            foreach (array_keys(SETTLEMENT_STEPS) as $k) {
                $calc['step_' . $k] = null;
            }
            $calc['saved'] = false;
        }
        $calc['contract'] = $c;
        $rows[] = $calc;
    }
    usort($rows, function ($a, $b) {
        return strcmp($a['contract']['name'], $b['contract']['name']);
    });
    return $rows;
}

function settlement_sum($rows)
{
    $t = array('count' => count($rows), 'done' => 0, 'fee' => 0, 'tax' => 0, 'byeong_amount' => 0, 'withholding_amount' => 0, 'byeong_pay' => 0, 'contract_amount' => 0, 'gap_amount' => 0, 'eul_amount' => 0);
    foreach ($rows as $r) {
        foreach (array('fee', 'tax', 'byeong_amount', 'withholding_amount', 'byeong_pay', 'contract_amount', 'gap_amount', 'eul_amount') as $k) {
            $t[$k] += $r[$k];
        }
        if ($r['status'] === 'done') {
            $t['done']++;
        }
    }
    return $t;
}

/** 그 달 정산 저장(없으면 지금 계약 조건으로 만들고, $changes 를 덮어씀) */
function save_month_settlement($contract, $month, $changes)
{
    $row = q_one('SELECT * FROM contract_settlements WHERE contract_id = ? AND month = ?', array((int) $contract['id'], $month));
    $base = $row ?: array(
        'fee' => (int) $contract['monthly_fee'], 'invoice' => (int) $contract['invoice'],
        'contract_rate' => (int) $contract['contract_rate'], 'gap_rate' => (int) $contract['gap_rate'], 'withholding' => (int) $contract['withholding'],
        'status' => 'pending', 'memo' => '', 'settled_at' => null, 'settled_by' => '',
        'step_received' => null, 'step_invoiced' => null, 'step_paid_byeong' => null, 'step_paid_eul' => null,
    );
    $keys = array('fee', 'invoice', 'contract_rate', 'gap_rate', 'withholding', 'status', 'memo', 'settled_at', 'settled_by', 'step_received', 'step_invoiced', 'step_paid_byeong', 'step_paid_eul');
    $v = array_merge(array_intersect_key($base, array_flip($keys)), $changes);
    $calc = contract_calc($v['fee'], (int) $v['invoice'], $v['contract_rate'], $v['gap_rate'], (int) $v['withholding']);
    $data = array(
        'fee' => $calc['fee'], 'invoice' => $calc['invoice'], 'tax' => $calc['tax'],
        'contract_rate' => $calc['contract_rate'], 'contract_amount' => $calc['contract_amount'],
        'byeong_amount' => $calc['byeong_amount'], 'withholding' => $calc['withholding'], 'withholding_amount' => $calc['withholding_amount'], 'byeong_pay' => $calc['byeong_pay'],
        'gap_rate' => $calc['gap_rate'], 'gap_amount' => $calc['gap_amount'], 'eul_amount' => $calc['eul_amount'],
        'status' => $v['status'], 'memo' => (string) $v['memo'], 'settled_at' => $v['settled_at'], 'settled_by' => (string) $v['settled_by'],
        'step_received' => $v['step_received'], 'step_invoiced' => $v['step_invoiced'], 'step_paid_byeong' => $v['step_paid_byeong'], 'step_paid_eul' => $v['step_paid_eul'],
        'updated_at' => now(),
    );
    if ($row) {
        q_update('contract_settlements', (int) $row['id'], $data);
    } else {
        q_insert('contract_settlements', $data + array('contract_id' => (int) $contract['id'], 'month' => $month, 'created_at' => now()));
    }
}

/** 한 해 월별 합계(정산표 기준) */
function year_settlement_summary($year)
{
    $out = array();
    for ($m = 1; $m <= 12; $m++) {
        $month = sprintf('%04d-%02d', $year, $m);
        $out[$month] = settlement_sum(month_settlements($month));
    }
    return $out;
}

/** 엑셀에서 수식으로 읽히지 않게 막고 CSV 한 줄로(스토어 모드의 csv_line 과 같은 방식) */
function contract_csv_line($cells)
{
    $out = array();
    foreach ($cells as $c) {
        $c = (string) $c;
        if ($c !== '' && strpos('=+-@', $c[0]) !== false && !is_numeric($c)) {
            $c = "'" . $c;
        }
        $out[] = '"' . str_replace('"', '""', $c) . '"';
    }
    return implode(',', $out) . "\r\n";
}
