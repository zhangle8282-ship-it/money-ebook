<?php
/** 전자책 상세: 책 정보·구매, 미리보기, 리뷰. */
$images = book_preview_images($book);
$hasPreview = book_has_preview($book);
$back = $_SERVER['REQUEST_URI'] ?? '/books/' . $book['id'];
$reviewCount = (int) $summary['count'];
$pages = (int) $book['pages'];
$free = book_is_free($book);

if ($book['preview_mode'] === 'manual') {
    $previewNote = '본문 일부를 무료로 읽을 수 있어요.';
} else {
    $shown = $images ? count($images) : (int) $book['preview_pages'];
    $previewNote = ($pages ? '전체 ' . $pages . '쪽 중 ' : '') . '앞부분 ' . $shown . '쪽을 무료로 읽을 수 있어요.';
}
?>
<nav class="crumbs wrap" aria-label="경로">
  <a href="/">홈</a><span aria-hidden="true">/</span>
<?php if ($book['category'] !== ''): ?>
  <a href="/?cat=<?= e(rawurlencode($book['category'])) ?>"><?= e($book['category']) ?></a><span aria-hidden="true">/</span>
<?php endif; ?>
  <span aria-current="page"><?= e($book['title']) ?></span>
</nav>

<?php if (!book_on_sale($book)): ?>
<div class="wrap"><p class="notice-inline">미리보기예요. 이 책은 지금 스토어에 보이지 않아요(<?= e(BOOK_STATUS[$book['status']]) ?>).</p></div>
<?php endif; ?>

<section class="book-hero wrap">
  <?= cover_html($book, 'hero') ?>
  <div class="book-info">
<?php if ($book['category'] !== ''): ?>
    <span class="pill"><?= e($book['category']) ?></span>
<?php endif; ?>
    <h1 class="book-title"><?= e($book['title']) ?></h1>
    <div class="book-byline"><?= e($book['author']) ?> 지음<?= $book['published_at'] ? ' · ' . e(fmt_month($book['published_at'])) . ' 출간' : '' ?></div>
    <div class="book-rating">
<?php if ($reviewCount): ?>
      <?= stars_html($summary['avg']) ?>
      <strong><?= number_format($summary['avg'], 1) ?></strong>
      <a href="#reviews">리뷰 <?= $reviewCount ?>개</a>
<?php else: ?>
      <a href="#reviews">아직 리뷰가 없어요</a>
<?php endif; ?>
    </div>
    <div class="divider"></div>
    <div class="book-price<?= $free ? ' is-free' : '' ?>"><?= e(price_label($book['price'])) ?><?php if ($free): ?> <span class="free-note">회원이면 누구나 무료로 읽을 수 있어요</span><?php endif; ?></div>
    <dl class="spec">
      <div><dt>형식</dt><dd><?= e($book['file_format'] !== '' ? $book['file_format'] : '-') ?></dd></div>
      <div><dt>분량</dt><dd><?= $pages ? '약 ' . number_format($pages) . '쪽' : '-' ?></dd></div>
      <div><dt>용량</dt><dd><?= $book['file_size'] ? e(fmt_bytes($book['file_size'])) : '-' ?></dd></div>
      <div><dt>기기</dt><dd>PC · 모바일 · 태블릿</dd></div>
    </dl>
    <div class="buy-actions">
<?php if ($owned): ?>
      <a class="btn btn-primary btn-lg" href="/read/<?= (int) $book['id'] ?>">바로 읽기</a>
<?php if (downloads_allowed()): ?>      <a class="btn btn-outline btn-lg" href="/download/<?= (int) $book['id'] ?>">다운로드</a>
<?php else: ?>      <a class="btn btn-outline btn-lg" href="/library">내 서재</a>
<?php endif; ?>
<?php elseif ($free): ?>
      <form method="post" action="/books/<?= (int) $book['id'] ?>/free">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary btn-lg">무료로 읽기</button>
      </form>
<?php elseif ($pendingNo): ?>
      <a class="btn btn-primary btn-lg" href="/orders/<?= e($pendingNo) ?>">입금 대기 중 · 주문 보기</a>
<?php else: ?>
      <form method="post" action="/cart/add">
        <?= csrf_field() ?>
        <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
        <input type="hidden" name="buy_now" value="1">
        <button type="submit" class="btn btn-primary btn-lg">구매하기</button>
      </form>
<?php if ($inCart): ?>
      <a class="btn btn-outline btn-lg" href="/cart">장바구니 보기</a>
<?php else: ?>
      <form method="post" action="/cart/add">
        <?= csrf_field() ?>
        <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
        <button type="submit" class="btn btn-outline btn-lg">장바구니 담기</button>
      </form>
<?php endif; ?>
<?php endif; ?>
<?php if ($hasPreview): ?>
      <a class="btn-text btn-lg" href="#preview">미리보기</a>
<?php endif; ?>
    </div>
<?php if (trim((string) $book['description']) !== ''): ?>
    <div class="book-desc"><?= paragraphs($book['description']) ?></div>
<?php endif; ?>
  </div>
</section>

<?php if ($hasPreview): ?>
<section id="preview" class="preview-section" aria-labelledby="preview-title">
  <div class="wrap">
    <div class="section-intro">
      <h2 id="preview-title">미리보기</h2>
      <p><?= e($previewNote) ?></p>
    </div>
    <div class="preview-layout">
      <div class="preview-col">
        <article class="reader is-collapsed<?= $images && $book['preview_mode'] === 'auto' ? ' reader-pages' : '' ?>" id="reader" tabindex="0" aria-label="미리보기 본문">
<?php if ($book['preview_mode'] === 'manual'): ?>
          <?= paragraphs($book['preview_text']) ?>
<?php elseif ($images): ?>
<?php foreach ($images as $i => $src): ?>
          <img class="reader-page" src="<?= e($src) ?>" alt="미리보기 <?= $i + 1 ?>쪽" loading="lazy">
<?php endforeach; ?>
<?php else: ?>
          <?= $book['preview_html'] ?>
<?php endif; ?>
          <div class="reader-fade" aria-hidden="true"></div>
        </article>
        <button type="button" class="btn-round" data-reader-toggle aria-controls="reader" aria-expanded="false">미리보기 계속 읽기</button>
      </div>
      <aside class="buy-box" aria-label="구매 안내">
<?php if ($owned): ?>
        <div class="buy-box-title">이미 구매한 책이에요</div>
        <p>PC, 모바일, 태블릿 어디서든 읽던 곳부터 이어서 읽을 수 있어요.</p>
        <a class="btn btn-primary" href="/read/<?= (int) $book['id'] ?>">바로 읽기</a>
<?php elseif ($free): ?>
        <div class="buy-box-title">무료로 끝까지 읽어 보세요</div>
        <p>로그인만 하면 결제 없이 내 서재에 담기고, PC, 모바일, 태블릿 어디서든 사이트 뷰어로 읽을 수 있어요.</p>
        <div class="buy-box-price"><span>가격</span><strong class="is-free">무료</strong></div>
        <form method="post" action="/books/<?= (int) $book['id'] ?>/free">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-primary btn-block">무료로 읽기</button>
        </form>
<?php else: ?>
        <div class="buy-box-title">계속 읽고 싶다면</div>
        <p>입금이 확인되면 PC, 모바일, 태블릿 어디서든 사이트 뷰어로 바로 읽을 수 있어요.</p>
        <div class="buy-box-price"><span>판매가</span><strong><?= won($book['price']) ?></strong></div>
<?php if ($pendingNo): ?>
        <a class="btn btn-primary" href="/orders/<?= e($pendingNo) ?>">입금 대기 중 · 주문 보기</a>
<?php else: ?>
        <form method="post" action="/cart/add">
          <?= csrf_field() ?>
          <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
          <input type="hidden" name="buy_now" value="1">
          <button type="submit" class="btn btn-primary btn-block">구매하기</button>
        </form>
<?php endif; ?>
<?php endif; ?>
      </aside>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="reviews" class="reviews wrap" aria-labelledby="reviews-title">
  <div class="review-summary">
    <h2 id="reviews-title">리뷰</h2>
    <div class="score">
      <span class="score-num"><?= $reviewCount ? number_format($summary['avg'], 1) : '0.0' ?></span>
      <div class="score-side">
        <?= stars_html($summary['avg']) ?>
        <span>리뷰 <?= $reviewCount ?>개</span>
      </div>
    </div>
    <div class="dist">
<?php foreach ($summary['dist'] as $star => $n): $pct = $reviewCount ? round($n / $reviewCount * 100) : 0; ?>
      <div class="dist-row">
        <span class="dist-label"><?= $star ?>점</span>
        <div class="dist-bar" role="img" aria-label="<?= $star ?>점 <?= $pct ?>%"><i style="width:<?= $pct ?>%"></i></div>
        <span class="dist-pct"><?= $pct ?>%</span>
      </div>
<?php endforeach; ?>
    </div>
  </div>

  <div class="review-main">
    <div class="review-write">
      <div class="review-write-head">
        <span class="review-write-title">리뷰 쓰기</span>
        <span class="review-write-note">구매한 분만 작성할 수 있어요</span>
      </div>
<?php if ($canReview): ?>
      <form method="post" action="/books/<?= (int) $book['id'] ?>/reviews" class="review-form">
        <?= csrf_field() ?>
        <input type="hidden" name="back" value="<?= e($back) ?>">
        <fieldset class="star-picker">
          <legend class="sr-only">별점</legend>
<?php for ($n = 1; $n <= 5; $n++): ?>
          <input type="radio" class="sr-only" id="rate-<?= $n ?>" name="rating" value="<?= $n ?>" required>
          <label for="rate-<?= $n ?>" class="star-btn" data-star="<?= $n ?>"><span aria-hidden="true">★</span><span class="sr-only">별점 <?= $n ?>점</span></label>
<?php endfor; ?>
          <span class="rate-text" aria-hidden="true">별점을 선택하세요</span>
        </fieldset>
        <label for="review-body" class="field-label">내용</label>
        <textarea id="review-body" name="body" rows="3" minlength="5" maxlength="1000" required placeholder="이 책은 어땠나요? 다른 독자에게 도움이 되는 이야기를 남겨주세요."></textarea>
        <button type="submit" class="btn btn-primary btn-sm align-end">등록</button>
      </form>
<?php else: ?>
      <p class="review-write-locked">
<?php if (!$user): ?>
        <a href="/login?next=<?= e(rawurlencode('/books/' . $book['id'] . '#reviews')) ?>">로그인</a>하고 구매한 책에 리뷰를 남겨 주세요.
<?php elseif ($myReview): ?>
        이 책에 리뷰를 남겼어요. 고마워요!<?= $myReview['status'] === 'hidden' ? ' (관리자가 숨긴 리뷰예요)' : '' ?>
<?php else: ?>
        이 책을 구매하고 입금이 확인되면 리뷰를 쓸 수 있어요.
<?php endif; ?>
      </p>
<?php endif; ?>
    </div>

    <nav class="review-sort" aria-label="리뷰 정렬">
<?php foreach (REVIEW_SORTS as $key => $label): ?>
      <a href="<?= e(url_with(array('sort' => $key === 'latest' ? null : $key, 'rv' => null))) ?>#reviews"<?= $sort === $key ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
    </nav>

<?php if ($reviews): ?>
    <div class="review-list">
<?php foreach ($reviews as $r): $mine = $user && (int) $r['user_id'] === (int) $user['id']; ?>
      <article class="review">
        <div class="review-meta">
          <span class="review-stars" role="img" aria-label="별점 <?= (int) $r['rating'] ?>점"><?= stars_text($r['rating']) ?></span>
          <span class="review-name"><?= e(mask_name($r['user_name'])) ?><?= $mine ? ' (나)' : '' ?></span>
          <span class="review-date"><?= e(fmt_date($r['created_at'])) ?></span>
        </div>
        <p class="review-body"><?= nl2br(e($r['body']), false) ?></p>
        <div class="review-actions">
<?php if ($mine): ?>
          <span class="helpful is-static">도움돼요 <?= (int) $r['helpful'] ?></span>
          <form method="post" action="/reviews/<?= (int) $r['id'] ?>/delete" data-confirm="리뷰를 삭제할까요?">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= e($back) ?>">
            <button type="submit" class="btn-text btn-xs">삭제</button>
          </form>
<?php else: ?>
          <form method="post" action="/reviews/<?= (int) $r['id'] ?>/helpful">
            <?php if ($user) { echo csrf_field(); } ?>
            <input type="hidden" name="back" value="<?= e($back) ?>">
            <button type="submit" class="helpful" aria-pressed="<?= $r['voted'] ? 'true' : 'false' ?>">도움돼요 <?= (int) $r['helpful'] ?></button>
          </form>
<?php endif; ?>
        </div>
      </article>
<?php endforeach; ?>
    </div>
<?php if ($reviewCount > count($reviews)): ?>
    <a class="btn-round btn-more" href="<?= e(url_with(array('rv' => $limit + 10))) ?>#reviews">리뷰 더 보기</a>
<?php endif; ?>
<?php else: ?>
    <p class="empty-reviews">아직 리뷰가 없어요. 이 책을 읽고 첫 리뷰를 남겨 주세요.</p>
<?php endif; ?>
  </div>
</section>
