<?php
/**
 * 그린청소 홈페이지: 첫 화면, 견적 문의 접수, 개인정보 안내, 관리자(견적 문의·홈페이지 정보·사진·계정).
 */

function cleaning_routes()
{
    return array(
        array('GET', '~^/health$~', 'page_health'),
        array('GET', '~^/$~', 'page_cleaning_home'),
        array('POST', '~^/inquiry$~', 'action_cleaning_inquiry'),
        array('GET', '~^/privacy$~', 'page_cleaning_privacy'),
        array('GET', '~^/robots\.txt$~', 'cleaning_robots'),
        array('GET', '~^/sitemap\.xml$~', 'cleaning_sitemap'),
        array('GET', '~^/rss\.xml$~', 'blog_rss'),
        array('GET', '~^/blog$~', 'page_blog_list'),
        array('GET', '~^/blog/(\d+)(?:-([^/]*))?$~', 'page_blog_post'),
        // IndexNow 주인 확인 파일(/{열쇠}.txt)
        array('GET', '~^/([a-f0-9]{32})\.txt$~', 'indexnow_key_file'),
        // 관리자
        array('GET|POST', '~^/admin/login$~', 'admin_login'),
        array('POST', '~^/admin/logout$~', 'admin_logout'),
        array('GET', '~^/admin(?:/settings)?$~', 'admin_cleaning_home'),
        array('GET', '~^/admin/inquiries$~', 'admin_inquiries'),
        array('POST', '~^/admin/inquiries/(\d+)$~', 'admin_inquiry_action'),
        array('GET|POST', '~^/admin/site$~', 'admin_cleaning_site'),
        array('GET|POST', '~^/admin/photos$~', 'admin_cleaning_photos'),
        array('GET|POST', '~^/admin/reviews$~', 'admin_cleaning_reviews'),
        // 블로그
        array('GET', '~^/admin/blog$~', 'admin_blog_list'),
        array('GET|POST', '~^/admin/blog/new$~', 'admin_blog_form'),
        array('GET|POST', '~^/admin/blog/(\d+)/edit$~', 'admin_blog_form'),
        array('POST', '~^/admin/blog/(\d+)/delete$~', 'admin_blog_delete'),
        array('POST', '~^/admin/blog/(\d+)/publish$~', 'admin_blog_publish'),
        array('POST', '~^/admin/blog/upload$~', 'admin_blog_upload'),
        array('POST', '~^/admin/blog/paste$~', 'admin_blog_paste'),
        array('GET|POST', '~^/admin/blog/auto$~', 'admin_autoblog'),
        array('GET|POST', '~^/admin/blog/stock$~', 'admin_stock'),
        array('GET|POST', '~^/admin/blog/notes$~', 'admin_notes'),
        // 블로그 자동 글쓰기: Claude 예약 작업이 쓰는 글 받는 통로(비밀 열쇠)
        array('GET', '~^/api/auto/plan$~', 'api_auto_plan'),
        array('POST', '~^/api/auto/post$~', 'api_auto_post'),
        // 정기청소 정산
        array('GET', '~^/admin/contracts$~', 'admin_contracts_month'),
        array('POST', '~^/admin/contracts/settle$~', 'admin_contracts_settle'),
        array('GET', '~^/admin/contracts/list$~', 'admin_contracts_list'),
        array('GET|POST', '~^/admin/contracts/roles$~', 'admin_contract_roles'),
        array('GET|POST', '~^/admin/contracts/partners$~', 'admin_partners'),
        array('GET|POST', '~^/admin/contracts/partners/(\d+)/edit$~', 'admin_partner_edit'),
        array('POST', '~^/admin/contracts/partners/(\d+)/delete$~', 'admin_partner_delete'),
        array('GET|POST', '~^/admin/contracts/new$~', 'admin_contract_form'),
        array('GET|POST', '~^/admin/contracts/(\d+)/edit$~', 'admin_contract_form'),
        array('POST', '~^/admin/contracts/(\d+)/delete$~', 'admin_contract_delete'),
        array('POST', '~^/admin/contracts/(\d+)/assign$~', 'admin_contract_assign'),
        array('GET|POST', '~^/admin/account$~', 'admin_account'),
        array('GET|POST', '~^/admin/code$~', 'admin_custom_code'),
        array('GET|POST', '~^/admin/search$~', 'admin_search_submit'),
        array('GET', '~^/admin/visits$~', 'admin_visits'),
        array('GET|POST', '~^/admin/usage$~', 'admin_usage'),
        // 일회성 정산
        array('GET', '~^/admin/onetime$~', 'admin_onetime_list'),
        array('GET', '~^/admin/onetime/jobs$~', 'admin_onetime_jobs'),
        array('GET|POST', '~^/admin/onetime/new$~', 'admin_onetime_form'),
        array('GET|POST', '~^/admin/onetime/(\d+)/edit$~', 'admin_onetime_form'),
        array('POST', '~^/admin/onetime/(\d+)/settle$~', 'admin_onetime_settle'),
        array('POST', '~^/admin/onetime/(\d+)/delete$~', 'admin_onetime_delete'),
        array('POST', '~^/admin/onetime/(\d+)/assign$~', 'admin_onetime_assign'),
        array('GET|POST', '~^/admin/onetime/roles$~', 'admin_onetime_roles'),
        // 인력 배치 정보
        array('GET', '~^/admin/workers$~', 'admin_workers'),
        array('GET|POST', '~^/admin/workers/new$~', 'admin_worker_form'),
        array('GET|POST', '~^/admin/workers/(\d+)/edit$~', 'admin_worker_form'),
        array('POST', '~^/admin/workers/(\d+)/delete$~', 'admin_worker_delete'),
        array('POST', '~^/admin/workers/(\d+)/assign$~', 'admin_worker_assign'),
        // 검색어 페이지
        array('GET', '~^/admin/pages$~', 'admin_landing_list'),
        array('GET|POST', '~^/admin/pages/new$~', 'admin_landing_form'),
        array('GET|POST', '~^/admin/pages/(\d+)/edit$~', 'admin_landing_form'),
        array('POST', '~^/admin/pages/(\d+)/delete$~', 'admin_landing_delete'),
        // 맨 끝: /음성공장청소 처럼 검색어 페이지(위 주소에 해당하지 않을 때만)
        array('GET', '~^/([^/.]+)$~', 'page_landing'),
    );
}

/* ───────── 공개 화면 ───────── */

function page_cleaning_home()
{
    // 화면 새로고침 없이 보내지 못했을 때(자바스크립트 꺼짐) 입력값과 오류를 한 번만 되살립니다.
    indexnow_due();
    visit_track();
    start_session();
    $form = $_SESSION['inquiry_form'] ?? null;
    unset($_SESSION['inquiry_form']);
    render('cleaning/home', array(
        'sent' => input('sent') === '1',
        'form' => $form ? $form['values'] : array('kind' => array_key_exists(input('kind'), CLEANING_KINDS) ? input('kind') : 'office', 'name' => '', 'phone' => '', 'address' => ''),
        'errors' => $form ? $form['errors'] : array(),
        'photos' => cleaning_photos(),
    ), null);
}

function action_cleaning_inquiry()
{
    $ajax = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    $done = function () use ($ajax) {
        if ($ajax) {
            json_out(array('ok' => true));
        }
        redirect('/?sent=1#quote');
    };
    $fail = function ($errors, $values) use ($ajax) {
        if ($ajax) {
            json_out(array('ok' => false, 'errors' => $errors), 422);
        }
        start_session();
        $_SESSION['inquiry_form'] = array('values' => $values, 'errors' => $errors);
        redirect('/#quote');
    };
    // 자동 등록 막기: 사람에게는 보이지 않는 칸(website)이 채워져 있으면 접수한 것처럼만 보여 줍니다.
    if (input('website') !== '') {
        $done();
    }
    list($v, $errors) = inquiry_from_request();
    if (!csrf_valid()) {
        $fail(array('form' => '보안 확인이 만료되었어요. 새로고침한 뒤 다시 보내 주세요.'), $v);
    }
    if ($errors) {
        $fail($errors, $v);
    }
    if (inquiry_rate_limited()) {
        $fail(array('form' => '문의가 이미 여러 번 접수됐어요. 급하시면 ' . gc('phone') . '로 전화 주세요.'), $v);
    }
    save_inquiry($v);
    $done();
}

function page_cleaning_privacy()
{
    visit_track();
    render('cleaning/privacy', array('title' => '개인정보처리방침'), 'cleaning/simple');
}

/** robots.txt: 검색 로봇이 사이트맵 · RSS 주소를 스스로 찾아가게 적어 두고, 관리자 화면은 막습니다. */
function cleaning_robots()
{
    header('Content-Type: text/plain; charset=utf-8');
    $base = base_url();
    $out = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /inquiry\nDisallow: /api\n" . bot_robots_lines() . "\nSitemap: " . $base . "/sitemap.xml\n";
    if (blog_has_posts()) {
        $out .= 'Sitemap: ' . $base . "/rss.xml\n";
    }
    // 다음 웹마스터도구 사이트 인증(맨 끝에 한 줄)
    $daum = daum_verify_line(gc('daum_verify'));
    if ($daum !== '') {
        $out .= "\n" . $daum . "\n";
    }
    echo $out;
    exit;
}

function cleaning_sitemap()
{
    indexnow_due();
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (sitemap_entries() as $u) {
        echo '<url><loc>' . e(base_url() . $u[0]) . '</loc>' . ($u[1] ? '<lastmod>' . e(date('c', strtotime($u[1]))) . '</lastmod>' : '') . "</url>\n";
    }
    echo '</urlset>';
    exit;
}

/* ───────── 관리자 ───────── */

function admin_cleaning_home()
{
    require_admin();
    redirect('/admin/inquiries');
}

function admin_inquiries()
{
    require_admin();
    $status = input('status');
    $where = array_key_exists($status, INQUIRY_STATUS) ? ' WHERE status = ?' : '';
    render_admin('cleaning_inquiries', array(
        'title' => '견적 문의', 'nav' => 'inquiries',
        'rows' => q_all('SELECT * FROM inquiries' . $where . ' ORDER BY id DESC LIMIT 500', $where ? array($status) : array()),
        'status' => $status,
        'counts' => inquiry_counts(),
    ));
}

function admin_inquiry_action($id)
{
    require_admin();
    $back = safe_back(input('back'), '/admin/inquiries');
    require_csrf($back);
    $row = q_one('SELECT * FROM inquiries WHERE id = ?', array((int) $id));
    if (!$row) {
        not_found();
    }
    if (input('action') === 'delete') {
        q('DELETE FROM inquiries WHERE id = ?', array((int) $row['id']));
        flash($row['name'] . ' 문의를 지웠어요.');
        redirect($back);
    }
    $status = array_key_exists(input('status'), INQUIRY_STATUS) ? input('status') : $row['status'];
    q_update('inquiries', (int) $row['id'], array(
        'status' => $status,
        'memo' => str_cut(str_replace("\r\n", "\n", input('memo')), 1000, ''),
        'updated_at' => now(),
    ));
    flash($row['name'] . ' 문의를 저장했어요.' . ($row['status'] === 'new' && $status !== 'new' ? ' 처리한 문의라 ‘새 문의’에서 뺐어요.' : ''));
    redirect($back);
}

const CLEANING_SITE_FIELDS = array(
    'gc_name', 'gc_phone', 'gc_kakao_url', 'gc_channeltalk_key', 'gc_tagline', 'gc_area',
    'gc_seo_title', 'gc_seo_desc', 'gc_seo_keywords',
    'gc_owner', 'gc_biz_number', 'gc_biz_type', 'gc_biz_item', 'gc_address', 'gc_email', 'gc_notify_email',
);

function admin_cleaning_site()
{
    require_admin();
    $errors = array();
    $values = settings();
    if (is_post() && strncmp(input('action'), 'tg_', 3) === 0) {
        require_csrf('/admin/site');
        admin_telegram_action(input('action'));
    }
    if (is_post() && input('action') === 'test_mail') {
        require_csrf('/admin/site');
        $text = "홈페이지 견적 문의 알림 메일이 잘 오는지 확인하는 시험 메일이에요.\n\n새 문의가 들어오면 이 주소로 알려 드려요.\n" . site_base_url() . "/admin/inquiries\n";
        $html = cleaning_mail_html(array(
            'label' => '알림 메일 시험',
            'preheader' => '견적 문의 알림 메일이 잘 오는지 확인하는 시험 메일이에요.',
            'badge' => '시험 메일',
            'title' => '알림 메일이 잘 도착했어요',
            'intro' => '홈페이지로 견적 문의가 들어오면 이런 모양의 메일로 바로 알려 드려요. 스팸함에 들어갔다면 ‘스팸 아님’으로 옮겨 주세요.',
            'rows' => array(array('받는 주소', e(gc('notify_email'))), array('보낸 시각', e(date('Y.m.d H:i')))),
            'buttons' => array(array('관리자 화면 열기', site_base_url() . '/admin/inquiries', true)),
            'note' => gc('name') . ' 관리자 화면 › 홈페이지 관리에서 보낸 시험 메일이에요.',
        ));
        $result = send_notice_mail('[' . gc('name') . '] 알림 메일 시험', $text, $html);
        $messages = array(
            'sent' => gc('notify_email') . '로 시험 메일을 보냈어요. 몇 분 안에 안 오면 스팸함도 확인해 주세요.',
            'failed' => '서버에서 메일을 보내지 못했어요. 견적 문의는 관리자 화면에서 그대로 확인할 수 있어요.',
            'off' => '알림 받을 이메일을 먼저 저장해 주세요.',
        );
        flash($messages[$result], $result === 'sent' ? 'ok' : 'error');
        redirect('/admin/site#notify');
    }
    if (is_post()) {
        require_csrf('/admin/site');
        foreach (CLEANING_SITE_FIELDS as $key) {
            $values[$key] = str_cut(trim(preg_replace('/\s+/u', ' ', input($key))), $key === 'gc_seo_desc' || $key === 'gc_seo_keywords' ? 300 : 200, '');
        }
        if ($values['gc_seo_title'] === '') {
            $errors[] = '검색 제목을 적어 주세요.';
        }
        if ($values['gc_name'] === '') {
            $errors[] = '업체 이름을 적어 주세요.';
        }
        $digits = preg_replace('/[^0-9]/', '', $values['gc_phone']);
        if (strlen($digits) < 9 || strlen($digits) > 12) {
            $errors[] = '대표 전화번호를 적어 주세요.';
        }
        if ($values['gc_kakao_url'] !== '' && !preg_match('~^https?://[^\s<>"]+$~i', $values['gc_kakao_url'])) {
            $errors[] = '카카오톡 채널 주소는 http:// 또는 https:// 로 시작하게 넣어 주세요.';
        }
        if ($values['gc_channeltalk_key'] !== '' && !preg_match('/^[A-Za-z0-9-]{8,64}$/', $values['gc_channeltalk_key'])) {
            $errors[] = '채널톡 플러그인 키는 영문·숫자·하이픈(-)으로 된 값을 그대로 붙여 넣어 주세요.';
        }
        foreach (array('gc_email' => '이메일', 'gc_notify_email' => '알림 이메일') as $key => $label) {
            if ($values[$key] !== '' && !filter_var($values[$key], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $label . ' 주소를 확인해 주세요.';
            }
        }
        if (!$errors) {
            $fields = array_flip(CLEANING_SITE_FIELDS);
            $changed = array_intersect_key(settings(), $fields) != array_intersect_key($values, $fields);
            save_settings(array_intersect_key($values, $fields));
            flash('홈페이지 정보를 저장했어요. 바로 반영돼요.' . ($changed ? indexnow_result_text(home_changed('홈페이지 정보 수정')) : ''));
            redirect('/admin/site');
        }
    }
    render_admin('cleaning_site', array('title' => '홈페이지 관리', 'nav' => 'site', 'values' => $values, 'errors' => $errors));
}

function admin_cleaning_photos()
{
    require_admin();
    $errors = array();
    $photos = cleaning_photos();
    if (is_post() && !$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = '한 번에 올린 사진이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요. 몇 장씩 나눠 올려 주세요.';
    } elseif (is_post()) {
        require_csrf('/admin/photos');
        // 칸 하나: 지우기 체크 → 새 사진이 있으면 바꾸기
        $slot = function ($field, $current, $label) use (&$errors) {
            if (input('remove_' . $field) === '1') {
                delete_public_file($current);
                $current = '';
            }
            if (has_upload($field)) {
                try {
                    $path = store_image($_FILES[$field], 'site');
                    delete_public_file($current);
                    $current = $path;
                } catch (RuntimeException $e) {
                    $errors[] = $label . ': ' . $e->getMessage();
                }
            }
            return $current;
        };
        for ($i = 0; $i < CLEANING_SITE_PHOTOS; $i++) {
            $photos['site'][$i] = $slot('site_' . $i, $photos['site'][$i], '작업 현장 사진 ' . ($i + 1));
        }
        for ($i = 0; $i < CLEANING_BA_PAIRS; $i++) {
            $title = str_cut(trim(input('ba_title_' . $i)), 40, '');
            if ($title !== '') {
                $photos['ba'][$i]['title'] = $title;
            }
            $photos['ba'][$i]['before'] = $slot('ba_before_' . $i, $photos['ba'][$i]['before'], '청소 전후 ' . ($i + 1) . ' · 청소 전');
            $photos['ba'][$i]['after'] = $slot('ba_after_' . $i, $photos['ba'][$i]['after'], '청소 전후 ' . ($i + 1) . ' · 청소 후');
        }
        $photos['map'] = $slot('map', $photos['map'], '서비스 지역 지도');
        save_cleaning_photos($photos);
        $pinged = home_changed('사진 수정');
        if (!$errors) {
            flash('사진을 저장했어요. 홈페이지에 바로 보여요.' . indexnow_result_text($pinged));
            redirect('/admin/photos');
        }
    }
    render_admin('cleaning_photos', array('title' => '사진 관리', 'nav' => 'photos', 'photos' => $photos, 'errors' => $errors));
}

/** 고객 후기 고치기(최대 6개). 글이 빈 칸은 빠집니다. */
function admin_cleaning_reviews()
{
    require_admin();
    $errors = array();
    $reviews = cleaning_reviews();
    if (is_post()) {
        require_csrf('/admin/reviews');
        $reviews = array();
        for ($i = 0; $i < CLEANING_REVIEW_MAX; $i++) {
            $text = trim(str_replace(array("\r\n", "\n"), ' ', input('review_text_' . $i)));
            $who = trim(input('review_who_' . $i));
            if ($text === '') {
                continue;
            }
            if (str_len($text) > 200) {
                $errors[] = '후기 ' . ($i + 1) . '은(는) 200자 이내로 줄여 주세요.';
            }
            $reviews[] = array(str_cut($text, 200, ''), str_cut(mask_reviewer($who), 20, ''));
        }
        if (!$errors) {
            save_settings(array('gc_reviews' => json_encode($reviews, JSON_UNESCAPED_UNICODE)));
            $pinged = home_changed('후기 수정');
            flash(($reviews ? '후기를 저장했어요. 홈페이지에 바로 반영돼요.' : '후기를 모두 비웠어요. 홈페이지에서 고객 후기 구역이 숨겨져요.') . indexnow_result_text($pinged));
            redirect('/admin/reviews');
        }
    }
    render_admin('cleaning_reviews', array('title' => '후기 관리', 'nav' => 'reviews', 'reviews' => $reviews, 'errors' => $errors));
}

/**
 * 헤드 코드: 공개 화면 <head>(와 </body> 앞)에 넣을 코드.
 * 카페24 웹 방화벽이 <script> 가 든 요청을 막을 수 있어서, 화면에서 base64 로 감싸 보내고 여기서 풉니다.
 */
function admin_custom_code()
{
    require_admin();
    $errors = array();
    $values = array('head' => gc('head_code'), 'body' => gc('body_code'), 'enabled' => gc('code_enabled') !== '0');
    if (is_post()) {
        require_csrf('/admin/code');
        $decode = function ($field) {
            $b64 = input_raw($field . '_b64');
            if ($b64 !== '') {
                $raw = base64_decode($b64, true);
                return $raw === false ? null : $raw;
            }
            return input_raw($field);
        };
        $head = $decode('head_code');
        $body = $decode('body_code');
        if ($head === null || $body === null) {
            $errors[] = '코드를 받지 못했어요. 새로고침한 뒤 다시 저장해 주세요.';
        } else {
            $head = str_replace("\r\n", "\n", $head);
            $body = str_replace("\r\n", "\n", $body);
            $values = array('head' => $head, 'body' => $body, 'enabled' => input('enabled') === '1');
            foreach (array('head' => '헤드 코드', 'body' => '본문 끝 코드') as $k => $label) {
                if (strlen($values[$k]) > 50000) {
                    $errors[] = $label . '가 너무 길어요(50,000자까지).';
                }
                if (preg_match('~</?(head|body|html)[\s>]~i', $values[$k])) {
                    $errors[] = $label . '에는 <head>, <body>, <html> 태그 자체는 넣지 말고, 그 안에 들어갈 코드만 넣어 주세요.';
                }
            }
        }
        if (!$errors) {
            save_settings(array('gc_head_code' => $values['head'], 'gc_body_code' => $values['body'], 'gc_code_enabled' => $values['enabled'] ? '1' : '0'));
            flash($values['enabled'] ? '코드를 저장했어요. 홈페이지 모든 화면에 바로 들어가요.' : '코드를 저장했어요. 지금은 꺼 두어서 홈페이지에는 들어가지 않아요.');
            redirect('/admin/code');
        }
    }
    render_admin('custom_code', array('title' => '헤드 코드', 'nav' => 'code', 'values' => $values, 'errors' => $errors));
}

/** 관리자 계정: 내 비밀번호 바꾸기 · 관리자 추가 · 다른 관리자 지우기 · 권한(전체 / 업무만). ‘업무만’ 관리자는 내 비밀번호만 */
function admin_account()
{
    $admin = require_admin();
    $full = $admin['access'] !== 'work';
    $errors = array();
    $created = array('username' => '', 'access' => 'work');
    $form = input('form');
    if (is_post()) {
        $me = q_one('SELECT * FROM admins WHERE id = ?', array($admin['id']));
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        } elseif (!$full && in_array($form, array('create', 'delete', 'access'), true)) {
            $errors[] = '관리자 추가 · 지우기 · 권한 바꾸기는 전체 권한 관리자만 할 수 있어요.';
        } elseif ($form === 'access') {
            $target = q_one('SELECT * FROM admins WHERE id = ?', array(input_int('admin_id')));
            $access = input('access') === 'work' ? 'work' : 'all';
            if (!$target) {
                $errors[] = '관리자를 찾을 수 없어요.';
            } elseif ((int) $target['id'] === (int) $admin['id']) {
                $errors[] = '내 계정의 권한은 바꿀 수 없어요. 다른 전체 권한 관리자에게 부탁해 주세요.';
            } else {
                q('UPDATE admins SET access = ? WHERE id = ?', array($access, (int) $target['id']));
                flash('‘' . $target['username'] . '’의 권한을 ‘' . ADMIN_ACCESS[$access] . '’(으)로 바꿨어요.');
                redirect('/admin/account');
            }
        } elseif ($form === 'create') {
            // 새 관리자 추가: 내 비밀번호를 한 번 더 확인합니다.
            $username = trim(input('new_username'));
            $password = input_raw('new_admin_password');
            $created['username'] = $username;
            $created['access'] = input('access') === 'all' ? 'all' : 'work';
            if (!password_verify(input_raw('my_password'), $me['password_hash'])) {
                $errors[] = '지금 내 비밀번호가 맞지 않아요.';
            } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
                $errors[] = '새 아이디는 영문·숫자(그리고 _ . -) 3~30자로 정해 주세요.';
            } elseif (q_value('SELECT COUNT(*) FROM admins WHERE LOWER(username) = LOWER(?)', array($username))) {
                $errors[] = '‘' . $username . '’은(는) 이미 있는 아이디예요.';
            } elseif (strlen($password) < 10) {
                $errors[] = '새 관리자 비밀번호는 10자 이상으로 정해 주세요.';
            } elseif ($password !== input_raw('new_admin_password2')) {
                $errors[] = '새 관리자 비밀번호 확인이 일치하지 않아요.';
            } else {
                q_insert('admins', array('username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'access' => $created['access'], 'created_at' => now()));
                flash('관리자 ‘' . $username . '’(' . ADMIN_ACCESS[$created['access']] . ')을(를) 만들었어요. 이 아이디와 비밀번호로 /admin 에 로그인할 수 있어요.');
                redirect('/admin/account');
            }
        } elseif ($form === 'delete') {
            $target = q_one('SELECT * FROM admins WHERE id = ?', array(input_int('admin_id')));
            if (!$target) {
                $errors[] = '관리자를 찾을 수 없어요.';
            } elseif ((int) $target['id'] === (int) $admin['id']) {
                $errors[] = '지금 로그인한 내 계정은 지울 수 없어요.';
            } elseif ((int) q_value('SELECT COUNT(*) FROM admins') <= 1) {
                $errors[] = '관리자는 한 명 이상 있어야 해요.';
            } else {
                q('DELETE FROM admins WHERE id = ?', array((int) $target['id']));
                flash('관리자 ‘' . $target['username'] . '’을(를) 지웠어요. 그 아이디로 로그인해 있던 화면도 바로 풀려요.');
                redirect('/admin/account');
            }
        } else {
            $new = input_raw('new_password');
            if (!password_verify(input_raw('current_password'), $me['password_hash'])) {
                $errors[] = '지금 비밀번호가 맞지 않아요.';
            } elseif (strlen($new) < 10) {
                $errors[] = '새 비밀번호는 10자 이상으로 정해 주세요.';
            } elseif ($new !== input_raw('new_password2')) {
                $errors[] = '새 비밀번호 확인이 일치하지 않아요.';
            } else {
                q('UPDATE admins SET password_hash = ? WHERE id = ?', array(password_hash($new, PASSWORD_DEFAULT), $admin['id']));
                login_admin(q_one('SELECT * FROM admins WHERE id = ?', array($admin['id'])));
                flash('비밀번호를 바꿨어요.');
                redirect('/admin/account');
            }
        }
    }
    render_admin('cleaning_account', array(
        'title' => '계정', 'nav' => 'account', 'errors' => $errors, 'form' => $form, 'created' => $created,
        'admins' => q_all('SELECT id, username, access, created_at FROM admins ORDER BY id'), 'full' => $full,
    ));
}

/** 용량 · 트래픽: 서버 공간을 종류별로(색 · 퍼센트), 첫 화면 무게 · 추정 트래픽, 무거운 파일 */
function admin_usage()
{
    require_admin();
    if (is_post()) {
        require_csrf('/admin/usage');
        $num = function ($k) {
            return min(100000000, (int) preg_replace('/[^0-9]/', '', input($k)));
        };
        save_settings(array(
            'gc_host_plan' => str_cut(trim(input('plan')), 40, ''),
            'gc_host_quota_mb' => (string) $num('quota_mb'),
            'gc_host_traffic_mb' => (string) $num('traffic_mb'),
        ));
        flash('카페24 상품 정보를 저장했어요.');
        redirect('/admin/usage');
    }
    $visits = (int) q_value('SELECT SUM(hits) FROM visit_sources WHERE day >= ?', array(date('Y-m-d', strtotime('-29 days'))));
    render_admin('usage', array(
        'title' => '용량 · 트래픽', 'nav' => 'usage',
        'u' => usage_cached(input('fresh') === '1'),
        'plan' => gc('host_plan'),
        'quota' => (int) gc('host_quota_mb'),
        'trafficQuota' => (int) gc('host_traffic_mb'),
        'home' => traffic_home_weight(),
        'visits' => $visits,
        'heavy' => usage_heaviest(),
        'today' => server_stat_report(date('Y-m-d')),
        'month' => server_stat_report(date('Y-m-d', strtotime('-29 days'))),
    ));
}

/** 유입 경로: 어느 검색 사이트에서 어떤 검색어로 들어왔는지(기간별) */
function admin_visits()
{
    require_admin();
    $days = (int) input('days', '7');
    $days = isset(VISIT_PERIODS[$days]) ? $days : 7;
    render_admin('visits', array('title' => '유입 경로', 'nav' => 'visits', 'days' => $days, 'r' => visit_report($days)));
}

/** 검색 등록: 사이트맵 · RSS 주소 안내, 검색 사이트 확인 코드, 바뀐 주소 바로 알리기(IndexNow) */
const SEARCH_SUBMIT_FIELDS = array('gc_naver_verify', 'gc_google_verify', 'gc_bing_verify', 'gc_daum_verify', 'gc_indexnow_on', 'gc_canonical_redirect');

function admin_search_submit()
{
    require_admin();
    $errors = array();
    $values = settings();
    if (is_post() && in_array(input('action'), array('bots', 'bots_recommend'), true)) {
        require_csrf('/admin/search');
        $rules = array();
        foreach (BOTS as $k => $b) {
            $pick = input('action') === 'bots_recommend' ? $b[5] : (input('bot_' . $k) === 'block' ? 'block' : 'allow');
            if ($pick === 'block') {
                $rules[$k] = 'block';
            }
        }
        save_settings(array('gc_bot_rules' => json_encode($rules)));
        redirect('/admin/search?saved=' . (input('action') === 'bots_recommend' ? 'bots_recommend' : 'bots') . '#bots');
    }
    if (is_post() && input('action') === 'ping_all') {
        require_csrf('/admin/search');
        $last = null;
        foreach (indexnow_log() as $row) {
            if (($row['why'] ?? '') === '모두 알리기' && !isset($row['skip'])) {
                $last = $row['at'];
                break;
            }
        }
        if (gc('indexnow_on') !== '1') {
            flash('바로 알리기가 꺼져 있어요. 켜고 저장한 뒤 다시 눌러 주세요.', 'error');
        } elseif ($last !== null && time() - strtotime($last) < INDEXNOW_ALL_GAP) {
            // 같은 주소를 자주 보내면 검색 사이트가 무시하거나 스팸으로 볼 수 있어요.
            flash('‘모두 알리기’는 ' . date('H:i', strtotime($last)) . '에 이미 보냈어요. 같은 주소를 자주 보내면 검색 사이트가 무시할 수 있어서 '
                . date('H:i', strtotime($last) + INDEXNOW_ALL_GAP) . '부터 다시 보낼 수 있어요. 새 글이나 바뀐 내용은 그때그때 자동으로 알려요.', 'info');
        } else {
            $paths = array_map(function ($row) {
                return $row[0];
            }, sitemap_entries());
            $entry = indexnow_ping($paths, '모두 알리기', true);
            $ok = $entry && !empty($entry['codes']) && array_filter($entry['codes'], 'indexnow_ok');
            flash('사이트맵에 있는 주소 ' . count($paths) . '개를 보냈어요.' . indexnow_result_text($entry)
                . ($ok ? ' 검색 결과에 반영되는 건 검색 사이트가 정하고 보통 며칠~2주 걸려요. 여러 번 누를 필요는 없어요.' : ''), $ok ? 'ok' : (isset($entry['skip']) ? 'info' : 'error'));
        }
        redirect('/admin/search#indexnow');
    }
    if (is_post()) {
        require_csrf('/admin/search');
        // 사이트 확인 코드: 태그를 통째로 붙여 넣어도 값만 저장
        foreach (array('gc_naver_verify', 'gc_google_verify', 'gc_bing_verify') as $key) {
            $values[$key] = verify_code(str_cut(trim(input($key)), 300, ''));
        }
        $daum = trim(str_cut(input('gc_daum_verify'), 300, ''));
        $values['gc_daum_verify'] = daum_verify_line($daum);
        if ($daum !== '' && $values['gc_daum_verify'] === '') {
            $errors[] = '다음 웹마스터도구 인증 줄은 #DaumWebMasterTool: 로 시작하는 한 줄을 그대로 붙여 넣어 주세요.';
            $values['gc_daum_verify'] = $daum;
        }
        $values['gc_indexnow_on'] = input('indexnow_on') === '1' ? '1' : '0';
        $values['gc_canonical_redirect'] = input('canonical_redirect') === '1' ? '1' : '0';
        if (!$errors) {
            save_settings(array_intersect_key($values, array_flip(SEARCH_SUBMIT_FIELDS)));
            flash('저장했어요. 확인 코드는 홈페이지에 바로 들어가요. 이제 검색 사이트 화면에서 ‘소유 확인’을 눌러 주세요.');
            redirect('/admin/search');
        }
    }
    render_admin('search_submit', array(
        'title' => '검색 등록', 'nav' => 'search', 'values' => $values, 'errors' => $errors,
        'base' => base_url(),
        'key' => indexnow_key(),
        'entries' => count(sitemap_entries()),
        'posts' => (int) q_value('SELECT COUNT(*) FROM blog_posts WHERE ' . blog_public_sql(), array(now())),
        'log' => array_slice(indexnow_log(), 0, INDEXNOW_LOG_MAX),
        'local' => !indexnow_public_host(),
        'googleChecks' => google_seo_checks(),
        'botRules' => bot_rules(),
        'googleTargets' => google_index_targets(),
    ));
}

/** 홈페이지 관리 › 텔레그램 알림: 연결 확인 · 시험 메시지 · 켜기/끄기 · 연결 끊기 */
function admin_telegram_action($action)
{
    $back = '/admin/site#telegram';
    // 실패 이유는 텔레그램 칸 안에도 남겨 둡니다(위쪽 안내는 아래로 내려가면 안 보여서).
    $fail = function ($text) use ($back) {
        save_settings(array('gc_tg_last' => json_encode(array('at' => now(), 'what' => '연결 확인', 'ok' => false, 'error' => $text), JSON_UNESCAPED_UNICODE)));
        flash($text, 'error');
        redirect($back);
    };
    if ($action === 'tg_check') {
        save_settings(array('gc_tg_check' => json_encode(array('at' => now(), 'lines' => telegram_server_check()), JSON_UNESCAPED_UNICODE)));
        flash('서버 연결 점검을 마쳤어요. 텔레그램 알림 칸에서 결과를 보세요.');
        redirect($back);
    }
    if ($action === 'tg_connect') {
        $token = input('tg_token') !== '' ? preg_replace('/\s+/', '', input('tg_token')) : gc('tg_token');
        if (!telegram_token_ok($token)) {
            $fail('봇 토큰 모양이 아니에요. @BotFather 가 준 ‘숫자:영문’ 모양의 토큰을 그대로 붙여 넣어 주세요.');
        }
        $me = telegram_call($token, 'getMe');
        if (!$me['ok']) {
            $fail(telegram_error_text($me));
        }
        $bot = (string) ($me['result']['username'] ?? '');
        save_settings(array('gc_tg_token' => $token, 'gc_tg_bot' => $bot));
        list($chat, $r) = telegram_find_chat($token);
        if (!$chat) {
            $fail($r['ok'] ? '봇(@' . $bot . ')은 확인했어요. 이제 텔레그램에서 이 봇에게 아무 말이나 한 번 보내고(단체방이면 봇을 초대한 뒤 한마디) 다시 ‘연결 확인’을 눌러 주세요.' : telegram_error_text($r));
        }
        // 받는 대화방 목록에 더함(이미 있으면 그대로) — 다른 사람 · 단체방은 ‘받는 사람 찾기’로 더 추가
        $list = telegram_chats();
        if (!in_array($chat[0], array_column($list, 'id'), true)) {
            $list[] = array('id' => $chat[0], 'title' => str_cut($chat[1], 60, ''), 'type' => 'private');
            telegram_save_chats($list);
        }
        save_settings(array('gc_tg_on' => '1'));
        $sent = telegram_send('✅ ' . gc('name') . ' 홈페이지와 연결됐어요. 새 견적 문의가 들어오면 여기로 바로 알려 드릴게요.', $token, $chat[0]);
        telegram_remember($sent, '연결 확인');
        flash($sent['ok'] ? '텔레그램 ‘' . $chat[1] . '’ 대화방과 연결했어요. 시험 메시지가 도착했는지 확인해 보세요.' : telegram_error_text($sent), $sent['ok'] ? 'ok' : 'error');
        redirect($back);
    }
    if ($action === 'tg_test') {
        if (!telegram_token_ok(gc('tg_token')) || !telegram_chats()) {
            flash('먼저 텔레그램을 연결해 주세요.', 'error');
            redirect($back);
        }
        $sent = telegram_send('🔔 알림 시험이에요. 새 견적 문의가 들어오면 이렇게 알려 드려요.' . "\n" . site_base_url() . '/admin/inquiries');
        telegram_remember($sent, '시험 메시지');
        if ($sent['ok']) {
            flash('받는 대화방 ' . $sent['total'] . '곳에 시험 메시지를 보냈어요. 텔레그램을 확인해 보세요.');
        } else {
            $why = array();
            foreach ($sent['failed'] as $title => $reason) {
                $why[] = $title . ': ' . $reason;
            }
            flash($sent['sent'] . '/' . $sent['total'] . '곳에 보냈어요. 못 보낸 곳 — ' . implode(' / ', $why), 'error');
        }
        redirect($back);
    }
    if ($action === 'tg_find') {
        // 봇에게 말을 걸었거나 봇을 초대한 대화방 중 아직 받는 곳으로 추가하지 않은 곳 찾기
        if (!telegram_token_ok(gc('tg_token'))) {
            flash('먼저 봇 토큰을 넣고 연결해 주세요.', 'error');
            redirect($back);
        }
        list($chats, $r) = telegram_updates_chats(gc('tg_token'));
        if (!$r['ok']) {
            flash(telegram_error_text($r), 'error');
            redirect($back);
        }
        $have = array_column(telegram_chats(), 'id');
        $new = array_values(array_filter($chats, function ($c) use ($have) {
            return !in_array($c['id'], $have, true);
        }));
        save_settings(array('gc_tg_candidates' => json_encode(array('at' => now(), 'list' => $new), JSON_UNESCAPED_UNICODE)));
        flash($new ? count($new) . '곳을 찾았어요. 텔레그램 알림 칸에서 ‘추가’를 눌러 주세요.' : '새로 찾은 곳이 없어요. 받을 분이 텔레그램에서 봇(@' . gc('tg_bot') . ')을 찾아 ‘시작’을 누른 뒤 다시 찾아 주세요. 단체방은 봇을 초대하고 /start 를 보내 주세요.', $new ? 'ok' : 'error');
        redirect($back);
    }
    if ($action === 'tg_add') {
        $id = input('chat_id');
        $cand = telegram_candidates();
        $pick = null;
        foreach ($cand['list'] as $c) {
            if ((string) $c['id'] === $id) {
                $pick = $c;
            }
        }
        if (!$pick) {
            flash('그 대화방을 찾지 못했어요. ‘받는 사람 찾기’를 다시 눌러 주세요.', 'error');
            redirect($back);
        }
        $list = telegram_chats();
        $list[] = $pick;
        telegram_save_chats($list);
        save_settings(array('gc_tg_candidates' => json_encode(array('at' => $cand['at'], 'list' => array_values(array_filter($cand['list'], function ($c) use ($id) {
            return (string) $c['id'] !== $id;
        }))), JSON_UNESCAPED_UNICODE)));
        $sent = telegram_send('✅ ' . gc('name') . ' 견적 문의 알림을 이 대화방에서도 받아요.', gc('tg_token'), $pick['id']);
        if (!empty($sent['migrated'])) {
            // 단체방이 큰 단체방으로 바뀌어 번호가 달라진 경우 새 번호로 저장
            foreach ($list as $i => $c) {
                if ((string) $c['id'] === (string) $pick['id']) {
                    $list[$i]['id'] = $sent['migrated'];
                }
            }
            telegram_save_chats($list);
        }
        flash('‘' . $pick['title'] . '’을(를) 받는 곳에 추가했어요.' . ($sent['ok'] ? ' 안내 메시지를 보냈어요.' : ' 다만 메시지를 보내지 못했어요: ' . telegram_error_text($sent)), $sent['ok'] ? 'ok' : 'error');
        redirect($back);
    }
    if ($action === 'tg_remove') {
        $id = input('chat_id');
        $list = telegram_chats();
        $left = array_values(array_filter($list, function ($c) use ($id) {
            return (string) $c['id'] !== $id;
        }));
        telegram_save_chats($left);
        flash(count($left) < count($list) ? '받는 곳에서 뺐어요.' . (!$left ? ' 받는 곳이 없어서 알림이 가지 않아요.' : '') : '이미 빠져 있어요.');
        redirect($back);
    }
    if ($action === 'tg_toggle') {
        $on = gc('tg_on') === '1' ? '0' : '1';
        save_settings(array('gc_tg_on' => $on));
        flash($on === '1' ? '텔레그램 알림을 켰어요.' : '텔레그램 알림을 껐어요. 연결은 그대로 남아 있어요.');
        redirect($back);
    }
    if ($action === 'tg_clear') {
        save_settings(array('gc_tg_token' => '', 'gc_tg_bot' => '', 'gc_tg_chat' => '', 'gc_tg_chat_title' => '', 'gc_tg_chats' => '', 'gc_tg_candidates' => '', 'gc_tg_last' => ''));
        flash('텔레그램 연결을 끊고 토큰을 지웠어요.');
        redirect($back);
    }
    redirect($back);
}
