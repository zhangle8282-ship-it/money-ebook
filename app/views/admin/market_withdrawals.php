<?php /** 관리자 › 마켓 운영 › 출금 신청. */
$filters = array('' => '전체') + WITHDRAW_STATUS;
$back = $_SERVER['REQUEST_URI'] ?? '/admin/market/withdrawals';
?>
<?= view('admin/_market_tabs', array('tab' => $tab)) ?>
<div class="toolbar">
  <nav class="tabs" aria-label="출금 상태">
<?php foreach ($filters as $key => $label): ?>
    <a href="/admin/market/withdrawals<?= $key !== '' ? '?status=' . $key : '' ?>"<?= $status === $key || ($key === '' && !array_key_exists($status, WITHDRAW_STATUS)) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
  </nav>
</div>
<p class="muted small">계좌로 직접 보낸 뒤 ‘지급 완료’를 눌러 주세요. 반려하면 금액이 추천인의 출금 가능 금액으로 돌아가요.</p>
<section class="card flush">
<?php if ($rows): ?>
<div class="table-wrap">
<table class="table">
  <thead><tr><th scope="col">신청</th><th scope="col">추천인</th><th scope="col" class="num">금액</th><th scope="col">보낼 계좌</th><th scope="col">상태 · 처리</th></tr></thead>
  <tbody>
<?php foreach ($rows as $w): ?>
    <tr>
      <td><?= e(fmt_date($w['created_at'], 'Y.m.d H:i')) ?></td>
      <td><?= e($w['user_name'] ?? '(탈퇴)') ?><div class="sub"><?= e($w['user_email'] ?? '') ?><?= $w['code'] ? ' · ' . e($w['code']) : '' ?></div></td>
      <td class="num"><strong><?= won($w['amount']) ?></strong></td>
      <td><?= e($w['bank_name']) ?> <span class="mono"><?= e($w['bank_account']) ?></span><div class="sub">예금주 <?= e($w['bank_holder']) ?></div></td>
      <td>
<?php if ($w['status'] === 'requested'): ?>
        <form method="post" action="/admin/market/withdrawals/<?= (int) $w['id'] ?>" class="decide-form">
          <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
          <input type="text" name="admin_memo" maxlength="500" placeholder="메모 (선택)" aria-label="메모">
          <button type="submit" name="action" value="paid" class="btn btn-primary btn-sm" data-confirm-click="<?= e(won($w['amount'])) ?>을 보냈나요?">지급 완료</button>
          <button type="submit" name="action" value="rejected" class="btn btn-ghost btn-sm">반려</button>
        </form>
<?php else: ?>
        <span class="status withdraw-<?= e($w['status']) ?>"><?= e(WITHDRAW_STATUS[$w['status']]) ?></span>
        <div class="sub"><?= e(fmt_date($w['processed_at'], 'Y.m.d H:i')) ?><?= trim((string) $w['admin_memo']) !== '' ? ' · ' . e($w['admin_memo']) : '' ?></div>
<?php endif; ?>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php else: ?>
  <p class="muted pad">출금 신청이 없어요.</p>
<?php endif; ?>
</section>
