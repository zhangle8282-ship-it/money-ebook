<?php
/** 그린청소 공개 화면 머리말. 변수: $nav(구역 id => 이름), $onHome(첫 화면이면 #구역, 다른 화면이면 /#구역), $current('blog' 등) */
$name = gc('name');
$phone = gc('phone');
$kakao = gc('kakao_url');
$channelKey = gc('channeltalk_key');
$base = !empty($onHome) ? '' : '/';
?>
<body class="g-body<?= $kakao !== '' ? ' has-kakao' : '' ?><?= $channelKey !== '' ? ' has-channeltalk' : ' has-mobile-bar' ?>"<?= $channelKey !== '' ? ' data-channeltalk="' . e($channelKey) . '"' : '' ?>>
<a class="g-skip" href="#main">본문 바로가기</a>

<header class="g-header" data-header>
  <div class="g-wrap g-header-inner">
    <a class="g-logo" href="/" aria-label="<?= e($name) ?> 처음으로"><?= cleaning_logo() ?></a>
    <nav class="g-nav" id="g-nav" aria-label="주요 메뉴">
<?php foreach ($nav as $id => $label): ?>
      <a href="<?= $base ?>#<?= e($id) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
<?php if (blog_has_posts()): ?>      <a href="/blog"<?= ($current ?? '') === 'blog' ? ' aria-current="page"' : '' ?>>블로그</a>
<?php endif; ?>
      <a class="g-nav-quote" href="<?= $base ?>#quote">무료 견적 문의</a>
    </nav>
    <div class="g-header-cta">
<?php if ($kakao !== ''): ?>      <a class="g-btn-kakao g-btn-sm" href="<?= e($kakao) ?>" target="_blank" rel="noopener"><?= cleaning_kakao_icon() ?>카톡 상담</a>
<?php endif; ?>
      <a class="g-header-phone" href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a>
    </div>
    <button type="button" class="g-menu-btn" aria-controls="g-nav" aria-expanded="false" data-menu-btn>
      <span class="g-sr">메뉴 열기</span>
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
  </div>
</header>
