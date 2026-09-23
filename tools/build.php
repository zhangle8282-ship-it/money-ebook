<?php
/**
 * 설치 파일 만들기
 *   php tools/build.php [출력 폴더]
 *
 * 출력 폴더(기본 dist/)에 세 파일을 만듭니다.
 *   ebook-store-<버전>.zip   설치 파일 (www/, app/ — 설치 도구나 관리자 업데이트에 올림)
 *   install.php              설치 도구 (서버 www 에 이 파일 하나만 올림)
 *   설치방법.txt              설치 방법과 설치 코드
 * 설치 코드는 만들 때마다 새로 정해지고, 같은 번에 만든 install.php 에서만 통해요.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "PHP zip 확장이 필요해요.\n");
    exit(1);
}

$root = dirname(__DIR__);
$out = $argv[1] ?? $root . '/dist';
$version = trim(file_get_contents($root . '/app/VERSION'));
$name = 'ebook-store-' . $version;
if (!is_dir($out)) {
    mkdir($out, 0755, true);
}

// 헷갈리는 글자(0/O, 1/I/L)를 뺀 8자리 코드: ABCD-EFGH
$alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 8; $i++) {
    $code .= ($i === 4 ? '-' : '') . $alphabet[random_int(0, strlen($alphabet) - 1)];
}
$fill = function ($text) use ($code, $version) {
    return str_replace(array('@@INSTALL_CODE@@', '@@VERSION@@'), array($code, $version), $text);
};
$guide = $fill(file_get_contents(__DIR__ . '/install-guide.txt'));

/** $dir 아래 파일을 zip 의 $prefix 아래에 넣습니다. $skip: 뺄 경로(앞부분) */
function add_tree(ZipArchive $zip, $dir, $prefix, $skip)
{
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    $zip->addEmptyDir(rtrim($prefix, '/'));
    foreach ($it as $file) {
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        foreach ($skip as $s) {
            if ($rel === rtrim($s, '/') || strncmp($rel, $s, strlen($s)) === 0 || basename($rel) === '.DS_Store') {
                continue 2;
            }
        }
        if ($file->isDir()) {
            $zip->addEmptyDir($prefix . $rel);
        } else {
            $zip->addFile($file->getPathname(), $prefix . $rel);
        }
    }
}

$zipPath = $out . '/' . $name . '.zip';
@unlink($zipPath);
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);
add_tree($zip, $root . '/app', $name . '/app/', array('config.local.php'));
add_tree($zip, $root . '/public', $name . '/www/', array('uploads/', 'install.php'));
$zip->addFromString($name . '/설치방법.txt', $guide);
$zip->close();

// 설치 도구: 템플릿에 설치 코드·버전을 넣고 zip 처리 코드(app/package.php)를 그대로 붙입니다.
$lib = file_get_contents($root . '/app/package.php');
$lib = preg_replace('/^<\?php\s*/', '', $lib);
$installer = str_replace('/*@@PACKAGE_LIB@@*/', $lib, $fill(file_get_contents(__DIR__ . '/install.template.php')));
file_put_contents($out . '/install.php', $installer);
file_put_contents($out . '/설치방법.txt', $guide);

echo "버전 {$version}\n";
echo "설치 코드 {$code}\n";
foreach (array($name . '.zip', 'install.php', '설치방법.txt') as $f) {
    echo '  ' . $out . '/' . $f . ' (' . number_format(filesize($out . '/' . $f)) . " bytes)\n";
}
