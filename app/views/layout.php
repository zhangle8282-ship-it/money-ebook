<?php
/** 공개 화면 공통 틀: 헤더(메뉴·검색·로그인), 알림, 푸터. */
$store = setting('store_name');
$user = current_user();
$cartCount = cart_count();
$nav = $nav ?? '';
$flash = has_session() ? flash() : null;
$biz = business_lines();
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(!empty($title) ? $title . ' · ' . $store : $store) ?></title>
<meta name="description" content="<?= e($description ?? setting('hero_text')) ?>">
<?= icon_links() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= e(design_fonts_url()) ?>">
<link rel="stylesheet" href="/assets/store.css?v=<?= @filemtime(PUBLIC_DIR . '/assets/store.css') ?>">
<?= design_style() ?>
</head>
<body>
<a class="skip-link" href="#main">본문 바로가기</a>
<header class="site-header">
  <div class="wrap header-inner">
<?php $d = design(); ?>
    <a class="logo" href="/"><?php if ($d['design_logo_type'] === 'image'): ?><img src="<?= e($d['design_logo_image']) ?>" alt="<?= e($store) ?>"><?php else: ?><?= e($store) ?><?php endif; ?></a>
    <nav class="main-nav" aria-label="주요 메뉴">
<?php foreach (HOME_SORTS as $key => $item): ?>
      <a href="<?= $key === 'latest' ? '/' : '/?sort=' . $key ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>><?= e($item['nav']) ?></a>
<?php endforeach; ?>
<?php if ($user): ?>      <a href="/market" class="nav-market"<?= $nav === 'market' ? ' aria-current="page"' : '' ?>>나의 마켓</a>
<?php endif; ?>
    </nav>
    <form class="search" action="/" method="get" role="search">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
      <input type="search" name="q" aria-label="전자책 검색" placeholder="제목, 저자로 검색" value="<?= e($q ?? '') ?>">
    </form>
    <div class="header-actions">
      <a class="header-link cart-link" href="/cart">장바구니<?php if ($cartCount): ?> <span class="badge" aria-label="<?= $cartCount ?>권"><?= $cartCount ?></span><?php endif; ?></a>
<?php if ($user): ?>
      <a class="header-link" href="/library">내 서재</a>
      <form method="post" action="/logout"><?= csrf_field() ?><button type="submit" class="header-link link-button">로그아웃</button></form>
<?php else: ?>
      <a class="header-link" href="/login?next=<?= e(rawurlencode($_SERVER['REQUEST_URI'] ?? '/')) ?>">로그인</a>
<?php endif; ?>
    </div>
  </div>
</header>
<?php if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>" role="status"><div class="wrap"><?= e($flash['message']) ?></div></div>
<?php endif; ?>
<main id="main">
<?= $content ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-inner">
    <div class="footer-info">
      <span>© <?= e($store) ?><?= $biz ? '' : ' · [사업자 정보]' ?></span>
<?php if ($biz): ?>      <span class="footer-biz"><?= e(implode(' · ', $biz)) ?></span>
<?php endif; ?>
    </div>
    <nav class="footer-links" aria-label="안내">
      <a href="/terms">이용약관</a>
      <a href="/privacy">개인정보처리방침</a>
      <a href="/admin">관리자</a>
    </nav>
  </div>
</footer>
<script src="/assets/store.js?v=<?= @filemtime(PUBLIC_DIR . '/assets/store.js') ?>" defer></script>
</body>
</html>
