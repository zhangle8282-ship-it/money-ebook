<?php /** 관리자 · 전자책 목록. */
$tabs = array('' => '전체') + BOOK_STATUS;
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>전자책 관리</h1>
  </div>
  <a class="btn btn-primary" href="/admin/books/new">새 전자책 등록</a>
</div>
<div class="toolbar">
  <nav class="tabs" aria-label="판매 상태">
<?php foreach ($tabs as $key => $label): ?>
    <a href="/admin/books<?= $key !== '' ? '?status=' . $key : '' ?>"<?= $status === $key || ($key === '' && !array_key_exists($status, BOOK_STATUS)) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
  </nav>
</div>
<section class="card flush">
<?php if ($books): ?>
<div class="table-wrap">
<table class="table">
  <thead>
    <tr><th scope="col">전자책</th><th scope="col">카테고리</th><th scope="col" class="num">판매가</th><th scope="col">파일 · 미리보기</th><th scope="col" class="num">판매 · 평점</th><th scope="col">상태</th></tr>
  </thead>
  <tbody>
<?php foreach ($books as $b): ?>
    <tr>
      <td>
        <div class="book-cell">
          <?= cover_html($b, 'thumb') ?>
          <div><a href="/admin/books/<?= (int) $b['id'] ?>/edit" class="strong"><?= e($b['title']) ?></a><div class="sub"><?= e($b['author'] !== '' ? $b['author'] : '저자 미입력') ?><?= $b['seller_name'] ? ' · 판매자 ' . e($b['seller_name']) : '' ?></div></div>
        </div>
      </td>
      <td><?= e($b['category'] !== '' ? $b['category'] : '-') ?></td>
      <td class="num"><?= $b['price'] ? won($b['price']) : '-' ?></td>
      <td><?= $b['file_format'] !== '' ? e($b['file_format']) . ' · ' . e(fmt_bytes($b['file_size'])) : '<span class="warn">파일 없음</span>' ?>
        <div class="sub"><?= book_has_preview($b) ? ($b['preview_mode'] === 'manual' ? '직접 입력' : '앞 ' . (count(book_preview_images($b)) ?: (int) $b['preview_pages']) . '쪽') : '<span class="warn">미리보기 없음</span>' ?></div></td>
      <td class="num"><?= (int) $b['sold'] ?>권<div class="sub"><?= $b['review_count'] ? '★ ' . number_format($b['avg_rating'], 1) . ' (' . (int) $b['review_count'] . ')' : '리뷰 없음' ?></div></td>
      <td><span class="status book-<?= e($b['status']) ?>"><?= e(BOOK_STATUS[$b['status']]) ?></span>
        <div class="sub"><a href="/books/<?= (int) $b['id'] ?>" target="_blank" rel="noopener">스토어</a><?php if ($b['file_format'] !== ''): ?> · <a href="/read/<?= (int) $b['id'] ?>" target="_blank" rel="noopener">뷰어</a><?php endif; ?></div></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php else: ?>
  <div class="empty-card">
    <p>등록된 전자책이 없어요.</p>
    <a class="btn btn-primary" href="/admin/books/new">첫 전자책 등록하기</a>
  </div>
<?php endif; ?>
</section>
