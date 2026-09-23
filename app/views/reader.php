<?php
/**
 * 전자책 뷰어 화면. 기기 폭에 맞춰 글자 크기·줄 길이가 바뀌고, 독자가 글자 크기·글꼴·줄 간격·배경을 고를 수 있어요.
 * 변수: $book, $user, $format, $back, (EPUB) $chapters, $c, $html, $restore, (PDF) $page
 */
$isEpub = $format === 'EPUB';
$count = $isEpub ? count($chapters) : 0;
$chapterTitle = $isEpub ? $chapters[$c - 1]['title'] : '';
$config = array(
    'bookId' => (int) $book['id'],
    'format' => $format,
    'csrf' => $user ? csrf_token() : '',
    'save' => (bool) $user,
    'chapter' => $isEpub ? $c : null,
    'chapters' => $isEpub ? $count : null,
    'restore' => $isEpub ? $restore : null,
    'page' => $isEpub ? null : (int) $page,
    'fileUrl' => $isEpub ? null : '/read/' . (int) $book['id'] . '/file',
);
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<title><?= e($chapterTitle !== '' ? $chapterTitle . ' · ' : '') ?><?= e($book['title']) ?></title>
<link rel="icon" href="/favicon.svg?v=1" type="image/svg+xml">
<link rel="icon" href="/favicon.ico?v=1" sizes="48x48">
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600&family=Noto+Serif+KR:wght@400;700&display=swap">
<link rel="stylesheet" href="/assets/reader.css?v=<?= @filemtime(PUBLIC_DIR . '/assets/reader.css') ?>">
<script>
// 저장해 둔 보기 설정을 화면을 그리기 전에 적용합니다(깜빡임 방지).
(function () {
  try {
    var s = JSON.parse(localStorage.getItem('reader-settings') || '{}') || {};
    var d = document.documentElement;
    d.setAttribute('data-theme', s.theme || (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
    if (s.font) d.setAttribute('data-font', s.font);
    if (s.lh) d.setAttribute('data-lh', s.lh);
    if (s.scale) d.style.setProperty('--rd-scale', s.scale);
  } catch (e) {}
})();
</script>
</head>
<body class="rd rd-<?= $isEpub ? 'epub' : 'pdf' ?>">
<header class="rd-bar" id="rd-bar">
  <a class="rd-btn" href="<?= e($back) ?>" aria-label="<?= $user ? '내 서재로 돌아가기' : '돌아가기' ?>">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>
  </a>
  <div class="rd-heading">
    <span class="rd-book"><?= e($book['title']) ?></span>
<?php if ($chapterTitle !== ''): ?>    <span class="rd-chapter"><?= e($chapterTitle) ?></span>
<?php endif; ?>
  </div>
<?php if ($isEpub): ?>
  <button type="button" class="rd-btn" data-sheet="toc" aria-label="목차" aria-expanded="false" aria-controls="sheet-toc">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 6h12M8 12h12M8 18h12"></path><circle cx="4" cy="6" r=".6" fill="currentColor"></circle><circle cx="4" cy="12" r=".6" fill="currentColor"></circle><circle cx="4" cy="18" r=".6" fill="currentColor"></circle></svg>
  </button>
<?php endif; ?>
  <button type="button" class="rd-btn" data-sheet="view" aria-label="보기 설정" aria-expanded="false" aria-controls="sheet-view">
    <span class="rd-aa" aria-hidden="true">가</span>
  </button>
</header>

<main class="rd-main" id="rd-main">
<?php if ($isEpub): ?>
  <article class="rd-text" lang="ko">
<?= $html !== '' ? $html : '<p class="rd-empty">이 부분에는 글이 없어요.</p>' ?>
  </article>
  <nav class="rd-chapnav" aria-label="장 이동">
<?php if ($c > 1): ?>    <a href="?c=<?= $c - 1 ?>" rel="prev" data-prev>이전 장</a>
<?php else: ?>    <span></span>
<?php endif; ?>
    <span class="rd-chapnav-now"><?= $c ?> / <?= $count ?></span>
<?php if ($c < $count): ?>    <a href="?c=<?= $c + 1 ?>" rel="next" class="is-next" data-next>다음 장</a>
<?php else: ?>    <a href="<?= e($back) ?>" class="is-next">다 읽었어요</a>
<?php endif; ?>
  </nav>
<?php else: ?>
  <div class="rd-pages" id="rd-pages">
    <p class="rd-loading" id="rd-loading">책을 불러오는 중이에요…</p>
  </div>
<?php endif; ?>
</main>

<footer class="rd-foot" id="rd-foot">
  <div class="rd-progress" aria-hidden="true"><i id="rd-progress-bar"></i></div>
  <span class="rd-progress-text" id="rd-progress-text" aria-live="polite"></span>
</footer>

<div class="rd-sheet" id="sheet-view" role="dialog" aria-modal="true" aria-labelledby="sheet-view-title" hidden>
  <div class="rd-sheet-backdrop" data-close></div>
  <div class="rd-sheet-panel">
    <div class="rd-sheet-head">
      <h2 id="sheet-view-title">보기 설정</h2>
      <button type="button" class="rd-btn" data-close aria-label="닫기">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
<?php if ($isEpub): ?>
    <div class="rd-row">
      <span class="rd-label">글자 크기</span>
      <div class="rd-stepper">
        <button type="button" data-scale="-1" aria-label="글자 작게"><span class="rd-a-sm">가</span></button>
        <output id="rd-scale-out">100%</output>
        <button type="button" data-scale="1" aria-label="글자 크게"><span class="rd-a-lg">가</span></button>
      </div>
    </div>
    <div class="rd-row">
      <span class="rd-label">글꼴</span>
      <div class="rd-seg">
        <button type="button" data-font="serif" class="rd-serif">명조</button>
        <button type="button" data-font="sans">고딕</button>
      </div>
    </div>
    <div class="rd-row">
      <span class="rd-label">줄 간격</span>
      <div class="rd-seg">
        <button type="button" data-lh="tight">좁게</button>
        <button type="button" data-lh="normal">보통</button>
        <button type="button" data-lh="loose">넓게</button>
      </div>
    </div>
<?php else: ?>
    <div class="rd-row">
      <span class="rd-label">크기</span>
      <div class="rd-stepper">
        <button type="button" data-zoom="-1" aria-label="작게">−</button>
        <output id="rd-zoom-out">화면 맞춤</output>
        <button type="button" data-zoom="1" aria-label="크게">+</button>
      </div>
    </div>
<?php endif; ?>
    <div class="rd-row">
      <span class="rd-label">배경</span>
      <div class="rd-seg rd-themes">
        <button type="button" data-theme="light"><i class="sw sw-light"></i>흰색</button>
        <button type="button" data-theme="sepia"><i class="sw sw-sepia"></i>미색</button>
        <button type="button" data-theme="dark"><i class="sw sw-dark"></i>어둡게</button>
      </div>
    </div>
    <p class="rd-note">설정은 이 기기에 저장돼요. 읽던 위치는 <?= $user ? '계정에 저장돼 다른 기기에서도 이어 읽을 수 있어요' : '관리자 미리보기라 저장하지 않아요' ?>.</p>
  </div>
</div>

<?php if ($isEpub): ?>
<div class="rd-sheet" id="sheet-toc" role="dialog" aria-modal="true" aria-labelledby="sheet-toc-title" hidden>
  <div class="rd-sheet-backdrop" data-close></div>
  <div class="rd-sheet-panel rd-toc-panel">
    <div class="rd-sheet-head">
      <h2 id="sheet-toc-title">목차</h2>
      <button type="button" class="rd-btn" data-close aria-label="닫기">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
    <ol class="rd-toc">
<?php foreach ($chapters as $i => $ch): ?>
      <li><a href="?c=<?= $i + 1 ?>"<?= $i + 1 === $c ? ' aria-current="page"' : '' ?>><span><?= $i + 1 ?></span><?= e($ch['title']) ?></a></li>
<?php endforeach; ?>
    </ol>
  </div>
</div>
<?php endif; ?>

<script>window.READER = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="/assets/reader.js?v=<?= @filemtime(PUBLIC_DIR . '/assets/reader.js') ?>" defer></script>
</body>
</html>
