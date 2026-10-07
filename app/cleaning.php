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
        // 새 견적 문의를 메일로 받을 주소(관리자 › 홈페이지 관리에서 바꿈)
        'gc_notify_email' => 'hledan@naver.com',
        // 사진: {"site":[6], "ba":[{title,before,after}×2], "map":""}
        'gc_photos' => '',
        // 고객 후기: [[글, 누가]…]. 비어 있으면 처음 후기(cleaning_default_reviews)
        'gc_reviews' => '',
        // 정기청소 정산 › 갑·을이 하는 일: {"gap":[…], "eul":[…]}. 비어 있으면 처음 목록(CONTRACT_ROLE_DEFAULTS)
        'gc_roles' => '',
        // 일회성 정산 › 파트너가 하는 일(비어 있으면 ONETIME_ROLE_DEFAULTS)
        'gc_onetime_roles' => '',
        // 검색 노출(SEO): 첫 화면 제목·설명·키워드, 네이버·구글 사이트 확인 코드
        'gc_seo_title' => '충북음성청소업체',
        'gc_seo_desc' => '충북음성청소업체, 금왕사무실정기청소, 음성공장청소, 충북혁신도시화장실청소, 진천상가청소, 대소공단청소',
        'gc_seo_keywords' => '충북음성청소업체, 금왕사무실정기청소, 음성공장청소, 충북혁신도시화장실청소, 진천상가청소, 대소공단청소, 음성청소업체, 진천청소업체, 사무실정기청소, 화장실청소, 상가청소, 공장청소',
        'gc_naver_verify' => '',
        // 관리자 › 헤드 코드: 공개 화면 <head> 끝과 </body> 바로 앞에 그대로 넣는 코드(분석·광고·확인 태그 등)
        'gc_head_code' => '',
        'gc_body_code' => '',
        'gc_code_enabled' => '1',
        'gc_google_verify' => '',
        // 관리자 › 검색 등록: 빙 사이트 확인 코드, 다음 웹마스터도구 robots.txt 인증 줄
        'gc_bing_verify' => '',
        'gc_daum_verify' => '',
        // IndexNow(바뀐 주소를 네이버 · 빙에 바로 알리기): 켜기, 열쇠, 최근 기록, 최근 알린 주소, 다음 예약 글 공개 시각
        'gc_indexnow_on' => '1',
        'gc_indexnow_key' => '',
        'gc_indexnow_log' => '',
        'gc_indexnow_sent' => '',
        'gc_indexnow_next' => '',
        // 첫 화면 내용(홈페이지 정보 · 사진 · 후기)을 마지막으로 바꾼 시각(사이트맵 lastmod)
        'gc_home_updated' => '',
        // 주소 하나로 모으기(http · www → https://그린청소.com): '0'이면 끔
        'gc_canonical_redirect' => '1',
        // 검색어 페이지 기본 5개를 넣었는지(한 번만)
        'gc_landing_seeded' => '',
        // 텔레그램 알림: 봇 토큰(비밀), 봇 아이디, 대화방 번호 · 이름, 켜기, 마지막 결과
        'gc_tg_token' => '',
        'gc_tg_bot' => '',
        'gc_tg_chat' => '',
        'gc_tg_chat_title' => '',
        // 받는 대화방 여러 곳 [{id, title, type}], 받는 사람 찾기 결과
        'gc_tg_chats' => '',
        'gc_tg_candidates' => '',
        'gc_tg_on' => '1',
        'gc_tg_last' => '',
        'gc_tg_check' => '',
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

/** 공개 화면 파일 주소(바뀌면 새로 받도록 ?v=수정 시각) */
function cleaning_asset($file)
{
    return '/assets/' . $file . '?v=' . @filemtime(PUBLIC_DIR . '/assets/' . $file);
}

/** 로고(그린 + 청소). $light: 어두운 바탕용 */
function cleaning_logo($light = false)
{
    $name = gc('name');
    $leaf = $light ? '#A8D5BA' : '#2A2D33';
    $stroke = $light ? '#fff' : '#2F7D5C';
    $text = mb_substr($name, 0, 2) === '그린' ? '<span class="g-logo-green">그린</span>' . e(mb_substr($name, 2)) : e($name);
    return '<svg class="g-logo-mark" viewBox="0 0 66 64" aria-hidden="true"><path d="M47.6 16.4A22 22 0 1 0 54 32H36" fill="none" stroke="' . $stroke . '" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/><path d="M50 14C50 7 55 3 62 3C62 10 57 14 50 14Z" fill="' . $leaf . '"/></svg><span class="g-logo-text">' . $text . '</span>';
}

function cleaning_kakao_icon()
{
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3C6.5 3 2 6.6 2 11c0 2.8 1.9 5.3 4.7 6.7l-1 3.6c-.1.3.3.6.6.4l4.2-2.8c.5.1 1 .1 1.5.1 5.5 0 10-3.6 10-8S17.5 3 12 3z"/></svg>';
}

function cleaning_phone_icon()
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>';
}

/** 사이트 확인 코드: 메타 태그를 통째로 붙여 넣어도 content 값만 꺼냅니다 */
function verify_code($raw)
{
    if (preg_match('/content=["\']([^"\']+)["\']/', (string) $raw, $m)) {
        $raw = $m[1];
    }
    return preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $raw);
}

/** 지역·업종별 청소(검색어가 담긴 소개 카드): [제목(검색어), 설명, 견적 종류] */
function cleaning_local_services()
{
    return array(
        array('충북 음성 청소업체', '음성군 사무실·상가·건물 정기청소를 지역 업체가 맡습니다. 가까이 있어 약속한 요일·시간에 정확히 방문합니다.', 'office'),
        array('금왕 사무실 정기청소', '금왕읍 사무실의 바닥·책상·탕비실·회의실·화장실까지 정해진 요일에 전담 인력이 같은 기준으로 관리합니다.', 'office'),
        array('음성 공장 청소', '음성 지역 공장의 사무동·휴게실·식당·화장실·복도를 근무 시간을 피해 깨끗하게 정리합니다.', 'building'),
        array('충북혁신도시 화장실 청소', '혁신도시 사무실·상가 화장실을 살균 세척하고 물때·냄새 관리와 소모품 보충까지 챙깁니다.', 'restroom'),
        array('진천 상가 청소', '진천 상가 매장 내부와 공용 계단·복도·엘리베이터를 영업 시작 전에 깔끔하게 마칩니다.', 'building'),
        array('대소공단 청소', '대소면 공단 공장·사무동 정기청소, 휴게실·화장실 위생 관리까지 한 번에 맡길 수 있습니다.', 'building'),
    );
}

/** 관리자가 넣은 코드(켜 둔 경우만). $where: head | body */
function custom_code($where)
{
    if (gc('code_enabled') === '0') {
        return '';
    }
    $code = gc($where === 'body' ? 'body_code' : 'head_code');
    return trim($code) !== '' ? "\n<!-- 관리자 › 헤드 코드 -->\n" . $code . "\n<!-- /헤드 코드 -->\n" : '';
}

/** tel: 링크용 숫자만 */
function tel_href($phone)
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', (string) $phone);
}

const CLEANING_KINDS = array('office' => '사무실', 'building' => '건물·상가', 'restroom' => '화장실');
const INQUIRY_STATUS = array('new' => '새 문의', 'contacted' => '연락함', 'contracted' => '계약', 'closed' => '종료');
const INQUIRY_MAILED = array('sent' => '메일 보냄', 'failed' => '메일 못 보냄', 'off' => '메일 알림 꺼짐');
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

const CLEANING_REVIEW_MAX = 6;

/** 후기 작성자 이름 가리기: 장혜진 → 장** (이미 *가 있으면 그대로) */
function mask_reviewer($name)
{
    $name = trim(preg_replace('/\s+/u', ' ', (string) $name));
    if ($name === '' || strpos($name, '*') !== false) {
        return $name;
    }
    return mb_substr($name, 0, 1) . '**';
}

/** 홈페이지 고객 후기: 관리자 › 후기 관리에서 고친 것, 아직 안 고쳤으면 처음 후기 */
function cleaning_reviews()
{
    $saved = gc('reviews');
    if ($saved === '') {
        return cleaning_default_reviews();
    }
    $list = json_decode($saved, true);
    $out = array();
    foreach (is_array($list) ? $list : array() as $r) {
        if (is_array($r) && isset($r[0]) && trim((string) $r[0]) !== '') {
            $out[] = array((string) $r[0], (string) ($r[1] ?? ''));
        }
    }
    return $out;
}

function cleaning_default_reviews()
{
    return array(
        array('매주 같은 분이 오셔서 따로 설명할 필요가 없어요. 월요일 아침 출근이 달라졌습니다.', '김**'),
        array('영업 시작 전에 끝내주셔서 지장이 전혀 없습니다. 화장실 관리가 특히 만족스러워요.', '장**'),
        array('공용 공간 민원이 확실히 줄었습니다. 견적도 현장 보고 투명하게 주셨어요.', '이**'),
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
    // 알림 메일을 보냈는지 함께 남겨 관리자 화면에서 확인할 수 있게 합니다.
    q_update('inquiries', $id, array('mailed' => notify_inquiry($v)));
    telegram_notify_inquiry($v);
    return $id;
}

/** 관리자 화면 주소(메일 본문용) */
function site_base_url()
{
    return (is_https() ? 'https' : 'http') . '://' . preg_replace('/[^A-Za-z0-9.:\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
}

/** 알림 메일 한 통 보내기($html 이 있으면 HTML + 글자 메일을 함께). 반환: sent | failed | off(받을 주소 없음) */
function send_notice_mail($subject, $text, $html = '')
{
    $to = gc('notify_email');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return 'off';
    }
    if (!function_exists('mail')) {
        return 'failed';
    }
    // 보내는 주소: 접속한 도메인(포트·www 제외). IP 주소로 접속했으면 쓰지 않습니다.
    $host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    $host = preg_replace('/^www\./', '', preg_replace('/[^a-z0-9.\-]/', '', $host));
    $isDomain = $host !== '' && strpos($host, '.') !== false && !filter_var($host, FILTER_VALIDATE_IP);
    $from = 'no-reply@' . ($isDomain ? $host : 'localhost.localdomain');
    $headers = 'From: =?UTF-8?B?' . base64_encode(gc('name')) . '?= <' . $from . ">\r\nMIME-Version: 1.0\r\n";
    if ($html !== '') {
        // 메일 앱이 HTML을 못 보여 주면 글자 메일이 대신 보입니다.
        $boundary = 'gc-' . bin2hex(random_bytes(8));
        $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
            . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
            . "--$boundary--\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
        $body = chunk_split(base64_encode($text));
    }
    $subjectLine = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    // 보내는 주소(-f)를 함께 알려 주면 받는 쪽에서 스팸으로 덜 분류합니다. 서버가 막으면 기본 방식으로 다시 보냅니다.
    $ok = @mail($to, $subjectLine, $body, $headers, '-f' . $from) || @mail($to, $subjectLine, $body, $headers);
    return $ok ? 'sent' : 'failed';
}

/**
 * 알림 메일 HTML(그린청소 디자인). 메일 앱마다 지원이 달라 표(table)와 인라인 스타일만 씁니다.
 * $o: preheader, badge, title, intro, rows([이름, 값 HTML]), buttons([글자, 주소, primary?]), note
 */
function cleaning_mail_html($o)
{
    $font = "-apple-system,BlinkMacSystemFont,'Apple SD Gothic Neo','Malgun Gothic','맑은 고딕',sans-serif";
    $name = gc('name');
    $logo = mb_substr($name, 0, 2) === '그린'
        ? '<span style="color:#ffffff">그린</span><span style="color:#D7EEDF">' . e(mb_substr($name, 2)) . '</span>'
        : e($name);
    $rows = '';
    foreach ($o['rows'] ?? array() as $i => $r) {
        $border = $i ? 'border-top:1px solid #E3ECE6;' : '';
        $rows .= '<tr><td style="' . $border . 'padding:14px 18px;width:92px;font-size:14px;color:#5B6068;vertical-align:top;font-family:' . $font . '">' . e($r[0]) . '</td>'
            . '<td style="' . $border . 'padding:14px 18px 14px 0;font-size:16px;font-weight:700;color:#1D3329;line-height:1.5;word-break:keep-all;font-family:' . $font . '">' . $r[1] . '</td></tr>';
    }
    $buttons = '';
    foreach ($o['buttons'] ?? array() as $b) {
        $style = !empty($b[2])
            ? 'background:#2F7D5C;color:#ffffff;border:1px solid #2F7D5C;'
            : 'background:#ffffff;color:#2A2D33;border:1px solid #CFD8D2;';
        $buttons .= '<a href="' . e($b[1]) . '" style="display:inline-block;margin:0 8px 10px 0;padding:13px 22px;border-radius:10px;font-size:15px;font-weight:700;text-decoration:none;font-family:' . $font . ';' . $style . '">' . e($b[0]) . '</a>';
    }
    return '<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($o['title']) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#EEF3EF;-webkit-text-size-adjust:100%">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . e($o['preheader'] ?? '') . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF3EF"><tr><td align="center" style="padding:28px 12px">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#ffffff;border:1px solid #DCE8E0;border-radius:18px;overflow:hidden">'
        // 머리: 녹색 띠 + 업체 이름
        . '<tr><td style="background:#2F7D5C;padding:20px 28px;font-family:' . $font . '">'
        . '<span style="font-size:22px;font-weight:800;letter-spacing:-0.5px">' . $logo . '</span>'
        . '<span style="font-size:13px;font-weight:600;color:#D7EEDF;padding-left:10px">' . e($o['label'] ?? '홈페이지 알림') . '</span></td></tr>'
        // 제목
        . '<tr><td style="padding:28px 28px 6px;font-family:' . $font . '">'
        . (!empty($o['badge']) ? '<span style="display:inline-block;background:#E6F2EA;color:#235F47;font-size:13px;font-weight:700;padding:5px 12px;border-radius:999px">' . e($o['badge']) . '</span>' : '')
        . '<h1 style="margin:12px 0 8px;font-size:23px;line-height:1.35;font-weight:800;color:#1D3329;word-break:keep-all">' . e($o['title']) . '</h1>'
        . '<p style="margin:0;font-size:15px;line-height:1.6;color:#555A62;word-break:keep-all">' . e($o['intro'] ?? '') . '</p></td></tr>'
        // 내용 표
        . ($rows !== '' ? '<tr><td style="padding:18px 28px 6px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F6FAF7;border:1px solid #E3ECE6;border-radius:14px">' . $rows . '</table></td></tr>' : '')
        // 단추
        . ($buttons !== '' ? '<tr><td style="padding:18px 28px 18px">' . $buttons . '</td></tr>' : '')
        // 바닥
        . '<tr><td style="padding:16px 28px 20px;background:#F6FAF7;border-top:1px solid #E3ECE6;font-size:12px;line-height:1.6;color:#8A9098;word-break:keep-all;font-family:' . $font . '">'
        . e($o['note'] ?? '') . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** 새 문의 알림 메일. 못 보내도 문의 접수는 그대로 되고, 관리자 › 견적 문의에서 볼 수 있습니다. */
function notify_inquiry($v)
{
    $kind = CLEANING_KINDS[$v['kind']];
    $when = date('Y.m.d H:i');
    $admin = site_base_url() . '/admin/inquiries';
    $subject = '[' . gc('name') . '] 새 견적 문의 · ' . $kind . ' · ' . $v['name'];
    $text = "홈페이지로 새 견적 문의가 들어왔어요.\n\n"
        . '청소 종류: ' . $kind . " 정기청소\n"
        . '업체명 / 담당자: ' . $v['name'] . "\n"
        . '연락처: ' . $v['phone'] . "\n"
        . '주소 · 면적: ' . ($v['address'] !== '' ? $v['address'] : '-') . "\n"
        . '접수: ' . $when . "\n\n"
        . "연락한 뒤에는 관리자 화면에서 상태와 메모를 남겨 주세요.\n" . $admin . "\n";
    $html = cleaning_mail_html(array(
        'label' => '견적 문의 알림',
        'preheader' => $v['name'] . ' · ' . $v['phone'] . ' · ' . $kind . ' 정기청소 문의',
        'badge' => $kind . ' 정기청소',
        'title' => '새 견적 문의가 들어왔어요',
        'intro' => '고객에게 영업일 하루 안에 연락드린다고 안내했어요. 아래 번호로 연락해 주세요.',
        'rows' => array(
            array('업체 / 담당자', e($v['name'])),
            array('연락처', '<a href="' . e(tel_href($v['phone'])) . '" style="color:#2F7D5C;text-decoration:none">' . e($v['phone']) . '</a>'),
            array('주소 · 면적', $v['address'] !== '' ? e($v['address']) : '<span style="color:#9AA19C;font-weight:400">적지 않음</span>'),
            array('접수', e($when)),
        ),
        'buttons' => array(
            array('전화 걸기', tel_href($v['phone']), true),
            array('관리자 화면에서 보기', $admin, false),
        ),
        'note' => gc('name') . ' 홈페이지 ‘무료 견적 문의’로 들어온 내용을 자동으로 보내 드렸어요. 이 메일은 보내기 전용이라 답장은 받지 않아요.',
    ));
    return send_notice_mail($subject, $text, $html);
}

function inquiry_counts()
{
    $counts = array_fill_keys(array_keys(INQUIRY_STATUS), 0);
    foreach (q_all('SELECT status, COUNT(*) AS n FROM inquiries GROUP BY status') as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    return $counts;
}
