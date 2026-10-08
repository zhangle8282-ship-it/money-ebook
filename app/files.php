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

// 사진 줄이기(그린청소 블로그 · 홈페이지 사진): 긴 변 최대 크기(px). 1MB 넘는 휴대폰 사진도 보통 150~250KB로 줄어요.
const IMAGE_MAX_SIDE = array('blog' => 1280, 'site' => 1600);
const IMAGE_JPEG_QUALITY = 80;
const IMAGE_PNG_KEEP = 307200; // 투명하지 않은 PNG는 줄인 뒤 300KB 넘으면 JPG로

/**
 * 저장한 사진을 가볍게: 긴 변을 줄이고 다시 압축합니다. 휴대폰 사진의 회전 정보도 반영합니다.
 * 움직이는 GIF, 사진 기능(GD)이 없거나 메모리가 모자랄 때, 줄인 결과가 오히려 클 때는 원본 그대로.
 * 반환: 최종 파일 경로(PNG가 JPG로 바뀌면 확장자도 바뀜)
 */
function image_shrink($file, $maxSide)
{
    if (!function_exists('imagecreatetruecolor')) {
        return $file;
    }
    $info = @getimagesize($file);
    if (!$info) {
        return $file;
    }
    list($w, $h, $type) = $info;
    $open = array(IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp');
    if (!isset($open[$type]) || !function_exists($open[$type])) {
        return $file;
    }
    $orient = $type === IMAGETYPE_JPEG ? jpeg_orientation($file) : 1;
    $scale = min(1, $maxSide / max(1, $w, $h));
    if ($scale >= 1 && $orient === 1 && filesize($file) <= 153600) {
        return $file; // 이미 작고 가벼운 사진
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
    $alpha = $type !== IMAGETYPE_JPEG && image_has_alpha($src);
    $dst = imagecreatetruecolor($nw, $nh);
    if ($alpha) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    image_free($src);
    if ($orient > 1) {
        $dst = image_orient($dst, $orient);
    }
    $tmp = $file . '.tmp';
    $ext = 'jpg';
    if ($alpha) {
        $ext = $type === IMAGETYPE_WEBP ? 'webp' : 'png';
        $ok = $ext === 'webp' ? imagewebp($dst, $tmp, 82) : imagepng($dst, $tmp, 9);
    } elseif ($type === IMAGETYPE_PNG && imagepng($dst, $tmp, 9) && filesize($tmp) <= IMAGE_PNG_KEEP) {
        // 글자 많은 캡처 화면처럼 PNG가 더 깨끗하고 충분히 가벼우면 PNG 그대로
        $ext = 'png';
        $ok = true;
    } else {
        imageinterlace($dst, true);
        $ok = imagejpeg($dst, $tmp, IMAGE_JPEG_QUALITY);
    }
    image_free($dst);
    clearstatcache();
    if (!$ok || !is_file($tmp) || ($orient === 1 && filesize($tmp) >= filesize($file))) {
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
        $filename = basename(image_shrink($dir . '/' . $filename, IMAGE_MAX_SIDE[$subdir]));
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
