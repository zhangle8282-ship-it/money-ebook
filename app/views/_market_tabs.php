<?php /** 나의 마켓 안의 메뉴. 변수: $tab (apply|referral) */ ?>
<nav class="subnav" aria-label="나의 마켓 메뉴">
  <a href="/market"<?= $tab === 'apply' ? ' aria-current="page"' : '' ?>>솔루션 신청</a>
  <a href="/market/referral"<?= $tab === 'referral' ? ' aria-current="page"' : '' ?>>추천인 · 수익</a>
<?php if (seller_enabled()): ?>  <a href="/market/sell"<?= $tab === 'sell' ? ' aria-current="page"' : '' ?>>내 전자책 판매</a>
<?php endif; ?>
</nav>
