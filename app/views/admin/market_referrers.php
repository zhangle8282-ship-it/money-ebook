<?php /** 관리자 › 마켓 운영 › 추천인. */
$filters = array('' => '전체') + REFERRER_STATUS;
$back = $_SERVER['REQUEST_URI'] ?? '/admin/market/referrers';
?>
<?= view('admin/_market_tabs', array('tab' => $tab)) ?>
<div class="toolbar">
  <nav class="tabs" aria-label="추천인 상태">
<?php foreach ($filters as $key => $label): ?>
    <a href="/admin/market/referrers<?= $key !== '' ? '?status=' . $key : '' ?>"<?= $status === $key || ($key === '' && !array_key_exists($status, REFERRER_STATUS)) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
  </nav>
</div>
<section class="card flush">
<?php if ($rows): ?>
  <ul class="review-admin-list">
<?php foreach ($rows as $r): ?>
    <li class="review-admin">
      <div class="review-admin-head">
        <span class="status referrer-<?= e($r['status']) ?>"><?= e(REFERRER_STATUS[$r['status']]) ?></span>
        <strong><?= e($r['user_name'] ?? '(탈퇴)') ?></strong>
        <span class="sub"><?= e($r['user_email'] ?? '') ?> · <?= e(fmt_date($r['created_at'], 'Y.m.d H:i')) ?> 신청</span>
<?php if ($r['code']): ?>        <span class="code-pill"><?= e($r['code']) ?></span>
<?php endif; ?>
      </div>
      <p class="review-admin-body"><?= trim((string) $r['intro']) !== '' ? nl2br(e($r['intro']), false) : '<span class="muted">홍보 계획을 적지 않았어요.</span>' ?></p>
      <p class="sub">추천 가입 <?= (int) $r['referred'] ?>건 · 누적 수익 <?= won($r['earned']) ?><?= $r['bank_name'] !== '' ? ' · 출금 계좌 ' . e($r['bank_name'] . ' ' . $r['bank_account'] . ' (' . $r['bank_holder'] . ')') : '' ?><?= trim((string) $r['admin_memo']) !== '' ? ' · 메모: ' . e($r['admin_memo']) : '' ?></p>
      <form method="post" action="/admin/market/referrers/<?= (int) $r['user_id'] ?>" class="decide-form">
        <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
        <input type="text" name="admin_memo" maxlength="500" placeholder="메모 (반려 사유 등, 신청자에게 보여요)" aria-label="메모" value="<?= e($r['admin_memo']) ?>">
<?php if ($r['status'] !== 'approved'): ?>        <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">승인</button>
<?php endif; ?>
<?php if ($r['status'] !== 'rejected'): ?>        <button type="submit" name="action" value="reject" class="btn btn-ghost btn-sm"><?= $r['status'] === 'approved' ? '활동 중지' : '반려' ?></button>
<?php endif; ?>
      </form>
    </li>
<?php endforeach; ?>
  </ul>
<?php else: ?>
  <p class="muted pad">추천인 신청이 없어요.</p>
<?php endif; ?>
</section>
