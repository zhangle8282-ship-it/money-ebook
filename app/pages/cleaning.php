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
        // 관리자
        array('GET|POST', '~^/admin/login$~', 'admin_login'),
        array('POST', '~^/admin/logout$~', 'admin_logout'),
        array('GET', '~^/admin(?:/settings)?$~', 'admin_cleaning_home'),
        array('GET', '~^/admin/inquiries$~', 'admin_inquiries'),
        array('POST', '~^/admin/inquiries/(\d+)$~', 'admin_inquiry_action'),
        array('GET|POST', '~^/admin/site$~', 'admin_cleaning_site'),
        array('GET|POST', '~^/admin/photos$~', 'admin_cleaning_photos'),
        array('GET|POST', '~^/admin/account$~', 'admin_account'),
    );
}

/* ───────── 공개 화면 ───────── */

function page_cleaning_home()
{
    // 화면 새로고침 없이 보내지 못했을 때(자바스크립트 꺼짐) 입력값과 오류를 한 번만 되살립니다.
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

function cleaning_robots()
{
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /admin\nSitemap: " . base_url() . "/sitemap.xml\n";
    exit;
}

function cleaning_sitemap()
{
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach (array('/', '/privacy') as $path) {
        echo '<url><loc>' . e(base_url() . $path) . '</loc></url>';
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
    q_update('inquiries', (int) $row['id'], array(
        'status' => array_key_exists(input('status'), INQUIRY_STATUS) ? input('status') : $row['status'],
        'memo' => str_cut(str_replace("\r\n", "\n", input('memo')), 1000, ''),
        'updated_at' => now(),
    ));
    flash($row['name'] . ' 문의를 저장했어요.');
    redirect($back);
}

const CLEANING_SITE_FIELDS = array(
    'gc_name', 'gc_phone', 'gc_kakao_url', 'gc_tagline', 'gc_area',
    'gc_owner', 'gc_biz_number', 'gc_biz_type', 'gc_biz_item', 'gc_address', 'gc_email', 'gc_notify_email',
);

function admin_cleaning_site()
{
    require_admin();
    $errors = array();
    $values = settings();
    if (is_post()) {
        require_csrf('/admin/site');
        foreach (CLEANING_SITE_FIELDS as $key) {
            $values[$key] = str_cut(trim(input($key)), 200, '');
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
        foreach (array('gc_email' => '이메일', 'gc_notify_email' => '알림 이메일') as $key => $label) {
            if ($values[$key] !== '' && !filter_var($values[$key], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $label . ' 주소를 확인해 주세요.';
            }
        }
        if (!$errors) {
            save_settings(array_intersect_key($values, array_flip(CLEANING_SITE_FIELDS)));
            flash('홈페이지 정보를 저장했어요. 바로 반영돼요.');
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
        if (!$errors) {
            flash('사진을 저장했어요. 홈페이지에 바로 보여요.');
            redirect('/admin/photos');
        }
    }
    render_admin('cleaning_photos', array('title' => '사진 관리', 'nav' => 'photos', 'photos' => $photos, 'errors' => $errors));
}

/** 관리자 비밀번호 바꾸기 */
function admin_account()
{
    $admin = require_admin();
    $errors = array();
    if (is_post()) {
        $row = q_one('SELECT * FROM admins WHERE id = ?', array($admin['id']));
        $new = input_raw('new_password');
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        } elseif (!password_verify(input_raw('current_password'), $row['password_hash'])) {
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
    render_admin('cleaning_account', array('title' => '계정', 'nav' => 'account', 'errors' => $errors));
}
