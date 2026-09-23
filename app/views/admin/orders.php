<?php /** 관리자 · 주문 내역(무통장 입금 확인). */
$tabs = array('' => '전체') + ORDER_STATUS;
$back = $_SERVER['REQUEST_URI'] ?? '/admin/orders';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>주문 내역</h1>
    <p class="muted">통장에 들어온 입금자명과 금액을 확인한 뒤 ‘입금 확인’을 누르면 주문자가 바로 내려받을 수 있어요.</p>
  </div>
</div>
<div class="toolbar">
  <nav class="tabs" aria-label="주문 상태">
<?php foreach ($tabs as $key => $label): ?>
    <a href="/admin/orders<?= $key !== '' ? '?status=' . $key : '' ?>"<?= $status === $key || ($key === '' && !array_key_exists($status, ORDER_STATUS)) ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if ($key !== ''): ?> <span class="count"><?= (int) ($counts[$key] ?? 0) ?></span><?php endif; ?></a>
<?php endforeach; ?>
  </nav>
  <form class="search-box" method="get" action="/admin/orders" role="search">
<?php if ($status !== ''): ?>    <input type="hidden" name="status" value="<?= e($status) ?>">
<?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="주문번호, 입금자명, 이메일" aria-label="주문 검색">
  </form>
</div>
<section class="card flush">
<?php if ($orders): ?>
  <?= view('admin/_order_rows', array('orders' => $orders, 'back' => $back)) ?>
<?php else: ?>
  <p class="muted pad">해당하는 주문이 없어요.</p>
<?php endif; ?>
</section>
