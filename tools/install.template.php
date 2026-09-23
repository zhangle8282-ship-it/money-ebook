<?php
/**
 * 전자책 스토어 설치 도구 (버전 @@VERSION@@)
 *
 * 1) 이 파일(install.php) 하나만 서버의 www 폴더에 올립니다.
 * 2) 브라우저에서 https://내도메인/install.php 를 엽니다.
 * 3) 설치 코드와 몇 가지 정보를 넣고 설치 zip 파일을 올리면 자동으로 설치됩니다.
 * 설치가 끝나면 이 파일은 스스로 지워집니다.
 *
 * 이 파일은 tools/build.php 가 만듭니다. 직접 고치지 말고 tools/install.template.php 를 고치세요.
 */
define('INSTALL_CODE', '@@INSTALL_CODE@@');
define('INSTALL_VERSION', '@@VERSION@@');
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Seoul');

/*@@PACKAGE_LIB@@*/

$root = dirname(__DIR__);

function inst_h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 이미 관리자가 있는 사이트면 이 도구로 덮어쓰지 않습니다(업데이트는 관리자 화면에서). */
function inst_already_installed($root)
{
    if (is_file($root . '/app/config.local.php')) {
        return true;
    }
    $db = $root . '/storage/store.sqlite';
    if (!is_file($db) || !class_exists('PDO')) {
        return false;
    }
    try {
        $pdo = new PDO('sqlite:' . $db);
        return (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function inst_post($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}

function inst_page($title, $body)
{
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    ?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= inst_h($title) ?> · 전자책 스토어 설치</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%232E5E4E'/%3E%3Cpath d='M32 19.5c-5.2-3.6-11.6-4.6-18-3.6v29c6.4-1 12.8 0 18 3.6 5.2-3.6 11.6-4.6 18-3.6v-29c-6.4-1-12.8 0-18 3.6z' fill='%23F6F4EF'/%3E%3Cpath d='M32 20v28' stroke='%232E5E4E' stroke-width='2.4' stroke-linecap='round'/%3E%3C/svg%3E">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600&family=Noto+Serif+KR:wght@700&display=swap">
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:#F6F4EF;color:#1D1C1A;font-family:'IBM Plex Sans KR',system-ui,-apple-system,sans-serif;-webkit-font-smoothing:antialiased;padding:48px 16px}
a{color:#2E5E4E}
.card{max-width:600px;margin:0 auto;background:#fff;border:1px solid #E3DED3;border-radius:16px;padding:36px 32px;display:flex;flex-direction:column;gap:20px}
.brand{display:flex;align-items:center;gap:8px}
.brand b{font-family:'Noto Serif KR',serif;font-size:19px}
.badge{padding:2px 8px;border-radius:10px;background:#1D1C1A;color:#fff;font-size:11px;font-weight:600}
h1{margin:0;font-size:24px;font-weight:600}
h2{margin:0;font-size:16px;font-weight:600}
p{margin:0;font-size:15px;line-height:1.7;color:#3A3833}
.muted{color:#5F5B53;font-size:14px}
.checks{list-style:none;margin:0;padding:14px 16px;background:#FAF9F5;border:1px solid #E3DED3;border-radius:10px;display:flex;flex-direction:column;gap:6px;font-size:14px}
.checks li{display:flex;gap:8px;align-items:flex-start}
.ok{color:#2E5E4E;font-weight:600}.no{color:#A23B2A;font-weight:600}
.checks small{display:block;color:#5F5B53}
.section{display:flex;flex-direction:column;gap:14px;padding-top:4px}
.field{display:flex;flex-direction:column;gap:6px}
.field label{font-size:14px;font-weight:500}
.req{color:#A23B2A}
input[type=text],input[type=password]{width:100%;height:44px;padding:0 14px;border:1px solid #D6D0C4;border-radius:8px;font:inherit;font-size:15px;color:#1D1C1A;background:#fff}
input:focus{outline:none;border-color:#2E5E4E;box-shadow:0 0 0 3px rgba(46,94,78,.15)}
.code{font-size:18px;letter-spacing:.12em;text-transform:uppercase}
.row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.row3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.help{font-size:13px;color:#5F5B53;line-height:1.6}
.drop{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;min-height:120px;padding:20px;border:1.5px dashed #C9C2B4;border-radius:10px;background:#FAF9F5;text-align:center;cursor:pointer}
.drop:hover,.drop:focus-within{border-color:#2E5E4E;background:#F3F6F2}
.drop input{position:absolute;inset:0;opacity:0;cursor:pointer}
.drop b{font-size:15px;font-weight:500}
.drop span{font-size:13px;color:#5F5B53}
.btn{display:inline-flex;align-items:center;justify-content:center;height:52px;padding:0 24px;border:0;border-radius:10px;background:#2E5E4E;color:#fff;font:inherit;font-size:16px;font-weight:600;cursor:pointer;text-decoration:none}
.btn:hover{background:#1F4337;color:#fff}
.btn:disabled{background:#9DB3AA;cursor:progress}
.btn-outline{background:#fff;color:#1D1C1A;border:1px solid #D6D0C4}
.btn-outline:hover{background:#FAF9F5;color:#1D1C1A}
.actions{display:flex;gap:10px;flex-wrap:wrap}
.alert{padding:14px 16px;border-radius:10px;background:#F6E4DF;color:#7A2A1D;font-size:14px;line-height:1.6}
.alert p{color:inherit;font-size:14px}
.success{padding:14px 16px;border-radius:10px;background:#E4EDE8;color:#1F4337;font-size:15px}
ol{margin:0;padding-left:20px;font-size:15px;line-height:1.8;color:#3A3833}
form{display:flex;flex-direction:column;gap:22px}
hr{border:0;border-top:1px solid #E3DED3;margin:0}
@media (max-width:560px){.card{padding:28px 20px}.row,.row3{grid-template-columns:1fr}}
</style>
</head>
<body>
<main class="card">
  <div class="brand"><b>전자책 스토어</b><span class="badge">설치</span></div>
<?= $body ?>
</main>
</body>
</html>
<?php
    exit;
}

/* ───────── 이미 설치된 사이트 ───────── */

if (inst_already_installed($root)) {
    $deleted = @unlink(__FILE__);
    inst_page('이미 설치됨', '<h1>이미 설치된 사이트예요</h1>'
        . '<p>관리자 계정이 있는 사이트라 설치 도구로 덮어쓰지 않았어요. 새 버전으로 바꾸려면 '
        . '<b>관리자 › 설정 › 프로그램 업데이트</b>에서 zip 파일을 올려 주세요.</p>'
        . ($deleted ? '<p class="muted">안전을 위해 설치 도구(install.php)는 지웠어요.</p>'
            : '<div class="alert"><p>설치 도구(install.php)를 지우지 못했어요. FTP로 www 폴더의 install.php 를 지워 주세요.</p></div>')
        . '<div class="actions"><a class="btn" href="/admin">관리자 화면으로</a><a class="btn btn-outline" href="/">스토어 보기</a></div>');
}

/* ───────── 서버 점검 ───────── */

$checks = array(
    array('PHP 7.3 이상', PHP_VERSION_ID >= 70300, '지금 PHP ' . PHP_VERSION . '. 호스팅 관리 화면에서 PHP 버전을 7.4 이상으로 바꿔 주세요.'),
    array('zip 풀기 기능', class_exists('ZipArchive'), '호스팅 업체에 PHP zip 확장을 켜 달라고 요청해 주세요.'),
    array('SQLite 데이터베이스', extension_loaded('pdo_sqlite'), '호스팅 업체에 PHP pdo_sqlite 확장을 켜 달라고 요청해 주세요.'),
    array('HTML 처리(DOM)', class_exists('DOMDocument'), '호스팅 업체에 PHP dom 확장을 켜 달라고 요청해 주세요.'),
    array('www 폴더 쓰기', is_writable(__DIR__), 'FTP 프로그램에서 www 폴더 권한을 707로 바꿔 주세요.'),
    array('www 바깥 폴더 쓰기', is_writable($root), 'FTP 최상위 폴더에 쓸 수 없어요. 호스팅 업체에 문의해 주세요.'),
);
$ready = true;
foreach ($checks as $c) {
    $ready = $ready && $c[1];
}

/* ───────── 설치 ───────── */

$errors = array();
$form = array(
    'code' => inst_post('code'), 'store_name' => inst_post('store_name') !== '' ? inst_post('store_name') : '전자책 스토어',
    'username' => inst_post('username'), 'bank_name' => inst_post('bank_name'),
    'bank_account' => inst_post('bank_account'), 'bank_holder' => inst_post('bank_holder'),
);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = '올린 파일이 서버 한도(' . ini_get('post_max_size') . ')보다 커요.';
    } else {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $form['code']));
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $password2 = isset($_POST['password2']) && is_string($_POST['password2']) ? $_POST['password2'] : '';
        if (!hash_equals(str_replace('-', '', INSTALL_CODE), $code)) {
            usleep(800000);
            $errors[] = '설치 코드가 맞지 않아요. 설치방법.txt 맨 위에 있는 코드를 넣어 주세요.';
        }
        if ($form['store_name'] === '' || preg_match_all('/./us', $form['store_name']) > 40) {
            $errors[] = '스토어 이름을 40자 이내로 넣어 주세요.';
        }
        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $form['username'])) {
            $errors[] = '관리자 아이디는 영문·숫자 3~30자로 정해 주세요.';
        }
        if (strlen($password) < 10) {
            $errors[] = '관리자 비밀번호는 10자 이상으로 정해 주세요.';
        } elseif ($password !== $password2) {
            $errors[] = '비밀번호 확인이 일치하지 않아요.';
        }
        $file = isset($_FILES['package']) && is_array($_FILES['package']) ? $_FILES['package'] : null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = '설치 파일(zip)을 선택해 주세요.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = $file['error'] === UPLOAD_ERR_INI_SIZE ? '설치 파일이 서버 업로드 한도(' . ini_get('upload_max_filesize') . ')보다 커요.' : '설치 파일을 받지 못했어요. 다시 시도해 주세요.';
        }
        if (!$ready) {
            $errors[] = '서버 점검에서 통과하지 못한 항목이 있어요.';
        }

        if (!$errors) {
            $error = '';
            $version = pkg_install($file['tmp_name'], $root, __DIR__, $error);
            if ($version === null) {
                $errors[] = $error;
            } else {
                // 설치한 프로그램을 불러와 관리자 계정과 기본 설정을 만듭니다.
                define('PUBLIC_DIR', __DIR__);
                require $root . '/app/bootstrap.php';
                $adminId = q_insert('admins', array(
                    'username' => $form['username'],
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'created_at' => now(),
                ));
                $settings = array('store_name' => $form['store_name']);
                foreach (array('bank_name', 'bank_account', 'bank_holder') as $k) {
                    if ($form[$k] !== '') {
                        $settings[$k] = $form[$k];
                    }
                }
                save_settings($settings);
                login_admin(q_one('SELECT * FROM admins WHERE id = ?', array($adminId)));
                // 호스팅 기본 페이지(index.html)가 있으면 스토어보다 먼저 보이므로 이름을 바꿔 둡니다.
                if (is_file(__DIR__ . '/index.html')) {
                    @rename(__DIR__ . '/index.html', __DIR__ . '/index.html.bak');
                }
                $deleted = @unlink(__FILE__);
                $bankNote = bank_ready() ? '' : '<li>관리자 › 설정에서 <b>입금 계좌</b>를 넣어 주세요. 계좌가 없으면 주문을 받지 않아요.</li>';
                inst_page('설치 완료', '<h1>설치가 끝났어요</h1>'
                    . '<div class="success">전자책 스토어 ' . inst_h($version) . ' 버전을 설치했고, 관리자 <b>' . inst_h($form['username']) . '</b> 계정으로 로그인했어요.</div>'
                    . ($deleted ? '' : '<div class="alert"><p>설치 도구(install.php)를 스스로 지우지 못했어요. 다른 사람이 쓰지 못하게 FTP로 www 폴더의 install.php 를 꼭 지워 주세요.</p></div>')
                    . '<h2>다음에 할 일</h2><ol>' . $bankNote
                    . '<li>관리자 › 설정에서 <b>사업자 정보</b>를 넣어 주세요.</li>'
                    . '<li>관리자 › 전자책 관리 › <b>새 전자책 등록</b>에서 책을 올려 주세요.</li></ol>'
                    . '<div class="actions"><a class="btn" href="/admin">관리자 화면으로</a><a class="btn btn-outline" href="/">스토어 보기</a></div>');
            }
        }
    }
}

/* ───────── 설치 화면 ───────── */

ob_start();
?>
  <h1>전자책 스토어 설치</h1>
  <p class="muted">몇 가지 정보를 넣고 설치 파일(zip)을 올리면 자동으로 설치돼요. 버전 <?= inst_h(INSTALL_VERSION) ?></p>

  <ul class="checks" aria-label="서버 점검">
<?php foreach ($checks as $c): ?>
    <li><span class="<?= $c[1] ? 'ok' : 'no' ?>"><?= $c[1] ? '통과' : '확인 필요' ?></span><span><?= inst_h($c[0]) ?><?php if (!$c[1]): ?><small><?= inst_h($c[2]) ?></small><?php endif; ?></span></li>
<?php endforeach; ?>
  </ul>

<?php if ($errors): ?>
  <div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= inst_h($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="install-form">
    <div class="section">
      <div class="field">
        <label for="code">설치 코드 <span class="req">*</span></label>
        <input id="code" name="code" type="text" class="code" required autocomplete="off" placeholder="XXXX-XXXX" value="<?= inst_h($form['code']) ?>">
        <span class="help">설치방법.txt 맨 위에 적힌 코드예요. 설치 파일을 가진 사람만 설치할 수 있게 해요.</span>
      </div>
      <div class="field">
        <label for="store_name">스토어 이름 <span class="req">*</span></label>
        <input id="store_name" name="store_name" type="text" required maxlength="40" value="<?= inst_h($form['store_name']) ?>">
      </div>
    </div>
    <hr>
    <div class="section">
      <h2>관리자 계정</h2>
      <div class="field">
        <label for="username">아이디 <span class="req">*</span></label>
        <input id="username" name="username" type="text" required autocomplete="username" pattern="[A-Za-z0-9_.\-]{3,30}" value="<?= inst_h($form['username']) ?>">
        <span class="help">영문·숫자 3~30자</span>
      </div>
      <div class="row">
        <div class="field">
          <label for="password">비밀번호 <span class="req">*</span></label>
          <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
        </div>
        <div class="field">
          <label for="password2">비밀번호 확인 <span class="req">*</span></label>
          <input id="password2" name="password2" type="password" required minlength="10" autocomplete="new-password">
        </div>
      </div>
      <span class="help">10자 이상. 설치 뒤 관리자 화면(/admin) 로그인에 써요.</span>
    </div>
    <hr>
    <div class="section">
      <h2>입금 계좌 <span class="muted">(선택 · 나중에 설정에서 넣어도 돼요)</span></h2>
      <div class="row3">
        <div class="field"><label for="bank_name">은행</label><input id="bank_name" name="bank_name" type="text" placeholder="예: 국민은행" value="<?= inst_h($form['bank_name']) ?>"></div>
        <div class="field"><label for="bank_account">계좌번호</label><input id="bank_account" name="bank_account" type="text" value="<?= inst_h($form['bank_account']) ?>"></div>
        <div class="field"><label for="bank_holder">예금주</label><input id="bank_holder" name="bank_holder" type="text" value="<?= inst_h($form['bank_holder']) ?>"></div>
      </div>
    </div>
    <hr>
    <div class="section">
      <h2>설치 파일 <span class="req">*</span></h2>
      <label class="drop" for="package">
        <b id="package-name">ebook-store-<?= inst_h(INSTALL_VERSION) ?>.zip 파일을 선택하세요</b>
        <span>압축을 풀지 않은 zip 파일 그대로 · 서버 업로드 한도 <?= inst_h(ini_get('upload_max_filesize')) ?></span>
        <input id="package" name="package" type="file" accept=".zip,application/zip" required>
      </label>
    </div>
    <button type="submit" class="btn" id="install-btn" style="width:100%"<?= $ready ? '' : ' disabled' ?>>설치하기</button>
  </form>
  <script>
  (function () {
    var input = document.getElementById('package');
    input.addEventListener('change', function () {
      if (input.files[0]) document.getElementById('package-name').textContent = input.files[0].name;
    });
    document.getElementById('install-form').addEventListener('submit', function () {
      var btn = document.getElementById('install-btn');
      setTimeout(function () { btn.disabled = true; btn.textContent = '설치하는 중이에요…'; }, 0);
    });
  })();
  </script>
<?php
inst_page('설치', ob_get_clean());
