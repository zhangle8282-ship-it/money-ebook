<?php
/**
 * 디자인 설정: 상단 영역(헤더)·본문 영역·하단 영역(푸터)의 배경색·글씨체·글씨 크기, 헤더 높이, 로고(텍스트/이미지).
 * 관리자 › 디자인에서 바꾸고, 공개 화면에 CSS 변수로 적용합니다.
 */

// 고를 수 있는 글씨체: 키 => [표시 이름, CSS 글꼴 이름, 구글 폰트 주소 조각, 대체 글꼴]
const DESIGN_FONTS = array(
    'plex' => array('IBM Plex Sans KR (기본 고딕)', 'IBM Plex Sans KR', 'IBM+Plex+Sans+KR:wght@400;500;600;700', 'sans-serif'),
    'noto-sans' => array('본고딕 (Noto Sans KR)', 'Noto Sans KR', 'Noto+Sans+KR:wght@400;500;700', 'sans-serif'),
    'noto-serif' => array('본명조 (Noto Serif KR)', 'Noto Serif KR', 'Noto+Serif+KR:wght@400;600;700', 'serif'),
    'nanum-gothic' => array('나눔고딕', 'Nanum Gothic', 'Nanum+Gothic:wght@400;700;800', 'sans-serif'),
    'nanum-myeongjo' => array('나눔명조', 'Nanum Myeongjo', 'Nanum+Myeongjo:wght@400;700;800', 'serif'),
    'gowun-dodum' => array('고운돋움', 'Gowun Dodum', 'Gowun+Dodum', 'sans-serif'),
    'gowun-batang' => array('고운바탕', 'Gowun Batang', 'Gowun+Batang:wght@400;700', 'serif'),
    'black-han-sans' => array('검은고딕 (굵은 제목용)', 'Black Han Sans', 'Black+Han+Sans', 'sans-serif'),
    'do-hyeon' => array('도현', 'Do Hyeon', 'Do+Hyeon', 'sans-serif'),
    'jua' => array('주아', 'Jua', 'Jua', 'sans-serif'),
    'system' => array('기기 기본 글꼴', '', '', 'system-ui'),
);

// 숫자 설정의 허용 범위: 키 => [최소, 최대]
const DESIGN_RANGES = array(
    'design_header_h' => array(48, 140),
    'design_header_size' => array(12, 20),
    'design_logo_size' => array(16, 44),
    'design_logo_height' => array(20, 100),
    'design_body_size' => array(14, 20),
    'design_footer_size' => array(11, 16),
);

function design_defaults()
{
    return array(
        'design_logo_type' => 'text',
        'design_logo_image' => '',
        'design_logo_font' => 'noto-serif',
        'design_logo_size' => '22',
        'design_logo_height' => '36',
        'design_header_bg' => '#F6F4EF',
        'design_header_h' => '72',
        'design_header_font' => 'plex',
        'design_header_size' => '15',
        'design_main_bg' => '#F6F4EF',
        'design_body_font' => 'plex',
        'design_heading_font' => 'noto-serif',
        'design_body_size' => '16',
        'design_footer_bg' => '#F6F4EF',
        'design_footer_font' => 'plex',
        'design_footer_size' => '13',
    );
}

/** 저장된 디자인 값(잘못된 값은 기본값으로) */
function design()
{
    $d = design_defaults();
    foreach ($d as $key => $default) {
        $v = setting($key);
        if ($v === '') {
            continue;
        }
        if (substr($key, -3) === '_bg' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) {
            continue;
        }
        if (substr($key, -5) === '_font' && !array_key_exists($v, DESIGN_FONTS)) {
            continue;
        }
        if (array_key_exists($key, DESIGN_RANGES)) {
            $v = (string) max(DESIGN_RANGES[$key][0], min(DESIGN_RANGES[$key][1], (int) $v));
        }
        $d[$key] = $v;
    }
    if (!in_array($d['design_logo_type'], array('text', 'image'), true) || ($d['design_logo_type'] === 'image' && $d['design_logo_image'] === '')) {
        $d['design_logo_type'] = 'text';
    }
    return $d;
}

/** 색의 밝기(0 어두움 ~ 1 밝음) */
function color_luminance($hex)
{
    $hex = ltrim($hex, '#');
    $c = array(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    foreach ($c as &$v) {
        $v /= 255;
        $v = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    }
    unset($v);
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function is_dark_color($hex)
{
    return color_luminance($hex) < 0.36;
}

function font_stack($key)
{
    $f = DESIGN_FONTS[$key] ?? DESIGN_FONTS['plex'];
    return $f[1] !== '' ? "'" . $f[1] . "', " . $f[3] : "system-ui, -apple-system, 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif";
}

/** 쓰는 글씨체만 불러오는 구글 폰트 주소. $all=true 면 전부(관리자 미리보기용) */
function design_fonts_url($all = false)
{
    $d = design();
    $keys = $all ? array_keys(DESIGN_FONTS) : array($d['design_logo_font'], $d['design_header_font'], $d['design_body_font'], $d['design_heading_font'], $d['design_footer_font'], 'noto-serif', 'plex');
    $parts = array();
    foreach (array_unique($keys) as $k) {
        if (!empty(DESIGN_FONTS[$k][2])) {
            $parts[] = 'family=' . DESIGN_FONTS[$k][2];
        }
    }
    return 'https://fonts.googleapis.com/css2?' . implode('&', $parts) . '&display=swap';
}

/** 공개 화면에 넣는 CSS 변수 */
function design_style()
{
    $d = design();
    $header = area_colors($d['design_header_bg']);
    $footer = area_colors($d['design_footer_bg']);
    $vars = array(
        '--header-bg' => $d['design_header_bg'],
        '--header-fg' => $header['fg'],
        '--header-sub' => $header['sub'],
        '--header-line' => $header['line'],
        '--header-accent' => $header['accent'],
        '--header-h' => (int) $d['design_header_h'] . 'px',
        '--header-font' => font_stack($d['design_header_font']),
        '--header-size' => (int) $d['design_header_size'] . 'px',
        '--logo-font' => font_stack($d['design_logo_font']),
        '--logo-size' => (int) $d['design_logo_size'] . 'px',
        '--logo-h' => (int) $d['design_logo_height'] . 'px',
        '--main-bg' => $d['design_main_bg'],
        '--body-font' => font_stack($d['design_body_font']),
        '--heading-font' => font_stack($d['design_heading_font']),
        '--main-scale' => round((int) $d['design_body_size'] / 16, 4),
        '--footer-bg' => $d['design_footer_bg'],
        '--footer-fg' => $footer['fg'],
        '--footer-sub' => $footer['sub'],
        '--footer-line' => $footer['line'],
        '--footer-font' => font_stack($d['design_footer_font']),
        '--footer-scale' => round((int) $d['design_footer_size'] / 13, 4),
    );
    $css = '';
    foreach ($vars as $k => $v) {
        $css .= $k . ':' . $v . ';';
    }
    return '<style>:root{' . str_replace('</', '<\\/', $css) . '}</style>';
}

/** 배경색에 맞는 글씨·선 색(어두운 배경이면 밝은 글씨) */
function area_colors($bg)
{
    if (is_dark_color($bg)) {
        return array('fg' => '#F6F4EF', 'sub' => 'rgba(246,244,239,.74)', 'line' => 'rgba(255,255,255,.14)', 'accent' => '#A8D8C5');
    }
    return array('fg' => '#1D1C1A', 'sub' => '#5F5B53', 'line' => '#E3DED3', 'accent' => '#2E5E4E');
}
