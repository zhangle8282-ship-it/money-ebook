<?php
/** 내 서재: 구매한 책 다운로드와 주문 내역. */
?>
<section class="page wrap">
  <h1 class="page-title">내 서재</h1>
  <p class="muted"><?= e($user['name']) ?>님이 구매한 전자책이에요. 어느 기기에서든 읽던 곳부터 이어서 읽을 수 있어요.</p>

<?php if ($owned): ?>
  <div class="book-grid library-grid">
<?php foreach ($owned as $b): ?>
    <div class="book-card">
      <a href="/books/<?= (int) $b['id'] ?>" tabindex="-1" aria-hidden="true"><?= cover_html($b) ?></a>
      <div class="book-card-info">
        <a class="book-card-title" href="/books/<?= (int) $b['id'] ?>"><?= e($b['title']) ?></a>
        <div class="book-card-author"><?= e($b['author']) ?> · <?= e($b['file_format']) ?></div>
<?php $pct = $percents[(int) $b['id']] ?? null; ?>
<?php if ($pct !== null): ?>        <div class="read-progress" aria-label="<?= $pct ?>% 읽음"><i style="width:<?= $pct ?>%"></i></div>
        <div class="book-card-author"><?= $pct >= 100 ? '다 읽었어요' : $pct . '% 읽음' ?></div>
<?php endif; ?>
        <div class="library-actions">
          <a class="btn btn-primary btn-sm" href="/read/<?= (int) $b['id'] ?>"><?= $pct ? '이어 읽기' : '읽기' ?></a>
<?php if (downloads_allowed()): ?>          <a class="btn btn-outline btn-sm" href="/download/<?= (int) $b['id'] ?>">다운로드</a>
<?php endif; ?>
        </div>
      </div>
    </div>
<?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty">
    <p>아직 읽을 수 있는 책이 없어요.<?= $orders ? ' 입금이 확인되면 여기에 나타나요.' : '' ?></p>
    <a href="/">전자책 둘러보기</a>
  </div>
<?php endif; ?>

<?php if ($orders): ?>
  <h2 class="block-title">주문 내역</h2>
  <div class="order-list">
<?php foreach ($orders as $o): ?>
    <a class="order-row" href="/orders/<?= e($o['order_no']) ?>">
      <span class="order-date"><?= e(fmt_date($o['created_at'])) ?></span>
      <span class="order-titles"><?= e($o['items'] ? $o['items'][0]['title'] : '') ?><?= count($o['items']) > 1 ? ' 외 ' . (count($o['items']) - 1) . '권' : '' ?></span>
      <span class="order-total"><?= won($o['total']) ?></span>
      <span class="status status-<?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']]) ?></span>
    </a>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>
