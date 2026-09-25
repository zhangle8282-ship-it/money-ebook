<?php
/** 판매자(오픈마켓) 전자책 등록 화면의 틀. 관리자 화면과 같은 모양을 쓰되 관리자 메뉴 대신 판매자 메뉴를 보여 줍니다. */
$store = setting('store_name');
$flash = flash();
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · <?= e($store) ?></title>
<?= icon_links() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600&family=Noto+Serif+KR:wght@700&display=swap">
<link rel="stylesheet" href="/assets/admin.css?v=<?= @filemtime(PUBLIC_DIR . '/assets/admin.css') ?>">
</head>
<body class="admin seller">
<a class="skip-link" href="#main">본문 바로가기</a>
<header class="seller-top">
  <div class="sidebar-brand">
    <a class="brand-name" href="/"><?= e($store) ?></a>
    <span class="brand-badge">판매자</span>
  </div>
  <nav class="seller-nav" aria-label="판매자 메뉴">
    <a href="/market/sell">내 전자책 판매</a>
    <a href="/market">나의 마켓</a>
    <a href="/">스토어로</a>
  </nav>
</header>
<main id="main" class="admin-main seller-main">
<?php if ($flash): ?>
  <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?= $content ?>
</main>
<script src="/assets/admin.js?v=<?= @filemtime(PUBLIC_DIR . '/assets/admin.js') ?>" defer></script>
</body>
</html>
