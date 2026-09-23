<?php
/** 장바구니. */
$buyable = array_filter($books, function ($b) {
    return $b['blocked'] === '';
});
$total = array_sum(array_map(function ($b) {
    return (int) $b['price'];
}, $buyable));
?>
<section class="page wrap-narrow">
  <h1 class="page-title">장바구니</h1>
<?php if ($books): ?>
  <ul class="line-items">
<?php foreach ($books as $b): ?>
    <li class="line-item<?= $b['blocked'] !== '' ? ' is-blocked' : '' ?>">
      <a href="/books/<?= (int) $b['id'] ?>" class="line-cover" tabindex="-1" aria-hidden="true"><?= cover_html($b, 'thumb') ?></a>
      <div class="line-body">
        <a class="line-title" href="/books/<?= (int) $b['id'] ?>"><?= e($b['title']) ?></a>
        <span class="line-sub"><?= e($b['author']) ?> · <?= e($b['file_format']) ?></span>
<?php if ($b['blocked'] !== ''): ?>        <span class="line-warn"><?= e($b['blocked']) ?></span>
<?php endif; ?>
      </div>
      <span class="line-price"><?= won($b['price']) ?></span>
      <form method="post" action="/cart/remove">
        <?= csrf_field() ?>
        <input type="hidden" name="book_id" value="<?= (int) $b['id'] ?>">
        <button type="submit" class="icon-btn" aria-label="<?= e($b['title']) ?> 빼기">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
        </button>
      </form>
    </li>
<?php endforeach; ?>
  </ul>
  <div class="total-row"><span>결제 예정 금액</span><strong><?= won($total) ?></strong></div>
  <div class="page-actions">
    <a class="btn btn-outline" href="/">계속 둘러보기</a>
<?php if ($buyable): ?>
    <a class="btn btn-primary" href="/checkout">주문하기</a>
<?php endif; ?>
  </div>
<?php else: ?>
  <div class="empty">
    <p>장바구니가 비어 있어요.</p>
    <a href="/">전자책 둘러보기</a>
  </div>
<?php endif; ?>
</section>
