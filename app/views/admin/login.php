<?php /** 관리자 로그인 / 첫 실행 시 관리자 계정 만들기. */ ?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · <?= e(setting('store_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600&family=Noto+Serif+KR:wght@700&display=swap">
<link rel="stylesheet" href="/assets/admin.css?v=<?= @filemtime(PUBLIC_DIR . '/assets/admin.css') ?>">
</head>
<body class="admin admin-auth">
<main class="auth-card">
  <div class="sidebar-brand auth-brand">
    <span class="brand-name"><?= e(setting('store_name')) ?></span>
    <span class="brand-badge">관리자</span>
  </div>
  <h1><?= e($title) ?></h1>
<?php if ($setup): ?>
  <p class="muted">처음 한 번만 보이는 화면이에요. 앞으로 쓸 관리자 아이디와 비밀번호를 정해 주세요.</p>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert" role="alert"><?= e($error) ?></div>
<?php endif; ?>
  <form method="post" action="/admin/login" class="stack">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="field">
      <label for="username">아이디</label>
      <input id="username" name="username" type="text" required autocomplete="username" value="<?= e($username) ?>"<?= $setup ? ' pattern="[A-Za-z0-9_.\-]{3,30}"' : '' ?>>
    </div>
    <div class="field">
      <label for="password">비밀번호</label>
      <input id="password" name="password" type="password" required autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>"<?= $setup ? ' minlength="10"' : '' ?>>
<?php if ($setup): ?>      <span class="field-help">10자 이상</span>
<?php endif; ?>
    </div>
<?php if ($setup): ?>
    <div class="field">
      <label for="password2">비밀번호 확인</label>
      <input id="password2" name="password2" type="password" required minlength="10" autocomplete="new-password">
    </div>
<?php endif; ?>
    <button type="submit" class="btn btn-primary btn-block"><?= $setup ? '관리자 계정 만들기' : '로그인' ?></button>
  </form>
  <a class="auth-back" href="/">스토어로 돌아가기</a>
</main>
</body>
</html>
