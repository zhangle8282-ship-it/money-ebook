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
        array('POST', '~^/admin/blog/upload$~', 'admin_blog_upload'),
        array('POST', '~^/admin/blog/paste$~', 'admin_blog_paste'),
        // 도급 정산
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
    render('cleaning/privacy', array('title' => '개인정보처리방침'), 'cleaning/simple');
}

/** robots.txt: 검색 로봇이 사이트맵 · RSS 주소를 스스로 찾아가게 적어 두고, 관리자 화면은 막습니다. */
function cleaning_robots()
{
    header('Content-Type: text/plain; charset=utf-8');
    $base = base_url();
    $out = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /inquiry\n\nSitemap: " . $base . "/sitemap.xml\n";
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

/** 관리자 계정: 내 비밀번호 바꾸기 · 관리자 추가 · 다른 관리자 지우기 */
function admin_account()
{
    $admin = require_admin();
    $errors = array();
    $created = array('username' => '');
    $form = input('form');
    if (is_post()) {
        $me = q_one('SELECT * FROM admins WHERE id = ?', array($admin['id']));
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        } elseif ($form === 'create') {
            // 새 관리자 추가: 내 비밀번호를 한 번 더 확인합니다.
            $username = trim(input('new_username'));
            $password = input_raw('new_admin_password');
            $created['username'] = $username;
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
                q_insert('admins', array('username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'created_at' => now()));
                flash('관리자 ‘' . $username . '’을(를) 만들었어요. 이 아이디와 비밀번호로 /admin 에 로그인할 수 있어요.');
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
        'admins' => q_all('SELECT id, username, created_at FROM admins ORDER BY id'),
    ));
}

/** 검색 등록: 사이트맵 · RSS 주소 안내, 검색 사이트 확인 코드, 바뀐 주소 바로 알리기(IndexNow) */
const SEARCH_SUBMIT_FIELDS = array('gc_naver_verify', 'gc_google_verify', 'gc_bing_verify', 'gc_daum_verify', 'gc_indexnow_on', 'gc_canonical_redirect');

function admin_search_submit()
{
    require_admin();
    $errors = array();
    $values = settings();
    if (is_post() && input('action') === 'ping_all') {
        require_csrf('/admin/search');
        if (gc('indexnow_on') !== '1') {
            flash('바로 알리기가 꺼져 있어요. 켜고 저장한 뒤 다시 눌러 주세요.', 'error');
        } else {
            $paths = array_map(function ($row) {
                return $row[0];
            }, sitemap_entries());
            $entry = indexnow_ping($paths, '모두 알리기', true);
            $ok = $entry && !empty($entry['codes']) && array_filter($entry['codes'], 'indexnow_ok');
            flash('사이트맵에 있는 주소 ' . count($paths) . '개를 보냈어요.' . indexnow_result_text($entry), $ok ? 'ok' : (isset($entry['skip']) ? 'info' : 'error'));
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
    ));
}
