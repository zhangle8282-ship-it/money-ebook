<?php
/**
 * 디자인 설정: 상단 영역(헤더)·본문 영역·하단 영역(푸터)의 배경색·글씨체·글씨 크기, 헤더 높이, 로고(텍스트/이미지).
 * 관리자 › 디자인에서 바꾸고, 공개 화면에 CSS 변수로 적용합니다.
 */

// 기본 글씨체: 누구나 상업적으로 쓸 수 있는 무료 글꼴(SIL 오픈 폰트 라이선스)과 기기 기본 글꼴만 둡니다.
// 다른 글씨체는 관리자 › 디자인 › 글씨체에서 운영자가 직접 올려서 씁니다.
// 키 => [표시 이름, CSS 글꼴 이름, 구글 폰트 주소 조각, 대체 글꼴]
const DESIGN_BASE_FONTS = array(
    'plex' => array('IBM Plex Sans KR (기본 본문)', 'IBM Plex Sans KR', 'IBM+Plex+Sans+KR:wght@400;500;600;700', 'sans-serif'),
    'noto-serif' => array('본명조 Noto Serif KR (기본 제목)', 'Noto Serif KR', 'Noto+Serif+KR:wght@400;600;700', 'serif'),
    'noto-sans' => array('본고딕 Noto Sans KR', 'Noto Sans KR', 'Noto+Sans+KR:wght@400;500;700', 'sans-serif'),
    'system' => array('기기 기본 글꼴', '', '', 'system-ui'),
);

// 올릴 수 있는 글씨체 파일: 형식 => [확장자, 파일 첫 바이트, CSS format()]
const FONT_FORMATS = array(
    'woff2' => array('woff2', array('wOF2'), 'woff2'),
    'woff' => array('woff', array('wOFF'), 'woff'),
    'ttf' => array('ttf', array("\x00\x01\x00\x00", 'true'), 'truetype'),
    'otf' => array('otf', array('OTTO'), 'opentype'),
);
const FONT_MAX_MB = 20;
const FONT_KINDS = array('sans-serif' => '고딕(민글씨) 계열', 'serif' => '명조(바탕) 계열', 'cursive' => '손글씨 계열');

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

/** 관리자가 올린 글씨체 목록 */
function uploaded_fonts()
{
    static $rows = null;
    if ($rows === null) {
        $rows = q_all('SELECT * FROM fonts ORDER BY id');
    }
    return $rows;
}

/** 고를 수 있는 모든 글씨체: 기본 글씨체 + 올린 글씨체('u번호'). 값은 [표시 이름, CSS 글꼴 이름, 구글 폰트 조각, 대체 글꼴] */
function design_fonts()
{
    $fonts = DESIGN_BASE_FONTS;
    foreach (uploaded_fonts() as $f) {
        $fonts['u' . (int) $f['id']] = array($f['name'], 'mkfont-' . (int) $f['id'], '', $f['kind']);
    }
    return $fonts;
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
        if (substr($key, -5) === '_font' && !array_key_exists($v, design_fonts())) {
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
    $fonts = design_fonts();
    $f = $fonts[$key] ?? $fonts['plex'];
    if ($f[1] === '') {
        return "system-ui, -apple-system, 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif";
    }
    // 올린 글씨체가 없는 글자는 기본 글씨체로 보여 줍니다.
    $fallback = strncmp($key, 'u', 1) === 0 ? ($f[3] === 'serif' ? "'Noto Serif KR', serif" : "'IBM Plex Sans KR', sans-serif") : $f[3];
    return "'" . $f[1] . "', " . $fallback;
}

/** 지금 디자인에서 쓰는 글씨체 키 */
function design_used_fonts()
{
    $d = design();
    return array_unique(array($d['design_logo_font'], $d['design_header_font'], $d['design_body_font'], $d['design_heading_font'], $d['design_footer_font']));
}

/** 쓰는 기본 글씨체만 불러오는 구글 폰트 주소. $all=true 면 기본 글씨체 전부(관리자 미리보기용) */
function design_fonts_url($all = false)
{
    $keys = $all ? array_keys(DESIGN_BASE_FONTS) : array_merge(design_used_fonts(), array('noto-serif', 'plex'));
    $parts = array();
    foreach (array_unique($keys) as $k) {
        if (!empty(DESIGN_BASE_FONTS[$k][2])) {
            $parts[] = 'family=' . DESIGN_BASE_FONTS[$k][2];
        }
    }
    return 'https://fonts.googleapis.com/css2?' . implode('&', $parts) . '&display=swap';
}

/** 올린 글씨체의 @font-face. $all=true 면 전부(관리자 화면), 아니면 지금 쓰는 것만 */
function font_face_css($all = false)
{
    $used = $all ? null : design_used_fonts();
    $css = '';
    foreach (uploaded_fonts() as $f) {
        if ($used !== null && !in_array('u' . (int) $f['id'], $used, true)) {
            continue;
        }
        $files = array(400 => $f['file_regular'], 700 => $f['file_bold']);
        foreach ($files as $weight => $path) {
            if ((string) $path === '' || !preg_match('~^/uploads/fonts/[A-Za-z0-9.-]+$~', $path)) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $format = isset(FONT_FORMATS[$ext]) ? FONT_FORMATS[$ext][2] : 'truetype';
            $css .= "@font-face{font-family:'mkfont-" . (int) $f['id'] . "';src:url('" . $path . "') format('" . $format . "');font-weight:" . $weight . ";font-style:normal;font-display:swap}";
        }
    }
    return $css;
}

/** 글씨체 파일 검사 후 저장(public/uploads/fonts). 반환: [공개 경로, 크기] */
function store_font_file($file)
{
    $err = upload_error($file);
    if ($err !== '') {
        throw new RuntimeException($err);
    }
    if ($file['size'] > FONT_MAX_MB * 1048576) {
        throw new RuntimeException('글씨체 파일은 ' . FONT_MAX_MB . 'MB 이하로 올려 주세요. 웹용 WOFF2 파일이 가장 작아요.');
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 4);
    $format = null;
    foreach (FONT_FORMATS as $key => $f) {
        if (in_array($head, $f[1], true)) {
            $format = $key;
        }
    }
    if (!isset(FONT_FORMATS[$ext]) || $format === null) {
        throw new RuntimeException('WOFF2, WOFF, TTF, OTF 글씨체 파일만 올릴 수 있어요.');
    }
    $dir = UPLOAD_DIR . '/fonts';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = random_name(FONT_FORMATS[$format][0]);
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('글씨체 파일을 저장하지 못했어요. 폴더 권한을 확인해 주세요.');
    }
    return array('/uploads/fonts/' . $name, (int) $file['size']);
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
    return '<style>' . font_face_css() . ':root{' . str_replace('</', '<\\/', $css) . '}</style>';
}

/** 배경색에 맞는 글씨·선 색(어두운 배경이면 밝은 글씨) */
function area_colors($bg)
{
    if (is_dark_color($bg)) {
        return array('fg' => '#F6F4EF', 'sub' => 'rgba(246,244,239,.74)', 'line' => 'rgba(255,255,255,.14)', 'accent' => '#A8D8C5');
    }
    return array('fg' => '#1D1C1A', 'sub' => '#5F5B53', 'line' => '#E3DED3', 'accent' => '#2E5E4E');
}
