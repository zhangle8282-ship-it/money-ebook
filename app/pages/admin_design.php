<?php
/**
 * 관리자 › 디자인: 로고(텍스트/이미지), 상단 영역(헤더)·본문 영역·하단 영역(푸터)의 배경색·글씨체·글씨 크기, 헤더 높이.
 */

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
        foreach (array('design_logo_font', 'design_header_font', 'design_body_font', 'design_heading_font', 'design_footer_font') as $key) {
            if (!array_key_exists($new[$key], DESIGN_FONTS)) {
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
