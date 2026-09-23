<?php /** 관리자 · 리뷰 관리. */
$tabs = array('' => '전체', 'visible' => '보이는 리뷰', 'hidden' => '숨긴 리뷰');
$back = $_SERVER['REQUEST_URI'] ?? '/admin/reviews';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>리뷰 관리</h1>
    <p class="muted">숨긴 리뷰는 스토어와 평점 계산에서 빠져요.</p>
  </div>
</div>
<div class="toolbar">
  <nav class="tabs" aria-label="리뷰 상태">
<?php foreach ($tabs as $key => $label): ?>
    <a href="/admin/reviews<?= $key !== '' ? '?status=' . $key : '' ?>"<?= $status === $key || ($key === '' && !isset($tabs[$status])) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
  </nav>
</div>
<section class="card flush">
<?php if ($reviews): ?>
  <ul class="review-admin-list">
<?php foreach ($reviews as $r): ?>
    <li class="review-admin<?= $r['status'] === 'hidden' ? ' is-hidden' : '' ?>">
      <div class="review-admin-head">
        <span class="stars-text" aria-label="별점 <?= (int) $r['rating'] ?>점"><?= stars_text($r['rating']) ?></span>
        <a href="/books/<?= (int) $r['book_id'] ?>#reviews" target="_blank" rel="noopener" class="strong"><?= e($r['book_title'] ?? '(삭제된 책)') ?></a>
        <span class="sub"><?= e($r['user_name'] ?? '') ?> · <?= e($r['user_email'] ?? '') ?> · <?= e(fmt_date($r['created_at'], 'Y.m.d H:i')) ?> · 도움돼요 <?= (int) $r['helpful'] ?></span>
<?php if ($r['status'] === 'hidden'): ?>        <span class="status book-hidden">숨김</span>
<?php endif; ?>
      </div>
      <p class="review-admin-body"><?= nl2br(e($r['body']), false) ?></p>
      <div class="review-admin-actions">
        <form method="post" action="/admin/reviews/<?= (int) $r['id'] ?>">
          <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
          <input type="hidden" name="action" value="<?= $r['status'] === 'hidden' ? 'show' : 'hide' ?>">
          <button type="submit" class="btn btn-outline btn-sm"><?= $r['status'] === 'hidden' ? '다시 보이기' : '숨기기' ?></button>
        </form>
        <form method="post" action="/admin/reviews/<?= (int) $r['id'] ?>" data-confirm="이 리뷰를 완전히 삭제할까요?">
          <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
          <input type="hidden" name="action" value="delete">
          <button type="submit" class="btn btn-ghost btn-sm">삭제</button>
        </form>
      </div>
    </li>
<?php endforeach; ?>
  </ul>
<?php else: ?>
  <p class="muted pad">리뷰가 없어요.</p>
<?php endif; ?>
</section>
