<?php /** 그린청소 단순 화면 틀(개인정보처리방침, 없는 페이지). 변수: $title, $content */
$name = gc('name');
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e($name) ?></title>
<meta name="theme-color" content="#2F7D5C">
<?= icon_links() ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
<link rel="stylesheet" href="/assets/green.css?v=<?= @filemtime(PUBLIC_DIR . '/assets/green.css') ?>">
<?= custom_code('head') ?>
</head>
<body class="g-body g-simple">
<header class="g-header">
  <div class="g-wrap g-header-inner">
    <a class="g-logo" href="/"><svg class="g-logo-mark" viewBox="0 0 66 64" aria-hidden="true"><path d="M47.6 16.4A22 22 0 1 0 54 32H36" fill="none" stroke="#2F7D5C" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/><path d="M50 14C50 7 55 3 62 3C62 10 57 14 50 14Z" fill="#2A2D33"/></svg><span class="g-logo-text"><?= e($name) ?></span></a>
    <a class="g-header-phone" href="<?= e(tel_href(gc('phone'))) ?>"><?= e(gc('phone')) ?></a>
  </div>
</header>
<main id="main" class="g-wrap g-page"><?= $content ?></main>
<footer class="g-footer g-footer-mini"><div class="g-wrap"><p class="g-copy">© <?= e($name) ?>. All rights reserved. <a href="/">홈으로</a></p></div></footer>
<?= custom_code('body') ?>
</body>
</html>
