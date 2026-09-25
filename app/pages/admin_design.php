<?php
/**
 * 관리자 › 디자인: 로고(텍스트/이미지), 상단 영역(헤더)·본문 영역·하단 영역(푸터)의 배경색·글씨체·글씨 크기, 헤더 높이.
 * 관리자 › 디자인 › 글씨체: 기본 글씨체 말고 쓰고 싶은 글씨체 파일을 직접 올립니다.
 */

const DESIGN_FONT_KEYS = array('design_logo_font' => '로고', 'design_header_font' => '상단 메뉴', 'design_body_font' => '본문', 'design_heading_font' => '제목', 'design_footer_font' => '하단');

function admin_design()
{
    require_admin();
    $errors = array();
    $values = design();

    if (is_post() && !$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = '올린 파일이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요.';
    } elseif (is_post()) {
        require_csrf('/admin/design');
        if (input('action') === 'reset') {
            delete_public_file($values['design_logo_image']);
            save_settings(design_defaults());
            flash('디자인을 처음 모습으로 되돌렸어요.');
            redirect('/admin/design');
        }

        $new = array();
        foreach (design_defaults() as $key => $default) {
            $new[$key] = input($key, $values[$key]);
        }
        foreach (array('design_header_bg', 'design_main_bg', 'design_footer_bg') as $key) {
            $new[$key] = strtoupper($new[$key]);
            if (!preg_match('/^#[0-9A-F]{6}$/', $new[$key])) {
                $errors[] = '배경색은 #RRGGBB 형식으로 골라 주세요.';
                $new[$key] = $values[$key];
            }
        }
        // 본문에는 흰 카드와 어두운 글씨가 많아서, 글씨가 잘 보이도록 밝은 배경만 받습니다.
        if (color_luminance($new['design_main_bg']) < 0.55) {
            $errors[] = '본문 영역 배경은 글씨가 잘 보이도록 밝은 색으로 골라 주세요.';
        }
        foreach (array_keys(DESIGN_FONT_KEYS) as $key) {
            if (!array_key_exists($new[$key], design_fonts())) {
                $new[$key] = $values[$key];
            }
        }
        foreach (DESIGN_RANGES as $key => $range) {
            $new[$key] = (string) max($range[0], min($range[1], (int) $new[$key]));
        }
        $new['design_logo_type'] = $new['design_logo_type'] === 'image' ? 'image' : 'text';
        $new['design_logo_image'] = $values['design_logo_image'];

        if (!$errors) {
            try {
                if (has_upload('logo_image')) {
                    $path = store_image($_FILES['logo_image'], 'design');
                    delete_public_file($values['design_logo_image']);
                    $new['design_logo_image'] = $path;
                } elseif (input('remove_logo') === '1') {
                    delete_public_file($values['design_logo_image']);
                    $new['design_logo_image'] = '';
                }
            } catch (RuntimeException $e) {
                $errors[] = '로고 이미지: ' . $e->getMessage();
            }
        }
        if (!$errors && $new['design_logo_type'] === 'image' && $new['design_logo_image'] === '') {
            $errors[] = '이미지 로고를 쓰려면 로고 이미지를 올려 주세요.';
        }
        if (!$errors) {
            save_settings($new);
            flash('디자인을 저장했어요. 스토어에 바로 적용돼요.');
            redirect('/admin/design');
        }
        $values = array_merge($values, $new);
    }

    render_admin('design', array('title' => '디자인', 'nav' => 'design', 'values' => $values, 'errors' => $errors));
}

function admin_fonts()
{
    require_admin();
    $errors = array();
    $form = array('font_name' => '', 'font_kind' => 'sans-serif');

    if (is_post() && !$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = '올린 파일이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요.';
    } elseif (is_post()) {
        require_csrf('/admin/design/fonts');
        $form['font_name'] = str_cut(trim(preg_replace('/[\x00-\x1F<>"\'\\\\]+/u', '', input('font_name'))), 40, '');
        $form['font_kind'] = array_key_exists(input('font_kind'), FONT_KINDS) ? input('font_kind') : 'sans-serif';
        if ($form['font_name'] === '') {
            $errors[] = '글씨체 이름을 적어 주세요.';
        }
        if (!has_upload('font_regular')) {
            $errors[] = '글씨체 파일(보통 굵기)을 올려 주세요.';
        }
        if (input('license_ok') !== '1') {
            $errors[] = '글씨체를 웹사이트에서 쓸 수 있는 권리가 있는지 확인하고 체크해 주세요.';
        }
        $saved = array();
        if (!$errors) {
            try {
                $regular = store_font_file($_FILES['font_regular']);
                $saved[] = $regular[0];
                $bold = has_upload('font_bold') ? store_font_file($_FILES['font_bold']) : array('', 0);
                $saved[] = $bold[0];
                q_insert('fonts', array(
                    'name' => $form['font_name'],
                    'kind' => $form['font_kind'],
                    'file_regular' => $regular[0],
                    'file_bold' => $bold[0],
                    'size' => $regular[1] + $bold[1],
                    'created_at' => now(),
                ));
                flash('‘' . $form['font_name'] . '’ 글씨체를 추가했어요. 디자인 설정에서 고를 수 있어요.');
                redirect('/admin/design/fonts');
            } catch (RuntimeException $e) {
                foreach ($saved as $path) {
                    delete_public_file($path);
                }
                $errors[] = $e->getMessage();
            }
        }
    }

    render_admin('fonts', array('title' => '글씨체', 'nav' => 'design', 'fonts' => uploaded_fonts(), 'form' => $form, 'errors' => $errors));
}

function admin_font_delete($id)
{
    require_admin();
    require_csrf('/admin/design/fonts');
    $font = q_one('SELECT * FROM fonts WHERE id = ?', array((int) $id));
    if (!$font) {
        not_found();
    }
    // 이 글씨체를 쓰던 곳은 기본 글씨체로 돌려놓습니다.
    $defaults = design_defaults();
    $reset = array();
    foreach (array_keys(DESIGN_FONT_KEYS) as $key) {
        if (setting($key) === 'u' . (int) $font['id']) {
            $reset[$key] = $defaults[$key];
        }
    }
    if ($reset) {
        save_settings($reset);
    }
    q('DELETE FROM fonts WHERE id = ?', array((int) $font['id']));
    delete_public_file($font['file_regular']);
    delete_public_file($font['file_bold']);
    flash('‘' . $font['name'] . '’ 글씨체를 지웠어요.' . ($reset ? ' 이 글씨체를 쓰던 곳은 기본 글씨체로 바뀌었어요.' : ''));
    redirect('/admin/design/fonts');
}
