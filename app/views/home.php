<?php
/** 스토어 홈: 소개, 카테고리, 전자책 목록. */
$cats = categories();
?>
<section class="hero wrap">
  <h1 class="hero-title"><?= e(setting('hero_title')) ?></h1>
  <p class="hero-text"><?= e(setting('hero_text')) ?></p>
<?php if ($cats): ?>
  <nav class="chips" aria-label="카테고리">
    <a class="chip" href="<?= e(url_with(array('cat' => null, 'q' => null, 'page' => null), '/')) ?>"<?= $category === '' ? ' aria-current="true"' : '' ?>>전체</a>
<?php foreach ($cats as $c): ?>
    <a class="chip" href="<?= e(url_with(array('cat' => $c, 'q' => null, 'page' => null), '/')) ?>"<?= $category === $c ? ' aria-current="true"' : '' ?>><?= e($c) ?></a>
<?php endforeach; ?>
  </nav>
<?php endif; ?>
</section>

<section class="book-section wrap" aria-labelledby="list-title">
  <div class="section-head">
    <h2 id="list-title"><?= e($heading) ?></h2>
    <span><?= e($hint) ?></span>
  </div>
<?php if ($books): ?>
  <div class="book-grid">
<?php foreach ($books as $b): ?>
    <a class="book-card" href="/books/<?= (int) $b['id'] ?>">
      <?= cover_html($b) ?>
      <div class="book-card-info">
        <div class="book-card-title"><?= e($b['title']) ?></div>
        <div class="book-card-author"><?= e($b['author']) ?></div>
        <div class="book-card-meta">
<?php if ($b['review_count']): ?>
          <span><span class="star" aria-hidden="true">★</span> <?= number_format($b['avg_rating'], 1) ?> (<?= (int) $b['review_count'] ?>)</span>
<?php else: ?>
          <span>아직 리뷰 없음</span>
<?php endif; ?>
          <span class="price"><?= won($b['price']) ?></span>
        </div>
      </div>
    </a>
<?php endforeach; ?>
  </div>
<?php if ($pages > 1): ?>
  <nav class="pager" aria-label="페이지">
<?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="<?= e(url_with(array('page' => $i > 1 ? $i : null))) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= $i ?></a>
<?php endfor; ?>
  </nav>
<?php endif; ?>
<?php else: ?>
  <div class="empty">
<?php if ($q !== ''): ?>
    <p>‘<?= e($q) ?>’에 맞는 전자책이 없어요. 다른 제목이나 저자로 찾아보세요.</p>
<?php elseif ($category !== ''): ?>
    <p><?= e($category) ?> 분야에는 아직 등록된 전자책이 없어요.</p>
<?php else: ?>
    <p>아직 판매 중인 전자책이 없어요.</p>
<?php endif; ?>
    <a href="/">전체 목록 보기</a>
  </div>
<?php endif; ?>
</section>
