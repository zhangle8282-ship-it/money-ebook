<?php
/** 주문 상세: 입금 안내(입금 대기), 다운로드(결제 완료). */
$status = $order['status'];
?>
<section class="page wrap-narrow">
<?php if ($status === 'pending'): ?>
  <p class="eyebrow"><?= $isNew ? '주문이 접수됐어요' : '입금 대기' ?></p>
  <h1 class="page-title">아래 계좌로 <?= won($order['total']) ?>을 입금해 주세요</h1>
  <div class="bank-box">
    <dl>
      <div><dt>은행</dt><dd><?= e($order['bank_name']) ?></dd></div>
      <div><dt>계좌번호</dt><dd><span id="account"><?= e($order['bank_account']) ?></span>
        <button type="button" class="btn-chip" data-copy="#account">복사</button></dd></div>
      <div><dt>예금주</dt><dd><?= e($order['bank_holder']) ?></dd></div>
      <div><dt>입금액</dt><dd><strong><?= won($order['total']) ?></strong></dd></div>
      <div><dt>입금자명</dt><dd><?= e($order['depositor']) ?></dd></div>
      <div><dt>입금 기한</dt><dd><?= e(fmt_date($order['due_at'], 'Y.m.d')) ?>까지</dd></div>
    </dl>
  </div>
  <p class="muted">입금이 확인되면 <a href="/library">내 서재</a>에서 바로 읽을 수 있어요. 기한이 지나면 주문이 취소될 수 있어요.</p>
<?php elseif (is_free_order($order)): ?>
  <p class="eyebrow ok">무료</p>
  <h1 class="page-title">무료로 받은 책이에요</h1>
  <p class="muted">아래에서 바로 읽거나, 언제든 <a href="/library">내 서재</a>에서 이어서 읽을 수 있어요.</p>
<?php elseif ($status === 'paid'): ?>
  <p class="eyebrow ok">결제 완료</p>
  <h1 class="page-title">입금이 확인됐어요</h1>
  <p class="muted">아래에서 바로 읽거나, 언제든 <a href="/library">내 서재</a>에서 이어서 읽을 수 있어요.</p>
<?php else: ?>
  <p class="eyebrow muted-eyebrow">주문 취소</p>
  <h1 class="page-title">취소된 주문이에요</h1>
  <p class="muted">다시 구매하려면 책 페이지에서 주문해 주세요.</p>
<?php endif; ?>

  <h2 class="block-title">주문 번호 <?= e($order['order_no']) ?> <span class="muted">· <?= e(fmt_date($order['created_at'], 'Y.m.d H:i')) ?></span></h2>
  <ul class="line-items">
<?php foreach ($items as $it):
    $b = array('id' => $it['book_id'], 'title' => $it['title'], 'author' => (string) $it['author'], 'category' => (string) $it['category'], 'cover_path' => (string) $it['cover_path']); ?>
    <li class="line-item">
      <span class="line-cover" aria-hidden="true"><?= cover_html($b, 'thumb') ?></span>
      <div class="line-body">
<?php if ($it['book_exists']): ?>        <a class="line-title" href="/books/<?= (int) $it['book_id'] ?>"><?= e($it['title']) ?></a>
<?php else: ?>        <span class="line-title"><?= e($it['title']) ?></span>
<?php endif; ?>
        <span class="line-sub"><?= e($it['author']) ?><?= $it['file_format'] ? ' · ' . e($it['file_format']) : '' ?></span>
      </div>
<?php if ($status === 'paid' && $it['book_exists']): ?>
      <a class="btn btn-primary btn-sm" href="/read/<?= (int) $it['book_id'] ?>">읽기</a>
<?php else: ?>
      <span class="line-price"><?= e(price_label($it['price'])) ?></span>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ul>
  <div class="total-row"><span><?= $status === 'paid' ? '결제 금액' : '주문 금액' ?></span><strong><?= won($order['total']) ?></strong></div>

  <div class="page-actions">
    <a class="btn btn-outline" href="/library">내 서재</a>
<?php if ($status === 'pending'): ?>
    <form method="post" action="/orders/<?= e($order['order_no']) ?>/cancel" data-confirm="이 주문을 취소할까요?">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-text">주문 취소</button>
    </form>
<?php endif; ?>
  </div>
</section>
