<?php /** 관리자 › 마켓 운영 탭. 변수: $tab */
$counts = market_pending_counts();
$tabs = array(
    'apps' => array('/admin/market', '마켓 운영 신청', $counts['applications']),
    'referrers' => array('/admin/market/referrers', '추천인', $counts['referrers']),
    'withdrawals' => array('/admin/market/withdrawals', '출금 신청', $counts['withdrawals']),
);
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>마켓 운영</h1>
    <p class="muted">회원이 ‘나의 마켓’에서 보낸 마켓 운영 신청, 추천인 신청, 수익 출금 신청을 처리해요. 추천 수익은 결제금액의 <?= REFERRAL_RATE ?>%이고, 입금을 확인한 신청만 수익으로 잡혀요.</p>
  </div>
</div>
<nav class="tabs big-tabs" aria-label="마켓 운영 메뉴">
<?php foreach ($tabs as $key => $t): ?>
  <a href="<?= $t[0] ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>><?= e($t[1]) ?><?php if ($t[2]): ?> <span class="nav-badge"><?= (int) $t[2] ?></span><?php endif; ?></a>
<?php endforeach; ?>
</nav>
