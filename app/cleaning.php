<?php
/**
 * 그린청소 홈페이지(사이트 모드 cleaning): 화면 문구, 설정 기본값, 사진, 견적 문의.
 * app/SITE_MODE 파일에 cleaning 이 적혀 있으면 첫 화면이 전자책 스토어 대신 이 홈페이지가 됩니다.
 */

/** 관리자 › 홈페이지 관리에서 바꾸는 값(설정 표에 gc_ 로 저장) */
function cleaning_defaults()
{
    return array(
        'gc_name' => '그린청소',
        'gc_phone' => '010-6636-7748',
        'gc_kakao_url' => '',
        // 채널톡(channel.io) 플러그인 키: 넣으면 홈페이지 오른쪽 아래에 채팅 상담 버튼이 떠 있습니다.
        'gc_channeltalk_key' => '11082e32-c584-482f-85ca-1d17724aaa7a',
        'gc_tagline' => '음성 · 진천 · 충북혁신도시 정기청소 전문',
        'gc_owner' => '김대열',
        'gc_biz_number' => '304-06-81659',
        'gc_biz_type' => '서비스',
        'gc_biz_item' => '청소대행',
        'gc_address' => '경기도 화성시 동탄면 동탄대로9길 19, 2629동 1103호',
        'gc_email' => 'hledan@naver.com',
        'gc_area' => '충북 음성 · 진천 · 혁신도시',
        'gc_notify_email' => '',
        // 사진: {"site":[6], "ba":[{title,before,after}×2], "map":""}
        'gc_photos' => '',
    );
}

/** 그린청소 설정 값 하나 */
function gc($key)
{
    return setting('gc_' . $key);
}

/** 사이트 이름(관리자 화면 제목 등): 청소 모드면 업체 이름, 아니면 스토어 이름 */
function site_name()
{
    return SITE_MODE === 'cleaning' ? gc('name') : setting('store_name');
}

/** tel: 링크용 숫자만 */
function tel_href($phone)
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', (string) $phone);
}

const CLEANING_KINDS = array('office' => '사무실', 'building' => '건물·상가', 'restroom' => '화장실');
const INQUIRY_STATUS = array('new' => '새 문의', 'contacted' => '연락함', 'contracted' => '계약', 'closed' => '종료');
const CLEANING_SITE_PHOTOS = 6;
const CLEANING_BA_PAIRS = 2;

/** 서비스 3가지: 종류 키 => [제목, 짧은 이름, 아이콘 path, 설명] */
function cleaning_services()
{
    return array(
        'office' => array('사무실 정기청소', '사무실 정기청소', 'M6 21V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v17M14 9h3a1 1 0 0 1 1 1v11M4 21h16M9 7h2M9 11h2M9 15h2',
            '책상·바닥·탕비실·회의실까지. 정해진 요일과 시간에 전담 인력이 방문해 업무 공간을 늘 같은 상태로 유지합니다.'),
        'building' => array('건물·상가 정기청소', '건물·상가 정기청소', 'M4 9l1.5-5h13L20 9M4 9v11h16V9M4 9c0 1.7 1.3 3 2.7 3S9.3 10.7 9.3 9c0 1.7 1.2 3 2.7 3s2.7-1.3 2.7-3c0 1.7 1.2 3 2.6 3S20 10.7 20 9M10 20v-5h4v5',
            '계단·복도·엘리베이터 등 공용부와 매장 내부. 고객이 처음 마주하는 공간을 깔끔하게 관리합니다.'),
        'restroom' => array('화장실 정기청소', '화장실 정기청소', 'M4 12h16v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2zM6 12V5a2 2 0 0 1 4 0M7 19l-1 2M17 19l1 2',
            '변기·세면대·바닥 살균 세척과 소모품 점검. 냄새와 물때까지 놓치지 않고 위생적으로 관리합니다.'),
    );
}

function cleaning_steps()
{
    return array(
        array('문의', '견적 폼, 전화, 카카오톡으로 공간 종류와 위치를 알려주세요.'),
        array('방문 견적', '현장을 직접 보고 면적·오염도에 맞춘 견적을 무료로 드립니다.'),
        array('계약', '청소 주기, 요일·시간, 범위를 확정하고 전담 인력을 배정합니다.'),
        array('정기 관리', '약속한 일정에 맞춰 방문하고, 요청사항은 바로 반영합니다.'),
    );
}

/** 청소 범위: 종류 키 => 항목 8개 */
function cleaning_scope()
{
    return array(
        'office' => array('바닥 청소기 · 물걸레', '책상 · 사무기기 먼지 제거', '휴지통 비우기 · 분리수거', '탕비실 싱크대 · 개수대', '회의실 테이블 · 의자 정리', '출입문 · 유리 손자국 제거', '창틀 · 블라인드 먼지', '소모품 점검 · 보충'),
        'building' => array('계단 · 복도 바닥 청소', '엘리베이터 내부 · 버튼', '출입구 · 현관 유리', '난간 · 손잡이 소독', '매장 내부 바닥', '쇼윈도 · 진열대 먼지', '공용 쓰레기장 정리', '외부 출입로 쓸기'),
        'restroom' => array('변기 · 소변기 살균 세척', '세면대 · 수전 물때 제거', '바닥 세척 · 배수구', '거울 · 칸막이 닦기', '냄새 관리 · 탈취', '휴지 · 핸드타월 보충', '휴지통 비우기', '타일 줄눈 부분 세척'),
    );
}

function cleaning_regions()
{
    return array(
        array('음성군', '음성읍 · 금왕읍 · 대소면 등'),
        array('진천군', '진천읍 · 덕산읍 · 이월면 등'),
        array('충북혁신도시', '음성·진천 혁신도시 일대'),
    );
}

function cleaning_reviews()
{
    return array(
        array('매주 같은 분이 오셔서 따로 설명할 필요가 없어요. 월요일 아침 출근이 달라졌습니다.', '혁신도시 사무실 · 40평'),
        array('매장 오픈 전에 끝내주셔서 영업에 지장이 전혀 없습니다. 화장실 관리가 특히 만족스러워요.', '진천 카페 운영 · 상가 1층'),
        array('건물 공용부 민원이 확실히 줄었습니다. 견적도 현장 보고 투명하게 주셨어요.', '음성 상가건물 · 관리인'),
    );
}

function cleaning_faq()
{
    return array(
        array('서비스 지역은 어디인가요?', '충북 음성군, 진천군, 충북혁신도시를 중심으로 운영합니다. 인근 지역은 전화나 카카오톡으로 문의해 주세요.'),
        array('정기청소 주기는 어떻게 정하나요?', '주 1회부터 주 5회까지 공간 규모와 사용 인원에 맞춰 협의합니다. 운영 중에도 주기 조정이 가능합니다.'),
        array('업무 시간 외에도 청소가 가능한가요?', '이른 아침, 퇴근 후 저녁, 주말 작업 모두 가능합니다. 영업·업무에 방해되지 않는 시간으로 맞춰드립니다.'),
        array('견적 비용이 따로 드나요?', '아니요. 현장 방문 견적은 무료이며, 견적 후 계약 여부는 자유롭게 결정하시면 됩니다.'),
        array('청소 장비와 세제는 누가 준비하나요?', '청소 장비와 세제는 모두 그린청소에서 준비합니다. 휴지·핸드타월 등 소모품 보충도 요청 시 가능합니다.'),
        array('매번 담당자가 바뀌나요?', '전담 인력 배정을 원칙으로 합니다. 같은 담당자가 같은 기준으로 관리해 품질이 일정합니다.'),
    );
}

/* ───────── 사진 ───────── */

/** 올린 사진: site(작업 현장 6칸), ba(청소 전후 2쌍), map(서비스 지역 지도) */
function cleaning_photos()
{
    $p = json_decode(gc('photos'), true);
    $p = is_array($p) ? $p : array();
    $site = array();
    for ($i = 0; $i < CLEANING_SITE_PHOTOS; $i++) {
        $site[$i] = (string) ($p['site'][$i] ?? '');
    }
    $titles = array('사무실 탕비실 정리 · 세척', '상가 공용 계단 · 복도');
    $ba = array();
    for ($i = 0; $i < CLEANING_BA_PAIRS; $i++) {
        $ba[$i] = array(
            'title' => (string) ($p['ba'][$i]['title'] ?? $titles[$i]),
            'before' => (string) ($p['ba'][$i]['before'] ?? ''),
            'after' => (string) ($p['ba'][$i]['after'] ?? ''),
        );
    }
    return array('site' => $site, 'ba' => $ba, 'map' => (string) ($p['map'] ?? ''));
}

function save_cleaning_photos($photos)
{
    save_settings(array('gc_photos' => json_encode($photos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
}

/* ───────── 견적 문의 ───────── */

/** 같은 곳(IP)에서 10분 안에 3건까지만 받습니다. IP는 그대로 두지 않고 해시로만 남깁니다. */
function inquiry_ip_hash()
{
    return substr(hash('sha256', 'inquiry|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . secret_key()), 0, 32);
}

function inquiry_rate_limited()
{
    $since = date('Y-m-d H:i:s', time() - 600);
    return (int) q_value('SELECT COUNT(*) FROM inquiries WHERE ip_hash = ? AND created_at >= ?', array(inquiry_ip_hash(), $since)) >= 3;
}

/** 견적 문의 입력 검사. 반환: [값, 오류 목록] */
function inquiry_from_request()
{
    $v = array(
        'kind' => array_key_exists(input('kind'), CLEANING_KINDS) ? input('kind') : 'office',
        'name' => str_cut(trim(input('name')), 60, ''),
        'phone' => str_cut(trim(input('phone')), 30, ''),
        'address' => str_cut(trim(input('address')), 120, ''),
    );
    $errors = array();
    if ($v['name'] === '') {
        $errors['name'] = '업체명이나 담당자 이름을 적어 주세요.';
    }
    $digits = preg_replace('/[^0-9]/', '', $v['phone']);
    if (strlen($digits) < 9 || strlen($digits) > 12 || !preg_match('/^[0-9+\-\s().]+$/', $v['phone'])) {
        $errors['phone'] = '연락받을 전화번호를 숫자로 적어 주세요.';
    }
    if (input('agree') !== '1') {
        $errors['agree'] = '개인정보 수집·이용에 동의해 주세요.';
    }
    return array($v, $errors);
}

function save_inquiry($v)
{
    $id = q_insert('inquiries', array(
        'kind' => $v['kind'], 'name' => $v['name'], 'phone' => $v['phone'], 'address' => $v['address'],
        'status' => 'new', 'memo' => '', 'ip_hash' => inquiry_ip_hash(), 'created_at' => now(), 'updated_at' => now(),
    ));
    notify_inquiry($v);
    return $id;
}

/** 새 문의 알림 메일(관리자 › 홈페이지 관리에 알림 이메일을 넣었을 때만). 못 보내도 문의 접수는 그대로 됩니다. */
function notify_inquiry($v)
{
    $to = gc('notify_email');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return false;
    }
    $subject = '[' . gc('name') . '] 새 견적 문의 · ' . CLEANING_KINDS[$v['kind']] . ' · ' . $v['name'];
    $body = "새 견적 문의가 들어왔어요.\n\n"
        . '종류: ' . CLEANING_KINDS[$v['kind']] . "\n"
        . '업체명 / 담당자: ' . $v['name'] . "\n"
        . '연락처: ' . $v['phone'] . "\n"
        . '주소 · 면적: ' . ($v['address'] !== '' ? $v['address'] : '-') . "\n"
        . '접수: ' . date('Y-m-d H:i') . "\n\n"
        . "관리자 화면에서 확인: " . (is_https() ? 'https' : 'http') . '://' . preg_replace('/[^A-Za-z0-9.:\-]/', '', $_SERVER['HTTP_HOST'] ?? '') . "/admin/inquiries\n";
    $host = preg_replace('/^www\./', '', preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = 'From: =?UTF-8?B?' . base64_encode(gc('name')) . '?= <no-reply@' . ($host !== '' ? $host : 'localhost') . ">\r\n"
        . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', chunk_split(base64_encode($body)), $headers);
}

function inquiry_counts()
{
    $counts = array_fill_keys(array_keys(INQUIRY_STATUS), 0);
    foreach (q_all('SELECT status, COUNT(*) AS n FROM inquiries GROUP BY status') as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    return $counts;
}
