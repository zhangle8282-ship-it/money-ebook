<?php
/**
 * 파일 저장: 표지·미리보기 이미지는 public/uploads(공개),
 * 전자책 원본은 storage/books(웹에서 직접 열 수 없음)에 둡니다.
 */

function ensure_storage()
{
    foreach (array(STORAGE_DIR, STORAGE_DIR . '/books', STORAGE_DIR . '/sessions', UPLOAD_DIR . '/covers', UPLOAD_DIR . '/previews') as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    // storage 가 실수로 웹 폴더 안에 놓여도 열리지 않게 막아 둡니다.
    $deny = "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n";
    if (is_dir(STORAGE_DIR) && !is_file(STORAGE_DIR . '/.htaccess')) {
        @file_put_contents(STORAGE_DIR . '/.htaccess', $deny);
    }
    // 업로드 폴더에서는 스크립트가 실행되지 않게 합니다.
    if (is_dir(UPLOAD_DIR) && !is_file(UPLOAD_DIR . '/.htaccess')) {
        @file_put_contents(UPLOAD_DIR . '/.htaccess', "<FilesMatch \"\\.(php[0-9]?|phtml|phar|html?|svg|js)$\">\n" . $deny . "</FilesMatch>\n");
    }
}

/** 업로드 오류 코드를 안내 문구로 바꿉니다. 정상이면 빈 문자열. */
function upload_error($file)
{
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        return '파일을 받지 못했어요.';
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            return is_uploaded_file($file['tmp_name']) ? '' : '파일을 받지 못했어요.';
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return '파일이 서버 업로드 한도(' . ini_get('upload_max_filesize') . ')보다 커요.';
        case UPLOAD_ERR_PARTIAL:
            return '파일이 끝까지 올라가지 않았어요. 다시 시도해 주세요.';
        default:
            return '파일을 올리지 못했어요(오류 ' . (int) $file['error'] . ').';
    }
}

function has_upload($name)
{
    return isset($_FILES[$name]) && is_array($_FILES[$name]) && ($_FILES[$name]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function random_name($ext)
{
    return date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
}

// 사진 줄이기(그린청소 블로그 · 홈페이지 사진): ‘균형’ 기준 — 화질과 용량의 중간.
// 긴 변 최대 크기(px): 블로그 글 폭(760px)에서 대부분 화면에 선명하게, 홈페이지 큰 사진은 조금 더 크게.
const IMAGE_MAX_SIDE = array('blog' => 1280, 'site' => 1600);
const IMAGE_JPEG_QUALITY = 80;   // 균형: 대부분 화면에서 원본과 차이를 느끼기 어려운 품질
const IMAGE_PNG_JPEG_QUALITY = 85; // 무거운 캡처 PNG를 JPG로 바꿀 때(글자가 뭉개지지 않게 조금 높게)
const IMAGE_PNG_KEEP = 307200;   // 투명하지 않은 PNG(글자 캡처 등)는 300KB까지 PNG 그대로
const IMAGE_HEAVY = 307200;      // 크기는 알맞아도 300KB 넘는 JPG는 다시 압축해서 30% 넘게 줄 때만 바꿈

/**
 * 저장한 사진을 가볍게 하되 화질은 지킵니다.
 * - 크기가 알맞은 JPG: 다시 압축하지 않고 촬영 정보 · 위치(GPS) · 미리보기 그림만 떼어 냄(화질 변화 없음)
 * - 큰 사진: 긴 변을 줄이고 품질 80으로 저장, 휴대폰 회전 정보 반영, 색 프로필(아이폰 등) 그대로 붙임
 * - 투명 PNG는 투명 유지, 움직이는 GIF · CMYK 사진은 그대로. 사진 기능(GD)이 없거나 메모리가 모자라거나 결과가 더 크면 원본.
 * 반환: 최종 파일 경로(PNG가 JPG로 바뀌면 확장자도 바뀜)
 */
function image_shrink($file, $maxSide)
{
    $info = @getimagesize($file);
    if (!$info) {
        return $file;
    }
    list($w, $h, $type) = $info;
    $jpeg = $type === IMAGETYPE_JPEG;
    $orient = $jpeg ? jpeg_orientation($file) : 1;
    $scale = min(1, $maxSide / max(1, $w, $h));
    if ($jpeg && $scale >= 1 && $orient === 1) {
        // 크기가 알맞은 JPG: 화질 손실 없이 숨은 정보만 떼고, 아주 무거울 때만 다시 압축
        jpeg_strip_meta($file);
        clearstatcache();
        if (filesize($file) <= IMAGE_HEAVY || ($info['channels'] ?? 3) === 4) {
            return $file;
        }
    } elseif ($scale >= 1 && $orient === 1 && filesize($file) <= 153600) {
        return $file; // 이미 작고 가벼운 PNG · WEBP
    }
    $open = array(IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp');
    if (!function_exists('imagecreatetruecolor') || !isset($open[$type]) || !function_exists($open[$type]) || ($info['channels'] ?? 3) === 4) {
        return $file; // GD 없음 · GIF · CMYK
    }
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    if (!image_memory_ok(($w * $h * ($orient > 1 ? 2 : 1) + $nw * $nh) * 5)) {
        return $file;
    }
    $src = @$open[$type]($file);
    if (!$src) {
        return $file;
    }
    $alpha = !$jpeg && image_has_alpha($src);
    if ($scale < 1) {
        $dst = imagecreatetruecolor($nw, $nh);
        if ($alpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        image_free($src);
    } else {
        $dst = $src;
    }
    if ($orient > 1) {
        $dst = image_orient($dst, $orient);
    }
    $tmp = $file . '.tmp';
    $ext = 'jpg';
    if ($alpha) {
        $ext = $type === IMAGETYPE_WEBP ? 'webp' : 'png';
        $ok = $ext === 'webp' ? imagewebp($dst, $tmp, 88) : imagepng($dst, $tmp, 9);
    } elseif ($type === IMAGETYPE_PNG && imagepng($dst, $tmp, 9) && filesize($tmp) <= IMAGE_PNG_KEEP) {
        // 글자 많은 캡처 화면처럼 PNG가 더 깨끗하고 충분히 가벼우면 PNG 그대로
        $ext = 'png';
        $ok = true;
    } else {
        imageinterlace($dst, true);
        $ok = imagejpeg($dst, $tmp, $type === IMAGETYPE_PNG ? IMAGE_PNG_JPEG_QUALITY : IMAGE_JPEG_QUALITY);
        if ($ok && $jpeg) {
            jpeg_copy_icc($file, $tmp); // 색 프로필(아이폰 Display P3 등) 그대로
        }
    }
    image_free($dst);
    clearstatcache();
    $before = filesize($file);
    $after = $ok && is_file($tmp) ? filesize($tmp) : 0;
    // 원본 그대로 두는 경우: 저장 실패 / 돌리지 않았는데 오히려 커짐 / 크기 그대로 다시 압축했는데 30% 넘게 줄지 않음
    if (!$after || ($orient === 1 && $after >= $before) || ($scale >= 1 && $orient === 1 && $after > $before * 0.7)) {
        @unlink($tmp);
        return $file;
    }
    $new = preg_replace('/\.[A-Za-z0-9]+$/', '.' . $ext, $file);
    if (!@rename($tmp, $new)) {
        @unlink($tmp);
        return $file;
    }
    if ($new !== $file) {
        @unlink($file);
    }
    return $new;
}

/** JPG를 조각(마커)으로 나눔. 반환: [[마커, 조각 바이트]…, 그림 데이터(SOS부터 끝까지)] 또는 null */
function jpeg_segments($bytes)
{
    if (substr($bytes, 0, 2) !== "\xFF\xD8") {
        return null;
    }
    $pos = 2;
    $len = strlen($bytes);
    $segs = array();
    while ($pos + 4 <= $len) {
        if ($bytes[$pos] !== "\xFF") {
            return null;
        }
        $m = ord($bytes[$pos + 1]);
        if ($m === 0xFF) {
            $pos++;
            continue;
        }
        if ($m === 0xDA) {
            return array($segs, substr($bytes, $pos));
        }
        if ($m === 0x01 || ($m >= 0xD0 && $m <= 0xD8)) {
            $pos += 2;
            continue;
        }
        $l = unpack('n', substr($bytes, $pos + 2, 2))[1];
        if ($l < 2 || $pos + 2 + $l > $len) {
            return null;
        }
        $segs[] = array($m, substr($bytes, $pos, 2 + $l));
        $pos += 2 + $l;
    }
    return null;
}

/** 화질 손실 없이 JPG의 숨은 정보(촬영 정보 · 위치 · 미리보기 그림 · 메모)만 떼어 냄. 색 프로필 · 회전에 필요 없는 건 남김 */
function jpeg_strip_meta($file)
{
    $bytes = (string) @file_get_contents($file);
    $parts = jpeg_segments($bytes);
    if (!$parts) {
        return;
    }
    $out = "\xFF\xD8";
    foreach ($parts[0] as $seg) {
        // APP1(Exif · XMP) · APP13(포토샵 · IPTC) · COM(메모)만 뺌. APP0 · APP2(색 프로필) · APP14(색 방식)는 그대로
        if (!in_array($seg[0], array(0xE1, 0xED, 0xFE), true)) {
            $out .= $seg[1];
        }
    }
    $out .= $parts[1];
    if (strlen($out) < strlen($bytes) && @file_put_contents($file . '.tmp', $out) === strlen($out)) {
        @rename($file . '.tmp', $file);
    }
}

/** 원본 JPG의 색 프로필(ICC)을 새로 만든 JPG에 그대로 붙임 */
function jpeg_copy_icc($from, $to)
{
    $src = jpeg_segments((string) @file_get_contents($from));
    $dst = jpeg_segments((string) @file_get_contents($to));
    if (!$src || !$dst) {
        return;
    }
    $icc = '';
    foreach ($src[0] as $seg) {
        if ($seg[0] === 0xE2 && substr($seg[1], 4, 12) === "ICC_PROFILE\0") {
            $icc .= $seg[1];
        }
    }
    if ($icc === '') {
        return;
    }
    $out = "\xFF\xD8";
    $done = false;
    foreach ($dst[0] as $seg) {
        $out .= $seg[1];
        if (!$done && $seg[0] === 0xE0) {
            $out .= $icc; // JFIF(APP0) 바로 다음
            $done = true;
        }
    }
    $out = $done ? $out . $dst[1] : "\xFF\xD8" . $icc . substr($out, 2) . $dst[1];
    @file_put_contents($to, $out);
}

/** 다 쓴 사진 메모리 돌려주기(PHP 8부터는 저절로 풀려서 부르지 않음) */
function image_free($img)
{
    if (PHP_VERSION_ID < 80000 && $img) {
        imagedestroy($img);
    }
}

/** 사진을 펼칠 메모리가 있는지(모자라면 한 번 늘려 봄, 최대 512MB) */
function image_memory_ok($need)
{
    $limit = ini_get('memory_limit');
    if ($limit === '-1') {
        return true;
    }
    $bytes = function ($v) {
        $v = trim((string) $v);
        $n = (float) $v;
        switch (strtolower(substr($v, -1))) {
            case 'g': return $n * 1073741824;
            case 'm': return $n * 1048576;
            case 'k': return $n * 1024;
        }
        return $n;
    };
    $want = memory_get_usage() + $need * 1.3 + 8388608;
    if ($bytes($limit) >= $want) {
        return true;
    }
    if ($want > 536870912) {
        return false;
    }
    return @ini_set('memory_limit', (string) ceil($want / 1048576) . 'M') !== false && $bytes(ini_get('memory_limit')) >= $want;
}

/** 투명한 곳이 있는지(팔레트 투명색 또는 반투명 점을 고르게 살펴봄) */
function image_has_alpha($img)
{
    if (!imageistruecolor($img)) {
        return imagecolortransparent($img) >= 0;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $sx = max(1, (int) ($w / 60));
    $sy = max(1, (int) ($h / 60));
    for ($y = 0; $y < $h; $y += $sy) {
        for ($x = 0; $x < $w; $x += $sx) {
            if ((imagecolorat($img, $x, $y) >> 24) & 0x7F) {
                return true;
            }
        }
    }
    return false;
}

/** JPG 회전 정보(1~8). exif 기능이 없어도 파일 앞부분에서 직접 읽음 */
function jpeg_orientation($file)
{
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($file, 'IFD0');
        return isset($exif['Orientation']) && $exif['Orientation'] >= 1 && $exif['Orientation'] <= 8 ? (int) $exif['Orientation'] : 1;
    }
    $data = (string) @file_get_contents($file, false, null, 0, 131072);
    $pos = strpos($data, "Exif\0\0");
    if ($pos === false) {
        return 1;
    }
    $tiff = $pos + 6;
    $le = substr($data, $tiff, 2) === 'II';
    $u16 = function ($o) use ($data, $le) {
        $v = unpack($le ? 'v' : 'n', substr($data, $o, 2));
        return $v ? $v[1] : 0;
    };
    $u32 = function ($o) use ($data, $le) {
        $v = unpack($le ? 'V' : 'N', substr($data, $o, 4));
        return $v ? $v[1] : 0;
    };
    $ifd = $tiff + $u32($tiff + 4);
    $count = $u16($ifd);
    for ($i = 0; $i < $count && $i < 200; $i++) {
        $e = $ifd + 2 + $i * 12;
        if ($u16($e) === 0x0112) {
            $o = $u16($e + 8);
            return $o >= 1 && $o <= 8 ? $o : 1;
        }
    }
    return 1;
}

/** 회전 정보대로 바로 세우기 */
function image_orient($img, $o)
{
    if (in_array($o, array(2, 7), true)) {
        imageflip($img, IMG_FLIP_HORIZONTAL);
    } elseif (in_array($o, array(4, 5), true)) {
        imageflip($img, IMG_FLIP_VERTICAL);
    }
    $angle = array(3 => 180, 5 => -90, 6 => -90, 7 => -90, 8 => 90)[$o] ?? 0;
    if ($angle) {
        $r = imagerotate($img, $angle, 0);
        if ($r) {
            image_free($img);
            $img = $r;
        }
    }
    return $img;
}

/** 방금 줄인 사진 결과(원본 · 저장 [용량, 가로, 세로]). $set 이 있으면 기록 */
function image_report($set = null)
{
    static $last = null;
    if ($set !== null) {
        $last = $set;
    }
    return $last;
}

/**
 * 사진 줄인 결과를 한 문장으로(예: 대표 사진을 가볍게 줄였어요: 4.2MB (4032×3024) → 312KB (1600×1200)).
 * 브라우저가 너무 큰 사진(9MB 넘음)을 먼저 줄였으면 함께 보낸 photo_orig(원본 용량,가로,세로)를 원본으로 씁니다.
 */
function image_report_text($label)
{
    $r = image_report();
    if (!$r || !$r['after'][0]) {
        return '';
    }
    list($bb, $bw, $bh) = $r['before'];
    if (preg_match('/^(\d+),(\d+),(\d+)$/', (string) input('photo_orig'), $m) && (int) $m[1] > $bb) {
        list(, $bb, $bw, $bh) = array_map('intval', $m);
    }
    list($ab, $aw, $ah) = $r['after'];
    $dim = function ($w, $h) {
        return $w . '×' . $h;
    };
    if ($aw !== $bw || $ah !== $bh) {
        return ' ' . $label . '을 가볍게 줄였어요: ' . fmt_bytes($bb) . ' (' . $dim($bw, $bh) . ') → ' . fmt_bytes($ab) . ' (' . $dim($aw, $ah) . '). 화면에서는 원본과 거의 같게 보여요.';
    }
    if ($ab < $bb * 0.7) {
        return ' ' . $label . '은 크기(' . $dim($aw, $ah) . ')는 그대로 두고 품질 ' . IMAGE_JPEG_QUALITY . '으로 다시 저장해 ' . fmt_bytes($bb) . ' → ' . fmt_bytes($ab) . '로 줄였어요.';
    }
    if ($ab < $bb) {
        return ' ' . $label . '은 화질 그대로 두고 촬영 정보 · 위치만 떼어 ' . fmt_bytes($bb) . ' → ' . fmt_bytes($ab) . '로 줄였어요 (' . $dim($aw, $ah) . ').';
    }
    return ' ' . $label . '은 이미 가벼워서 그대로 올렸어요 (' . fmt_bytes($ab) . ', ' . $dim($aw, $ah) . ').';
}

/** 공개 폴더 안 사진의 크기 · 용량(관리자 화면 표시용). 반환: '1600×1067 · 180KB' 또는 '' */
function public_image_info($path)
{
    if (!preg_match('~^/uploads/[A-Za-z0-9/_.-]+$~', (string) $path) || strpos($path, '..') !== false) {
        return '';
    }
    $file = PUBLIC_DIR . $path;
    $info = is_file($file) ? @getimagesize($file) : false;
    return $info ? $info[0] . '×' . $info[1] . ' · ' . fmt_bytes(filesize($file)) : '';
}

/** 이미지 검사 후 저장. 성공하면 공개 경로(/uploads/...), 실패하면 예외. */
function store_image($file, $subdir, $name = null, $gif = false)
{
    $err = upload_error($file);
    if ($err !== '') {
        throw new RuntimeException($err);
    }
    if ($file['size'] > 10 * 1048576) {
        throw new RuntimeException('이미지는 10MB 이하로 올려 주세요.');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp') + ($gif ? array(IMAGETYPE_GIF => 'gif') : array());
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException($gif ? 'JPG, PNG, GIF, WEBP 이미지만 올릴 수 있어요.' : 'JPG, PNG, WEBP 이미지만 올릴 수 있어요.');
    }
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = ($name ?? pathinfo(random_name('x'), PATHINFO_FILENAME)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('이미지를 저장하지 못했어요. 폴더 권한을 확인해 주세요.');
    }
    if (isset(IMAGE_MAX_SIDE[$subdir])) {
        $before = array((int) $file['size'], (int) $info[0], (int) $info[1]);
        $filename = basename(image_shrink($dir . '/' . $filename, IMAGE_MAX_SIDE[$subdir]));
        clearstatcache();
        $after = @getimagesize($dir . '/' . $filename);
        image_report(array('before' => $before, 'after' => array((int) @filesize($dir . '/' . $filename), (int) ($after[0] ?? 0), (int) ($after[1] ?? 0))));
    }
    return '/uploads/' . $subdir . '/' . $filename;
}

/** 전자책 원본 검사 후 저장. 반환: [저장경로, 원래 파일명, 크기, 형식(EPUB|PDF)] */
function store_book_file($file)
{
    $err = upload_error($file);
    if ($err !== '') {
        throw new RuntimeException($err);
    }
    if ($file['size'] > config('max_book_mb') * 1048576) {
        throw new RuntimeException('전자책 파일은 ' . config('max_book_mb') . 'MB 이하로 올려 주세요.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 64);
    if ($ext === 'pdf') {
        if (strpos($head, '%PDF-') === false) {
            throw new RuntimeException('PDF 파일이 아니거나 손상된 파일이에요.');
        }
        $format = 'PDF';
    } elseif ($ext === 'epub') {
        if (!epub_valid($file['tmp_name'])) {
            throw new RuntimeException('EPUB 파일이 아니거나 손상된 파일이에요.');
        }
        $format = 'EPUB';
    } else {
        throw new RuntimeException('EPUB 또는 PDF 파일만 올릴 수 있어요.');
    }
    $stored = random_name(strtolower($format));
    if (!move_uploaded_file($file['tmp_name'], STORAGE_DIR . '/books/' . $stored)) {
        throw new RuntimeException('전자책 파일을 저장하지 못했어요. storage 폴더 권한을 확인해 주세요.');
    }
    $original = preg_replace('/[\x00-\x1F\/\\\\]+/', '', basename($file['name']));
    return array($stored, $original, (int) $file['size'], $format);
}

function book_file_path($book)
{
    return $book['file_path'] !== '' ? STORAGE_DIR . '/books/' . basename($book['file_path']) : '';
}

function delete_public_file($publicPath)
{
    if (is_string($publicPath) && strncmp($publicPath, '/uploads/', 9) === 0 && strpos($publicPath, '..') === false) {
        @unlink(PUBLIC_DIR . $publicPath);
    }
}

function delete_preview_images($bookId)
{
    $dir = UPLOAD_DIR . '/previews/' . (int) $bookId;
    foreach (glob($dir . '/*') ?: array() as $f) {
        @unlink($f);
    }
    @rmdir($dir);
}

/** 파일을 내려보냅니다(한글 파일명 지원). */
function send_download($path, $filename, $mime)
{
    if (!is_file($path)) {
        return false;
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
    if (trim(pathinfo($ascii, PATHINFO_FILENAME), '_.') === '') {
        $ascii = 'ebook.' . pathinfo($filename, PATHINFO_EXTENSION);
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    return true;
}
