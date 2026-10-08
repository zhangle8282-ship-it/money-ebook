<?php
/**
 * 그린청소 블로그 자동 글쓰기.
 * - 매일 Claude 예약 작업이 /api/auto/plan 으로 ‘다음에 쓸 글’(종류 · 지역 · 키워드 · 주제)을 받아 글을 쓰고,
 *   AI 흔적을 지운 뒤 /api/auto/post 로 보내면, 하루 1개씩 공개되도록 예약합니다.
 * - 공개 시각은 날마다 정한 시간대 안에서 분 단위까지 다르게 정합니다(매일 같은 시각이면 자동 글로 보여 검색에서 빠질 수 있어서).
 * - 대표 사진은 사진 창고에서 글 종류에 맞는 ‘아직 안 쓴’ 현장 사진을 골라 붙입니다(같은 사진을 두 번 쓰지 않음).
 * - 글 받는 통로는 관리자 › 블로그 › 자동 글쓰기에서 만든 비밀 열쇠로만 열립니다(열쇠는 해시로만 저장).
 */

// 글 종류: [이름, 제목에 꼭 들어갈 말, 키워드 뒷부분, 청소 범위 종류]
const AUTO_KINDS = array(
    'office' => array('사무실', '사무실정기청소', '사무실정기청소', 'office'),
    'factory' => array('공장', '공장청소', '공장정기청소', 'building'),
    'restroom' => array('화장실', '화장실청소', '화장실정기청소', 'restroom'),
    'building' => array('건물 · 상가', '건물상가청소', '건물상가정기청소', 'building'),
);
// 20번에 한 바퀴: 사무실 8 · 공장 7 · 화장실 3 · 건물상가 2(사무실 · 공장 비중이 더 높게)
const AUTO_KIND_CYCLE = array('office', 'factory', 'office', 'factory', 'restroom', 'office', 'factory', 'building', 'office', 'factory',
    'office', 'restroom', 'factory', 'office', 'building', 'factory', 'office', 'restroom', 'factory', 'office');
// 키워드 앞에 붙는 서비스 지역(차례로 돌아가며)
const AUTO_REGIONS = array('음성', '진천', '금왕', '충북혁신도시', '대소', '청주 오창', '음성읍', '덕산', '경기 안성', '진천읍');
// 글 주제(차례로 돌아가며, 같은 주제가 몰리지 않게)
const AUTO_ANGLES = array(
    '알맞은 청소 주기(주 몇 회가 좋은지, 공간 · 인원별로)',
    '정기청소에서 꼭 챙기는 곳 체크리스트',
    '견적이 정해지는 기준(면적 · 횟수 · 오염 정도 · 작업 시간)',
    '지금 계절에 특히 신경 쓸 청소 포인트',
    '직원 · 손님이 바로 느끼는 차이(위생 · 첫인상 · 업무 분위기)',
    '자주 묻는 질문 5가지와 답',
    '직접 청소할 때와 전문 업체에 맡길 때 비교',
    '처음 정기청소를 맡길 때 진행 순서(문의부터 정기 관리까지)',
    '먼지 · 냄새 · 물때가 생기는 원인과 관리 방법',
    '업무 · 영업 시간을 피해 청소하는 방법',
    '정기청소 계약 전에 꼭 확인할 것',
    '청소가 빠뜨리기 쉬운 곳과 놓치지 않는 순서',
);
const AUTO_QUEUE_DAYS = 7;   // 최대 며칠 뒤까지 미리 예약해 둘 수 있는지
// 누락 막는 자동 검사
const AUTO_MIN_TEXT = 1500;      // 본문 글자 수(태그 뺀, 띄어쓰기 포함) 최소
const AUTO_MAX_TEXT = 3000;      // … 최대
const AUTO_KEYWORD_MAX = 6;      // 대표 키워드(제목 + 본문) 최대 횟수
const AUTO_SIMILAR_MAX = 0.35;   // 최근 글 30개와 겹치는 정도(새 글 기준) 최대
const AUTO_BANNED = array('최고', '1위', '100%', '99.9%', '무조건', '최저가', '완벽', '업계 최초', '국내 최초', '전국 최초', '보장합니다', '책임집니다');

/** 열쇠 확인: Authorization: Bearer … 또는 X-Auto-Token */
function auto_token_ok()
{
    $hash = gc('auto_token_hash');
    $given = '';
    $auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
        $given = $m[1];
    } elseif (!empty($_SERVER['HTTP_X_AUTO_TOKEN'])) {
        $given = (string) $_SERVER['HTTP_X_AUTO_TOKEN'];
    }
    return $hash !== '' && $given !== '' && hash_equals($hash, hash('sha256', $given));
}

/** 새 열쇠 만들기(화면에 한 번만 보여 주고 해시만 저장). 반환: 열쇠 */
function auto_token_new()
{
    $token = 'gc_' . bin2hex(random_bytes(24));
    save_settings(array('gc_auto_token_hash' => hash('sha256', $token), 'gc_auto_token_at' => now()));
    return $token;
}

/** 자동 글 몇 번째인지(종류 · 지역 · 주제 돌리기에 씀) */
function auto_count()
{
    return (int) q_value('SELECT COUNT(*) FROM blog_posts WHERE auto = 1');
}

/** 공개 시간대 [시작, 끝](‘HH:MM’, 분으로). 잘못된 값이면 08:30 ~ 20:30 */
function auto_window()
{
    $min = function ($t, $def) {
        return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', (string) $t, $m) ? (int) $m[1] * 60 + (int) $m[2] : $def;
    };
    $from = $min(gc('auto_from'), 510);
    $to = $min(gc('auto_to'), 1230);
    return $to - $from >= 120 ? array($from, $to) : array(510, 1230);
}

/**
 * 다음에 공개할 날과 시각. 오늘(아직 시간대가 남았으면)부터 자동 글이 없는 첫 날을 고르고,
 * 시각은 시간대 안에서 무작위(전날 자동 글과 1시간 30분 넘게 떨어지게). $withTime=false 면 날짜만. 너무 멀면 null
 */
function auto_next_slot($withTime = true)
{
    list($from, $to) = auto_window();
    $rows = q_all("SELECT published_at FROM blog_posts WHERE auto = 1 AND published_at >= ?", array(date('Y-m-d 00:00:00', strtotime('-1 day'))));
    $taken = array();
    foreach ($rows as $r) {
        $taken[substr($r['published_at'], 0, 10)] = (int) substr($r['published_at'], 11, 2) * 60 + (int) substr($r['published_at'], 14, 2);
    }
    $nowMin = (int) date('H') * 60 + (int) date('i');
    for ($i = 0; $i <= AUTO_QUEUE_DAYS; $i++) {
        $d = date('Y-m-d', strtotime('+' . $i . ' day'));
        $start = $i === 0 ? max($from, $nowMin + 30) : $from; // 오늘이면 지금부터 30분 뒤 이후
        if (isset($taken[$d]) || $start > $to - 10) {
            continue;
        }
        if (!$withTime) {
            return $d;
        }
        $prev = $taken[date('Y-m-d', strtotime($d . ' -1 day'))] ?? null;
        $pick = mt_rand($start, $to);
        for ($try = 0; $try < 12 && $prev !== null && abs($pick - $prev) < 90; $try++) {
            $pick = mt_rand($start, $to);
        }
        return $d . sprintf(' %02d:%02d:%02d', intdiv($pick, 60), $pick % 60, mt_rand(0, 59));
    }
    return null;
}

/** 사진 창고에서 아직 안 쓴 사진 수(종류별 + 전체) */
function stock_counts()
{
    $out = array_fill_keys(array_keys(AUTO_KINDS), 0) + array('etc' => 0, 'all' => 0);
    foreach (q_all('SELECT kind, COUNT(*) AS n FROM photo_stock WHERE used_post_id IS NULL GROUP BY kind') as $r) {
        $out[isset($out[$r['kind']]) ? $r['kind'] : 'etc'] += (int) $r['n'];
        $out['all'] += (int) $r['n'];
    }
    return $out;
}

/** 대표 사진 고르기: 경험 노트와 함께 올린 사진 → 같은 종류 사진 → ‘기타’ 사진(먼저 올린 것부터). 없으면 대표 사진 없이 */
function stock_pick($kind, $noteId = 0)
{
    if ($noteId && ($p = q_one('SELECT * FROM photo_stock WHERE used_post_id IS NULL AND note_id = ? ORDER BY id LIMIT 1', array((int) $noteId)))) {
        return $p;
    }
    return q_one("SELECT * FROM photo_stock WHERE used_post_id IS NULL AND note_id IS NULL AND kind IN (?, 'etc') ORDER BY CASE WHEN kind = ? THEN 0 ELSE 1 END, id LIMIT 1", array($kind, $kind));
}

/** 다음에 쓸 경험 노트: 글 종류가 같은 노트 먼저, 없으면 아무 종류나(먼저 적은 것부터). 없으면 null */
function note_pick($kind)
{
    return q_one('SELECT * FROM experience_notes WHERE used_post_id IS NULL ORDER BY CASE WHEN kind = ? THEN 0 ELSE 1 END, id LIMIT 1', array($kind));
}

/** 안 쓴 경험 노트 수 */
function note_left()
{
    return (int) q_value('SELECT COUNT(*) FROM experience_notes WHERE used_post_id IS NULL');
}

/** 글자만(태그 빼고, 띄어쓰기 하나로) */
function auto_plain($html)
{
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(array('</p>', '</h2>', '</h3>', '</li>', '<br>'), ' ', (string) $html)), ENT_QUOTES, 'UTF-8')));
}

/** 글을 6글자 조각들로(띄어쓰기 · 문장부호 뺌) */
function auto_shingles($text)
{
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($text, 'UTF-8'));
    $n = mb_strlen($s, 'UTF-8');
    $out = array();
    for ($i = 0; $i + 6 <= $n; $i += 2) {
        $out[mb_substr($s, $i, 6, 'UTF-8')] = true;
    }
    return $out;
}

/** 최근 글 30개 중 가장 많이 겹치는 글: [겹치는 비율(새 글 기준), 그 글 제목] 또는 null */
function auto_similar($text)
{
    $mine = auto_shingles($text);
    if (!$mine) {
        return null;
    }
    $best = null;
    foreach (q_all('SELECT title, body, format FROM blog_posts ORDER BY id DESC LIMIT 30') as $p) {
        $other = auto_shingles(blog_is_html($p) ? auto_plain($p['body']) : blog_plain($p['body']));
        if (!$other) {
            continue;
        }
        $share = count(array_intersect_key($mine, $other)) / count($mine);
        if ($best === null || $share > $best[0]) {
            $best = array($share, $p['title']);
        }
    }
    return $best;
}

/** 다음에 쓸 글 계획 */
function auto_plan()
{
    $n = auto_count();
    $kind = AUTO_KIND_CYCLE[$n % count(AUTO_KIND_CYCLE)];
    $region = AUTO_REGIONS[$n % count(AUTO_REGIONS)];
    $angle = AUTO_ANGLES[$n % count(AUTO_ANGLES)];
    // 경험 노트가 있으면 먼저: 그 노트의 종류 · 지역으로
    $note = note_pick($kind);
    if ($note) {
        $kind = isset(AUTO_KINDS[$note['kind']]) ? $note['kind'] : $kind;
        $squash = preg_replace('/\s+/u', '', $note['region']);
        foreach (AUTO_REGIONS as $r) {
            if ($squash !== '' && mb_strpos($squash, preg_replace('/\s+/u', '', $r)) !== false) {
                $region = $r;
                break;
            }
        }
    }
    $k = AUTO_KINDS[$kind];
    $counts = stock_counts();
    $notePhotos = $note ? (int) q_value('SELECT COUNT(*) FROM photo_stock WHERE used_post_id IS NULL AND note_id = ?', array((int) $note['id'])) : 0;
    return array(
        'enabled' => gc('auto_on') === '1',
        // 경험 노트(있으면 이걸 바탕으로 1인칭 경험담으로, 노트에 없는 사실은 지어내지 않기). 보낼 때 note_id 를 함께
        'experience' => $note ? array('id' => (int) $note['id'], 'kind' => $note['kind'], 'region' => $note['region'], 'title' => $note['title'],
            'work_date' => $note['work_date'], 'story' => (string) $note['body'], 'photos' => $notePhotos) : null,
        'skip_today' => !$note && gc('auto_need_note') === '1',
        'publish_date' => auto_next_slot(false),
        'publish_window' => vsprintf('%02d:%02d ~ %02d:%02d', array(intdiv(auto_window()[0], 60), auto_window()[0] % 60, intdiv(auto_window()[1], 60), auto_window()[1] % 60)),
        'kind' => $kind,
        'kind_label' => $k[0],
        'region' => $region,
        'keyword' => $region . ' ' . $k[2],
        'title_must_include' => $k[1],
        'angle' => $angle,
        'month' => (int) date('n'),
        'photo_left' => $notePhotos + $counts[$kind] + $counts['etc'],
        'photos' => $counts,
        'recent_titles' => array_column(q_all('SELECT title FROM blog_posts ORDER BY id DESC LIMIT 40'), 'title'),
        'site' => array('name' => gc('name'), 'phone' => gc('phone'), 'area' => gc('area'), 'url' => base_url()),
        'format' => array(
            'body' => 'HTML. 쓸 수 있는 태그: p, h2, h3, strong, em, u, span(style="color:#…", class="fs-sm|fs-lg|fs-xl"), ul, ol, li, blockquote, hr, br. 표 · 그림 태그는 쓰지 않음. 소제목은 h2.',
            'min_text' => AUTO_MIN_TEXT,
            'max_text' => AUTO_MAX_TEXT,
        ),
        // 받을 때 자동 검사(걸리면 422 와 이유를 돌려줌)
        'checks' => array(
            'keyword_max' => AUTO_KEYWORD_MAX,
            'similar_max' => AUTO_SIMILAR_MAX,
            'banned_words' => AUTO_BANNED,
            'text_length' => array(AUTO_MIN_TEXT, AUTO_MAX_TEXT),
        ),
    );
}

/**
 * 받은 글 검사 · 저장. $in: [title, summary, keywords, body, kind]. 반환: [성공, 결과 또는 오류 목록]
 * 사진 창고에서 대표 사진을 골라 붙이고, 다음 빈 날 공개되도록 예약합니다.
 */
function auto_receive($in)
{
    $errors = array();
    $kind = isset(AUTO_KINDS[$in['kind'] ?? '']) ? $in['kind'] : '';
    $title = str_cut(trim(preg_replace('/\s+/u', ' ', (string) ($in['title'] ?? ''))), 200, '');
    $summary = str_cut(trim(preg_replace('/\s+/u', ' ', (string) ($in['summary'] ?? ''))), 300, '');
    $keywords = str_cut(trim((string) ($in['keywords'] ?? '')), 300, '');
    $body = rich_clean_html((string) ($in['body'] ?? ''));
    if ($kind === '') {
        $errors[] = 'kind 는 office · factory · restroom · building 중 하나여야 해요.';
    }
    $squash = function ($s) {
        return preg_replace('/\s+/u', '', $s);
    };
    if ($title === '' || ($kind !== '' && mb_strpos($squash($title), AUTO_KINDS[$kind][1]) === false)) {
        $errors[] = '제목에 ‘' . ($kind !== '' ? AUTO_KINDS[$kind][1] : '청소 종류') . '’이(가) 들어가야 해요.';
    }
    $hasRegion = false;
    foreach (AUTO_REGIONS as $r) {
        if (preg_match('/' . preg_quote($squash($r), '/') . '[^,]*정기청소/u', $squash($keywords))) {
            $hasRegion = true;
            break;
        }
    }
    if (!$hasRegion) {
        $errors[] = '키워드에 ‘서비스 지역 + 정기청소’(예: 음성 사무실정기청소)가 들어가야 해요.';
    }
    if ($summary === '') {
        $errors[] = '설명(summary)을 넣어 주세요.';
    }
    // 누락 막는 자동 검사 1: 길이
    $text = auto_plain($body);
    $len = str_len($text);
    if ($len < AUTO_MIN_TEXT || $len > AUTO_MAX_TEXT) {
        $errors[] = '본문 길이는 ' . AUTO_MIN_TEXT . '~' . AUTO_MAX_TEXT . '자로 맞춰 주세요(지금 ' . $len . '자).';
    }
    // 2: 대표 키워드(키워드 칸 첫 번째)는 제목 + 본문에 ' . AUTO_KEYWORD_MAX . '번까지
    $main = trim(explode(',', $keywords)[0]);
    if ($main !== '') {
        $times = substr_count($squash($title . ' ' . $text), $squash($main));
        if ($times > AUTO_KEYWORD_MAX) {
            $errors[] = '대표 키워드 ‘' . $main . '’이(가) ' . $times . '번 들어갔어요. ' . AUTO_KEYWORD_MAX . '번 이하로 줄이고 나머지는 자연스러운 말로 바꿔 주세요.';
        }
    }
    // 3: 과장 · 광고 금지어
    $found = array_values(array_filter(AUTO_BANNED, function ($w) use ($title, $text, $summary) {
        return mb_strpos($title . ' ' . $summary . ' ' . $text, $w) !== false;
    }));
    if ($found) {
        $errors[] = '과장 · 광고 표현이 있어요: ' . implode(', ', $found) . '. 빼거나 사실대로 바꿔 주세요.';
    }
    // 4: 최근 글 30개와 많이 겹치면(유사문서) 안 받음
    if ($text !== '') {
        $sim = auto_similar($text);
        if ($sim && $sim[0] > AUTO_SIMILAR_MAX) {
            $errors[] = '최근 글 ‘' . $sim[1] . '’과(와) ' . round($sim[0] * 100) . '% 겹쳐요(' . round(AUTO_SIMILAR_MAX * 100) . '% 넘으면 안 받음). 도입 · 소제목 · 문장을 새로 써 주세요.';
        }
    }
    if ($title !== '' && q_value('SELECT COUNT(*) FROM blog_posts WHERE title = ?', array($title))) {
        $errors[] = '같은 제목의 글이 이미 있어요.';
    }
    $at = auto_next_slot();
    if ($at === null) {
        $errors[] = AUTO_QUEUE_DAYS . '일 뒤까지 이미 예약돼 있어요.';
    }
    $noteId = (int) ($in['note_id'] ?? 0);
    $note = $noteId ? q_one('SELECT * FROM experience_notes WHERE id = ?', array($noteId)) : null;
    if ($noteId && (!$note || $note['used_post_id'])) {
        $errors[] = '그 경험 노트는 없거나 이미 다른 글에 썼어요.';
    }
    if (!$note && gc('auto_need_note') === '1') {
        $errors[] = '경험 노트가 있을 때만 받도록 정해져 있어요(남은 노트 없음). 오늘은 쉬어요.';
    }
    if ($errors) {
        // 관리 화면에서 볼 수 있게 최근 거절 10개를 남김
        $log = json_decode((string) gc('auto_rejects'), true);
        $log = array_slice(array_merge(array(array('at' => now(), 'title' => $title, 'errors' => $errors)), is_array($log) ? $log : array()), 0, 10);
        save_settings(array('gc_auto_rejects' => json_encode($log, JSON_UNESCAPED_UNICODE)));
        return array(false, $errors);
    }
    $photo = stock_pick($kind, $note ? (int) $note['id'] : 0);
    $id = (int) q_insert('blog_posts', array(
        'title' => $title, 'slug' => blog_slugify($title), 'summary' => $summary, 'body' => $body, 'format' => 'html',
        'cover' => $photo ? $photo['path'] : '', 'seo_title' => '', 'keywords' => $keywords,
        'status' => 'published', 'published_at' => $at, 'views' => 0, 'auto' => 1,
        'created_at' => now(), 'updated_at' => $at,
    ));
    if ($photo) {
        q('UPDATE photo_stock SET used_post_id = ?, used_at = ? WHERE id = ?', array($id, now(), (int) $photo['id']));
    }
    if ($note) {
        q('UPDATE experience_notes SET used_post_id = ?, used_at = ? WHERE id = ?', array($id, now(), (int) $note['id']));
    }
    save_settings(array('gc_auto_last' => json_encode(array('at' => now(), 'id' => $id, 'title' => $title, 'publish_at' => $at, 'photo' => (bool) $photo), JSON_UNESCAPED_UNICODE)));
    indexnow_schedule();
    return array(true, array('id' => $id, 'url' => blog_url(find_blog_post($id), true), 'publish_at' => $at, 'cover' => $photo ? $photo['path'] : ''));
}
