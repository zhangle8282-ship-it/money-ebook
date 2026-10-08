<?php
/** 관리자 공통 틀: 왼쪽 메뉴 + 본문. */
$store = site_name();
$flash = flash();
$cleaning = SITE_MODE === 'cleaning';
$pendingBadge = $bookBadge = $marketBadge = 0;
if ($cleaning) {
    $inquiryBadge = (int) q_value("SELECT COUNT(*) FROM inquiries WHERE status = 'new'");
    $onetimeBadge = onetime_pending_count();
} else {
    $pendingBadge = (int) q_value("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $counts = market_pending_counts();
    $bookBadge = $counts['reviews'];
    $marketBadge = $counts['applications'] + $counts['referrers'] + $counts['withdrawals'];
}
$menu = $cleaning ? array(
    'inquiries' => array('/admin/inquiries', '견적 문의', '<path d="M4 4h16v12H7l-3 3z"></path><path d="M8 9h8M8 12h5"></path>'),
    'contracts' => array('/admin/contracts', '정기청소 정산', '<rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 7h8M8 11h2M12 11h2M16 11h0M8 15h2M12 15h2M8 18h8"></path>'),
    'onetime' => array('/admin/onetime', '일회성 정산', '<rect x="4" y="4" width="16" height="17" rx="2"></rect><path d="M8 2v4M16 2v4M4 10h16"></path><path d="M9 15l2 2 4-4"></path>'),
    'workers' => array('/admin/workers', '인력 배치', '<circle cx="9" cy="8" r="3.5"></circle><path d="M2.5 20a6.5 6.5 0 0 1 13 0"></path><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 3.5 5.5"></path>'),
    'site' => array('/admin/site', '홈페이지 관리', '<path d="M3 10l9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"></path>'),
    'photos' => array('/admin/photos', '사진 관리', '<rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="9" cy="10" r="2"></circle><path d="M21 16l-5-5-8 8"></path>'),
    'blog' => array('/admin/blog', '블로그', '<path d="M4 20h4L19 9l-4-4L4 16z"></path><path d="M13 7l4 4"></path>'),
    'pages' => array('/admin/pages', '검색어 페이지', '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"></path><circle cx="12" cy="9.5" r="2.5"></circle>'),
    'search' => array('/admin/search', '검색 등록', '<circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4.2-4.2"></path><path d="M8 11h6M11 8v6"></path>'),
    'visits' => array('/admin/visits', '유입 경로', '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path>'),
    'usage' => array('/admin/usage', '용량 · 트래픽', '<ellipse cx="12" cy="5" rx="8" ry="3"></ellipse><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"></path><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"></path>'),
    'reviews' => array('/admin/reviews', '후기 관리', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"></path>'),
    'code' => array('/admin/code', '헤드 코드', '<path d="M8 8l-4 4 4 4"></path><path d="M16 8l4 4-4 4"></path><path d="M13.5 5l-3 14"></path>'),
    'account' => array('/admin/account', '계정', '<circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>'),
) : array(
    'dashboard' => array('/admin', '대시보드', '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>'),
    'books' => array('/admin/books', '전자책 관리', '<path d="M4 19V5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"></path><path d="M4 19a2 2 0 0 0 2 2h13"></path>'),
    'reviews' => array('/admin/reviews', '리뷰 관리', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"></path>'),
    'orders' => array('/admin/orders', '주문 내역', '<path d="M6 2h12l2 5H4z"></path><path d="M4 7v13h16V7"></path><path d="M9 11h6"></path>'),
    'market' => array('/admin/market', '마켓 운영', '<path d="M3 9l1.5-5h15L21 9"></path><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"></path><path d="M5 13v7h14v-7"></path>'),
    'design' => array('/admin/design', '디자인', '<circle cx="13.5" cy="6.5" r="1.5"></circle><circle cx="17.5" cy="10.5" r="1.5"></circle><circle cx="8.5" cy="7.5" r="1.5"></circle><circle cx="6.5" cy="12.5" r="1.5"></circle><path d="M12 2a10 10 0 0 0 0 20c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3-.3-.4-.5-.8-.5-1.3 0-1.1.9-2 2-2h2.4A5.6 5.6 0 0 0 22 9.8C22 5.5 17.5 2 12 2z"></path>'),
    'settings' => array('/admin/settings', '설정', '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"></path>'),
);
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · <?= e($store) ?> 관리자</title>
<?= icon_links() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600&family=Noto+Serif+KR:wght@700&display=swap">
<link rel="stylesheet" href="/assets/admin.css?v=<?= @filemtime(PUBLIC_DIR . '/assets/admin.css') ?>-<?= @filesize(PUBLIC_DIR . '/assets/admin.css') ?>">
<?php /* 안전장치: 배포 중(파일이 반쯤 올라간 순간)이나 예전 파일이 남아 있어도 휴대폰에서 기본 틀은 유지되게 핵심 틀만 CSS 파일 뒤에 직접 넣어 둡니다(뒤에 있어야 반쯤 올라간 파일이 덮어쓰지 못함). 자세한 모양은 admin.css */ ?>
<style>
html{-webkit-text-size-adjust:100%;text-size-adjust:100%}
.admin-shell{display:flex;min-height:100vh}.admin-main{flex:1;min-width:0}
@media (max-width:860px){
.admin-shell{flex-direction:column}
.sidebar{width:100%;height:auto;position:static;padding:12px;gap:8px;border-right:0;border-bottom:1px solid #E3DED3;overflow:visible}
.sidebar-nav{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:4px;overflow:visible}
.sidebar-nav a{flex-direction:column;justify-content:center;gap:3px;height:auto;min-height:56px;padding:7px 2px;font-size:12px;line-height:1.25;text-align:center;word-break:keep-all}
.sidebar-foot{flex-direction:row;justify-content:space-between;margin-top:0}
.admin-main{padding:24px 16px 48px;gap:20px}
.grid-2,.grid-3{grid-template-columns:1fr}
select,input,textarea{max-width:100%}
}
</style>
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
<?php
// 그린청소: 매일 쓰는 업무(견적 문의 · 정기청소 정산 · 일회성 정산 · 인력 배치, 녹색)와 홈페이지를 고치는 관리 메뉴(주황색)를 색과 제목으로 나눕니다.
$workKeys = $cleaning ? array('inquiries', 'contracts', 'onetime', 'workers') : array();
// ‘업무만’ 아이디는 업무 메뉴만 보여 줍니다(다른 메뉴는 주소로 열어도 막힘).
$workOnly = $cleaning && ($admin['access'] ?? 'all') === 'work';
if ($workOnly) {
    $menu = array_intersect_key($menu, array_flip($workKeys));
}
$group = '';
foreach ($menu as $key => $m):
    $isWork = in_array($key, $workKeys, true);
    if ($cleaning && $group !== ($isWork ? 'work' : 'site')):
        $group = $isWork ? 'work' : 'site'; ?>
      <span class="nav-group nav-group-<?= $group ?>"><?= $isWork ? '업무' : '홈페이지 관리' ?></span>
<?php endif; ?>
      <a href="<?= $m[0] ?>"<?= $isWork ? ' class="nav-work"' : ($cleaning ? ' class="nav-site"' : '') ?><?= ($nav ?? '') === $key ? ' aria-current="page"' : '' ?>>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $m[2] ?></svg><?= e($m[1]) ?>
<?php if ($key === 'inquiries' && !empty($inquiryBadge)): ?>        <span class="nav-badge" aria-label="새 문의 <?= $inquiryBadge ?>건"><?= $inquiryBadge ?></span>
<?php elseif ($key === 'onetime' && !empty($onetimeBadge)): ?>        <span class="nav-badge" aria-label="정산 전 <?= $onetimeBadge ?>건"><?= $onetimeBadge ?></span>
<?php elseif ($key === 'orders' && $pendingBadge): ?>        <span class="nav-badge" aria-label="입금 대기 <?= $pendingBadge ?>건"><?= $pendingBadge ?></span>
<?php elseif ($key === 'market' && $marketBadge): ?>        <span class="nav-badge" aria-label="처리할 일 <?= $marketBadge ?>건"><?= $marketBadge ?></span>
<?php elseif ($key === 'books' && $bookBadge): ?>        <span class="nav-badge" aria-label="승인 대기 <?= $bookBadge ?>권"><?= $bookBadge ?></span>
<?php endif; ?>
      </a>
<?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="/" class="sidebar-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg><?= $cleaning ? '홈페이지 보기' : '스토어로 돌아가기' ?></a>
<?php if ($workOnly): ?>      <a href="/admin/account" class="sidebar-back"<?= ($nav ?? '') === 'account' ? ' aria-current="page"' : '' ?>>내 비밀번호 바꾸기</a>
<?php endif; ?>
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
