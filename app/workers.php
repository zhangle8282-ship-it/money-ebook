<?php
/**
 * 그린청소 인력 배치 정보: 일할 사람의 이름 · 연락처 · 커버 가능한 지역 · 원하는 방식(수수료 / 인수)을 모아 두고 찾아봅니다.
 * 관리자 화면 전용입니다(검색 사이트에 나오지 않음).
 */

// 원하는 방식: 키 => [이름, 설명]. 둘 다 고르면 DB에는 'both'로 저장합니다.
const WORKER_METHODS = array(
    'commission' => array('수수료 방식', '일을 받아서 하고, 수수료를 떼고 받기'),
    'takeover' => array('인수해서 직접', '현장을 넘겨받아 본인이 직접 맡기'),
);
const WORKER_BOTH = 'both';

/** 저장된 방식 → 고른 방식 키 목록 */
function worker_methods($method)
{
    return $method === WORKER_BOTH ? array_keys(WORKER_METHODS) : (array_key_exists($method, WORKER_METHODS) ? array($method) : array());
}

/** 고른 방식 키 목록 → 저장할 값(하나면 그 키, 둘이면 both, 없으면 '') */
function worker_method_value($keys)
{
    $keys = array_values(array_intersect(array_keys(WORKER_METHODS), (array) $keys));
    return count($keys) === count(WORKER_METHODS) ? WORKER_BOTH : ($keys[0] ?? '');
}
// 고를 수 있는 지역(음성군 9개 읍 · 면, 진천군 7개 읍 · 면, 충북혁신도시, 인근 충주 · 괴산). 이 밖의 지역은 ‘기타 지역’ 칸에 적습니다.
const WORKER_REGIONS = array(
    '음성군' => array('음성읍', '금왕읍', '대소면', '삼성면', '맹동면', '원남면', '생극면', '감곡면', '소이면'),
    '진천군' => array('진천읍', '덕산읍', '이월면', '광혜원면', '문백면', '백곡면', '초평면'),
    '혁신도시' => array('충북혁신도시'),
    '인근 시 · 군' => array('충주', '괴산'),
);
const WORKER_REGIONS_MAX = 40;
// 구성(혼자인지, 누구와 함께 일하는지). 기타는 관계를 직접 적습니다.
const WORKER_TEAMS = array(
    'male' => '남자', 'female' => '여자', 'couple' => '부부', 'siblings' => '남매',
    'mother_daughter' => '모녀', 'mother_son' => '모자', 'friends' => '친구', 'other' => '기타',
);

/** 보여 줄 구성 이름(기타는 적은 관계, 안 적었으면 ‘기타’). 안 골랐으면 '' */
function worker_team_label($w)
{
    $team = (string) ($w['team'] ?? '');
    if ($team === 'other') {
        return trim((string) ($w['team_note'] ?? '')) !== '' ? '기타 · ' . $w['team_note'] : '기타';
    }
    return WORKER_TEAMS[$team] ?? '';
}

function worker_region_options()
{
    return array_merge(...array_values(WORKER_REGIONS));
}

/** 저장된 지역 글자 → 목록 */
function worker_regions($text)
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $text)), 'strlen'));
}

/** 고른 지역 + 기타 지역 글자 → 저장할 글자(겹치는 것 빼고, 고른 순서 → 기타 순서) */
function worker_regions_text($picked, $other)
{
    $options = worker_region_options();
    $list = array();
    foreach ((array) $picked as $r) {
        if (is_string($r) && in_array($r, $options, true)) {
            $list[$r] = true;
        }
    }
    foreach (preg_split('/[,，、\/\n]+/u', (string) $other) as $r) {
        $r = str_cut(trim(preg_replace('/\s+/u', ' ', $r)), 30, '');
        if ($r !== '') {
            $list[$r] = true;
        }
    }
    return implode(', ', array_slice(array_keys($list), 0, WORKER_REGIONS_MAX));
}

function find_worker($id)
{
    return q_one('SELECT * FROM workers WHERE id = ?', array((int) $id));
}

/**
 * 찾기: $q 는 띄어쓰기로 나눈 낱말이 모두 들어 있어야 함(이름 · 지역 · 메모, 숫자는 전화번호까지).
 * $method: commission | takeover | '' , $region: 지역 이름 | '', $team: 구성 키 | ''
 */
function workers_search($q, $method, $region, $team = '')
{
    $where = array();
    $params = array();
    foreach (preg_split('/\s+/u', trim((string) $q), -1, PREG_SPLIT_NO_EMPTY) as $word) {
        $like = worker_like($word);
        $cond = "name LIKE ? ESCAPE '!' OR regions LIKE ? ESCAPE '!' OR memo LIKE ? ESCAPE '!' OR team_note LIKE ? ESCAPE '!'";
        array_push($params, $like, $like, $like, $like);
        // ‘부부’ · ‘모녀’처럼 구성 이름으로도 찾기
        $teamKey = array_search($word, WORKER_TEAMS, true);
        if ($teamKey !== false) {
            $cond .= ' OR team = ?';
            $params[] = $teamKey;
        }
        $digits = preg_replace('/\D/', '', $word);
        if (strlen($digits) >= 3) {
            $cond .= " OR REPLACE(REPLACE(phone, '-', ''), ' ', '') LIKE ?";
            $params[] = '%' . $digits . '%';
        }
        $where[] = '(' . $cond . ')';
    }
    if (array_key_exists($method, WORKER_METHODS)) {
        // 둘 다 고른 사람은 어느 쪽으로 찾아도 나옵니다.
        $where[] = '(method = ? OR method = ?)';
        array_push($params, $method, WORKER_BOTH);
    }
    if ($region !== '') {
        $where[] = "regions LIKE ? ESCAPE '!'";
        $params[] = worker_like($region);
    }
    if (array_key_exists($team, WORKER_TEAMS)) {
        $where[] = 'team = ?';
        $params[] = $team;
    }
    return q_all('SELECT * FROM workers' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY name, id', $params);
}

/** LIKE 찾기 글자(%, _ 는 글자 그대로). 백슬래시 대신 ! 로 감싸서 SQLite · MySQL 모두 같게 */
function worker_like($word)
{
    return '%' . str_replace(array('!', '%', '_'), array('!!', '!%', '!_'), $word) . '%';
}

function worker_counts()
{
    $counts = array_fill_keys(array_keys(WORKER_METHODS), 0);
    foreach (q_all('SELECT method, COUNT(*) AS n FROM workers GROUP BY method') as $r) {
        foreach (worker_methods($r['method']) as $key) {
            $counts[$key] += (int) $r['n'];
        }
    }
    return $counts;
}

/* ───────── 정기청소 정산과 잇기: 인력 배치 사람을 청소 담당 파트너로 ───────── */

/** 청소 담당으로 고를 수 있는 인력 배치 사람(아직 청소 담당 파트너로 이어지지 않은 사람만. 이어진 사람은 파트너 목록에 나옴) */
function workers_for_pick()
{
    return q_all("SELECT * FROM workers WHERE id NOT IN (SELECT worker_id FROM partners WHERE role = 'byeong' AND worker_id IS NOT NULL) ORDER BY name, id");
}

/** 인력 배치 사람 → 청소 담당 파트너(이미 있으면 그 파트너, 없으면 이름 · 연락처로 새로 만듦). 반환: [파트너 id, 새로 만들었는지] */
function partner_from_worker($worker)
{
    $id = (int) q_value("SELECT id FROM partners WHERE role = 'byeong' AND worker_id = ?", array((int) $worker['id']));
    if ($id) {
        return array($id, false);
    }
    $id = q_insert('partners', array(
        'role' => 'byeong', 'name' => $worker['name'], 'phone' => $worker['phone'], 'bank_name' => '', 'bank_account' => '', 'bank_holder' => '',
        'memo' => '인력 배치에서 등록', 'worker_id' => (int) $worker['id'], 'created_at' => now(), 'updated_at' => now(),
    ));
    return array((int) $id, true);
}

/** 사람마다 지금 맡고 있는 청소(끝나지 않은 것): worker_id => [[id, 청소 이름]…] */
function worker_assignments()
{
    $out = array();
    $rows = q_all("SELECT p.worker_id, c.id, c.name FROM contracts c JOIN partners p ON p.id = c.byeong_partner_id
        WHERE p.worker_id IS NOT NULL AND (c.end_month IS NULL OR c.end_month = '' OR c.end_month >= ?) ORDER BY c.name, c.id", array(date('Y-m')));
    foreach ($rows as $r) {
        $out[(int) $r['worker_id']][] = array((int) $r['id'], $r['name']);
    }
    return $out;
}

/**
 * 청소에 청소 담당 배치(정기청소 정산 › 청소 목록, 인력 배치 목록에서 바로).
 * $pick: 청소 담당 파트너 번호 | 'w:인력 배치 번호'(처음이면 청소 담당 파트너로 등록) | ''(비우기). 반환: [성공했는지, 안내 글]
 */
function assign_cleaner($contract, $pick)
{
    $pick = trim((string) $pick);
    if ($pick === '') {
        q_update('contracts', (int) $contract['id'], array('byeong_partner_id' => null, 'updated_at' => now()));
        return array(true, '‘' . $contract['name'] . '’의 청소 담당을 비웠어요.');
    }
    $created = false;
    if (preg_match('/^w:(\d+)$/', $pick, $m)) {
        $worker = find_worker($m[1]);
        if (!$worker) {
            return array(false, '인력 배치에서 그 사람을 찾지 못했어요. 새로고침해 주세요.');
        }
        list($pid, $created) = partner_from_worker($worker);
    } else {
        $pid = ctype_digit($pick) ? (int) $pick : 0;
        $partner = $pid ? q_one('SELECT * FROM partners WHERE id = ?', array($pid)) : null;
        if (!$partner || $partner['role'] !== 'byeong') {
            return array(false, '청소 담당 파트너를 다시 골라 주세요.');
        }
    }
    q_update('contracts', (int) $contract['id'], array('byeong_partner_id' => $pid, 'updated_at' => now()));
    $name = (string) q_value('SELECT name FROM partners WHERE id = ?', array($pid));
    return array(true, '‘' . $contract['name'] . '’에 ‘' . $name . '’ 님을 청소 담당으로 배치했어요.'
        . ($created ? ' 처음 배치라 청소 담당 파트너로도 등록했어요. 지급 계좌는 정기청소 정산 › 파트너 · 계좌에서 넣어 주세요.' : ''));
}

/** 배치할 수 있는 청소(끝나지 않은 것): 이름순 */
function contracts_open()
{
    return q_all("SELECT * FROM contracts WHERE end_month IS NULL OR end_month = '' OR end_month >= ? ORDER BY name, id", array(date('Y-m')));
}
