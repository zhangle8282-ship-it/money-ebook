<?php
/**
 * 설치 파일(zip) 검사와 설치. 설치 도구(install.php)와 관리자 › 설정 › 프로그램 업데이트가 함께 씁니다.
 * zip 구조: [폴더/]app/…, [폴더/]www/…  (app/VERSION, app/bootstrap.php, www/index.php 가 있어야 합니다)
 * 설치할 때 storage(데이터)와 www/uploads(표지·미리보기)는 건드리지 않습니다.
 */

const PKG_MAX_BYTES = 104857600; // 풀었을 때 100MB 이하
const PKG_KEEP_BACKUPS = 2;      // 이전 프로그램 폴더(app.bak-날짜)를 몇 개 남길지

/** zip 을 열고 검사합니다. 성공: ['zip' => ZipArchive, 'prefix' => 앞 폴더, 'version' => 버전], 실패: 안내 문구 */
function pkg_open($path)
{
    if (!class_exists('ZipArchive')) {
        return '서버에 zip 기능(ZipArchive)이 없어 설치할 수 없어요. 호스팅 업체에 PHP zip 확장을 켜 달라고 요청해 주세요.';
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return 'zip 파일을 열 수 없어요. 내려받은 파일을 압축을 풀지 않고 그대로 올려 주세요.';
    }
    $prefix = null;
    $total = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!pkg_safe_name($stat['name'])) {
            $zip->close();
            return '안전하지 않은 경로가 들어 있는 zip 파일이라 설치하지 않았어요.';
        }
        $total += $stat['size'];
        if (preg_match('~^((?:[^/]+/)?)app/bootstrap\.php$~', $stat['name'], $m)) {
            $prefix = $m[1];
        }
    }
    if ($total > PKG_MAX_BYTES) {
        $zip->close();
        return 'zip 파일이 너무 커요.';
    }
    if ($prefix === null || $zip->locateName($prefix . 'www/index.php') === false || $zip->locateName($prefix . 'app/VERSION') === false) {
        $zip->close();
        return '전자책 스토어 설치 파일이 아니에요. 내려받은 ebook-store zip 파일을 그대로 올려 주세요.';
    }
    return array('zip' => $zip, 'prefix' => $prefix, 'version' => trim((string) $zip->getFromName($prefix . 'app/VERSION')));
}

/** zip 안의 경로가 설치 폴더 밖으로 나가지 않는지 확인합니다. */
function pkg_safe_name($name)
{
    if ($name === '' || strpos($name, "\0") !== false || strpos($name, '\\') !== false || $name[0] === '/' || preg_match('~^[A-Za-z]:~', $name)) {
        return false;
    }
    foreach (explode('/', $name) as $part) {
        if ($part === '..') {
            return false;
        }
    }
    return true;
}

/** zip 의 $section('app/' 또는 'www/') 아래 파일을 $dest 에 풉니다. $skip: 건너뛸 경로(앞부분) */
function pkg_extract($pkg, $section, $dest, $skip)
{
    $zip = $pkg['zip'];
    $base = $pkg['prefix'] . $section;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strncmp($name, $base, strlen($base)) !== 0 || $name === $base) {
            continue;
        }
        $rel = substr($name, strlen($base));
        foreach ($skip as $s) {
            if (strncmp($rel, $s, strlen($s)) === 0) {
                continue 2;
            }
        }
        $target = $dest . '/' . $rel;
        if (substr($name, -1) === '/') {
            if (!is_dir($target) && !@mkdir($target, 0755, true)) {
                return false;
            }
            continue;
        }
        if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0755, true)) {
            return false;
        }
        $data = $zip->getFromIndex($i);
        if ($data === false || @file_put_contents($target, $data) === false) {
            return false;
        }
    }
    return true;
}

/**
 * 설치·업데이트: app 은 새 폴더에 먼저 풀어 통째로 바꾸고(이전 것은 app.bak-날짜 로 보관),
 * www 파일은 덮어씁니다. 반환: 설치한 버전, 실패하면 null ($error 에 이유)
 */
function pkg_install($path, $rootDir, $publicDir, &$error)
{
    $pkg = pkg_open($path);
    if (is_string($pkg)) {
        $error = $pkg;
        return null;
    }
    $app = $rootDir . '/app';
    $new = $rootDir . '/app.new-' . bin2hex(random_bytes(4));
    $ok = pkg_extract($pkg, 'app/', $new, array('config.local.php'))
        && pkg_extract($pkg, 'www/', $publicDir, array('uploads/', 'install.php'));
    $pkg['zip']->close();
    if (!$ok) {
        pkg_rmdir($new);
        $error = '파일을 풀지 못했어요. www 폴더와 그 바깥 폴더의 쓰기 권한을 확인해 주세요.';
        return null;
    }
    // 서버에서 직접 만든 설정(MySQL 등)은 새 버전에도 그대로 옮깁니다.
    if (is_file($app . '/config.local.php')) {
        @copy($app . '/config.local.php', $new . '/config.local.php');
    }
    $backup = null;
    if (is_dir($app)) {
        $backup = $rootDir . '/app.bak-' . date('Ymd-His');
        if (!@rename($app, $backup)) {
            pkg_rmdir($new);
            $error = '기존 프로그램 폴더를 바꾸지 못했어요. 폴더 권한을 확인해 주세요.';
            return null;
        }
    }
    if (!@rename($new, $app)) {
        if ($backup) {
            @rename($backup, $app);
        }
        pkg_rmdir($new);
        $error = '새 프로그램 폴더로 바꾸지 못했어요. 폴더 권한을 확인해 주세요.';
        return null;
    }
    $backups = glob($rootDir . '/app.bak-*', GLOB_ONLYDIR) ?: array();
    rsort($backups);
    foreach (array_slice($backups, PKG_KEEP_BACKUPS) as $old) {
        pkg_rmdir($old);
    }
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    return $pkg['version'];
}

function pkg_rmdir($dir)
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($dir);
}
