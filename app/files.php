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

/** 이미지 검사 후 저장. 성공하면 공개 경로(/uploads/...), 실패하면 예외. */
function store_image($file, $subdir, $name = null)
{
    $err = upload_error($file);
    if ($err !== '') {
        throw new RuntimeException($err);
    }
    if ($file['size'] > 10 * 1048576) {
        throw new RuntimeException('이미지는 10MB 이하로 올려 주세요.');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('JPG, PNG, WEBP 이미지만 올릴 수 있어요.');
    }
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = ($name ?? pathinfo(random_name('x'), PATHINFO_FILENAME)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('이미지를 저장하지 못했어요. 폴더 권한을 확인해 주세요.');
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
