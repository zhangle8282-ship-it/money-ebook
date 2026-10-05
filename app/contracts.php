<?php
/**
 * 청소 도급 정산(그린청소 관리자): 청소마다 월 청소비용·도급비율·갑을 비율을 정하고, 매달 정산합니다.
 *
 * 계산 순서(세금 먼저 → 도급 → 갑·을):
 *   세금(세금계산서 발행 시) = 청소비용 × 10%
 *   세금 뺀 금액            = 청소비용 − 세금
 *   도급비용                = 세금 뺀 금액 × 도급비율(10·20·30%)
 *   나눌 금액               = 세금 뺀 금액 − 도급비용
 *   갑 = 나눌 금액 × 갑 비율(기본 60%),  을 = 나눌 금액 − 갑
 */

const CONTRACT_TAX_RATE = 10;
const CONTRACT_RATES = array(10, 20, 30);
const CONTRACT_GAP_DEFAULT = 60;
const SETTLEMENT_STATUS = array('preview' => '정산 전', 'pending' => '정산 전', 'done' => '정산 완료');

// 갑·을이 하는 일(처음 목록). 관리자 › 도급 정산 › 갑 · 을 역할에서 더하고 뺄 수 있어요.
const CONTRACT_ROLE_DEFAULTS = array(
    'gap' => array('세금계산서 발행', '전화상담', '방문견적', '계약서 체결'),
    'eul' => array('홈페이지 관리', '홍보', '채널톡상담', '인원배치'),
);
const CONTRACT_ROLE_SIDES = array('gap' => '갑', 'eul' => '을');
const CONTRACT_ROLE_MAX = 20;

/** 갑·을이 하는 일 목록 */
function contract_roles()
{
    $saved = json_decode(gc('roles'), true);
    $roles = array();
    foreach (CONTRACT_ROLE_SIDES as $side => $label) {
        $list = is_array($saved) && isset($saved[$side]) && is_array($saved[$side]) ? $saved[$side] : CONTRACT_ROLE_DEFAULTS[$side];
        $roles[$side] = array_values(array_filter(array_map('strval', $list), 'strlen'));
    }
    return $roles;
}

function save_contract_roles($roles)
{
    save_settings(array('gc_roles' => json_encode(array('gap' => array_values($roles['gap']), 'eul' => array_values($roles['eul'])), JSON_UNESCAPED_UNICODE)));
}

/** 금액 계산. 반환: fee, tax, after_tax, contract_amount, base, gap_amount, eul_amount (+ 비율) */
function contract_calc($fee, $invoice, $contractRate, $gapRate)
{
    $fee = max(0, (int) $fee);
    $tax = $invoice ? (int) round($fee * CONTRACT_TAX_RATE / 100) : 0;
    $afterTax = $fee - $tax;
    $contractAmount = (int) round($afterTax * (int) $contractRate / 100);
    $base = $afterTax - $contractAmount;
    $gap = (int) round($base * (int) $gapRate / 100);
    return array(
        'fee' => $fee, 'invoice' => $invoice ? 1 : 0, 'tax' => $tax, 'after_tax' => $afterTax,
        'contract_rate' => (int) $contractRate, 'contract_amount' => $contractAmount, 'base' => $base,
        'gap_rate' => (int) $gapRate, 'gap_amount' => $gap, 'eul_amount' => $base - $gap,
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
            $calc = contract_calc($s['fee'], (int) $s['invoice'], $s['contract_rate'], $s['gap_rate']);
            $calc['status'] = $s['status'];
            $calc['memo'] = (string) $s['memo'];
            $calc['settled_at'] = $s['settled_at'];
            $calc['saved'] = true;
        } else {
            $calc = contract_calc($c['monthly_fee'], (int) $c['invoice'], $c['contract_rate'], $c['gap_rate']);
            $calc['status'] = 'preview';
            $calc['memo'] = '';
            $calc['settled_at'] = null;
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
    $t = array('count' => count($rows), 'done' => 0, 'fee' => 0, 'tax' => 0, 'contract_amount' => 0, 'base' => 0, 'gap_amount' => 0, 'eul_amount' => 0);
    foreach ($rows as $r) {
        foreach (array('fee', 'tax', 'contract_amount', 'base', 'gap_amount', 'eul_amount') as $k) {
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
        'contract_rate' => (int) $contract['contract_rate'], 'gap_rate' => (int) $contract['gap_rate'],
        'status' => 'pending', 'memo' => '', 'settled_at' => null,
    );
    $v = array_merge(array_intersect_key($base, array_flip(array('fee', 'invoice', 'contract_rate', 'gap_rate', 'status', 'memo', 'settled_at'))), $changes);
    $calc = contract_calc($v['fee'], (int) $v['invoice'], $v['contract_rate'], $v['gap_rate']);
    $data = array(
        'fee' => $calc['fee'], 'invoice' => $calc['invoice'], 'tax' => $calc['tax'],
        'contract_rate' => $calc['contract_rate'], 'contract_amount' => $calc['contract_amount'],
        'gap_rate' => $calc['gap_rate'], 'gap_amount' => $calc['gap_amount'], 'eul_amount' => $calc['eul_amount'],
        'status' => $v['status'], 'memo' => (string) $v['memo'], 'settled_at' => $v['settled_at'], 'updated_at' => now(),
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
