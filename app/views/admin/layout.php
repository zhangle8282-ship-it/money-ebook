<?php
/** 관리자 공통 틀: 왼쪽 메뉴 + 본문. */
$store = setting('store_name');
$flash = flash();
$pendingBadge = (int) q_value("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$menu = array(
    'dashboard' => array('/admin', '대시보드', '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>'),
    'books' => array('/admin/books', '전자책 관리', '<path d="M4 19V5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"></path><path d="M4 19a2 2 0 0 0 2 2h13"></path>'),
    'reviews' => array('/admin/reviews', '리뷰 관리', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"></path>'),
    'orders' => array('/admin/orders', '주문 내역', '<path d="M6 2h12l2 5H4z"></path><path d="M4 7v13h16V7"></path><path d="M9 11h6"></path>'),
    'settings' => array('/admin/settings', '설정', '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"></path>'),
);
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · <?= e($store) ?> 관리자</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600&family=Noto+Serif+KR:wght@700&display=swap">
<link rel="stylesheet" href="/assets/admin.css?v=<?= @filemtime(PUBLIC_DIR . '/assets/admin.css') ?>">
</head>
<body class="admin">
<a class="skip-link" href="#main">본문 바로가기</a>
<div class="admin-shell">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="brand-name"><?= e($store) ?></span>
      <span class="brand-badge">관리자</span>
    </div>
    <nav class="sidebar-nav" aria-label="관리자 메뉴">
<?php foreach ($menu as $key => $m): ?>
      <a href="<?= $m[0] ?>"<?= ($nav ?? '') === $key ? ' aria-current="page"' : '' ?>>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $m[2] ?></svg><?= e($m[1]) ?>
<?php if ($key === 'orders' && $pendingBadge): ?>        <span class="nav-badge" aria-label="입금 대기 <?= $pendingBadge ?>건"><?= $pendingBadge ?></span>
<?php endif; ?>
      </a>
<?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="/" class="sidebar-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>스토어로 돌아가기</a>
      <form method="post" action="/admin/logout"><?= csrf_field() ?><button type="submit" class="sidebar-logout"><?= e($admin['username']) ?> · 로그아웃</button></form>
      <span class="sidebar-version">버전 <?= e(app_version()) ?></span>
    </div>
  </aside>
  <main id="main" class="admin-main">
<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
<?= $content ?>
  </main>
</div>
<script src="/assets/admin.js?v=<?= @filemtime(PUBLIC_DIR . '/assets/admin.js') ?>" defer></script>
</body>
</html>
