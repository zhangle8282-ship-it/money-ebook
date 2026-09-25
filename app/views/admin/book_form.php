<?php
/** 전자책 등록/수정 (디자인: 관리자 · 전자책 등록). 관리자와 오픈마켓 판매자가 함께 씁니다. 변수: $mode(admin|seller), $urls, $seller */
$isEdit = (bool) $book;
$isSeller = $mode === 'seller';
$images = book_preview_images($form);
$action = $urls['action'];
$status = $isEdit ? $book['status'] : 'draft';
$currentPreview = '';
if ($isEdit && book_has_preview($form)) {
    $currentPreview = $form['preview_mode'] === 'manual' ? '직접 입력한 본문' : ($images ? '앞 ' . count($images) . '쪽 (PDF 이미지)' : '앞 ' . (int) $form['preview_pages'] . '쪽 분량 (EPUB 본문)');
}
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" id="book-form" class="book-form" novalidate
  data-book-id="<?= $isEdit ? (int) $book['id'] : '' ?>"
  data-file-url="<?= e($urls['file']) ?>"
  data-format="<?= e($form['file_format']) ?>"
  data-orig-mode="<?= e($isEdit ? $book['preview_mode'] : '') ?>"
  data-orig-pages="<?= $isEdit ? (int) $book['preview_pages'] : '' ?>"
  data-has-images="<?= $images ? '1' : '' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="pdf_pages" value="">
  <input type="file" name="preview_images[]" multiple hidden tabindex="-1" aria-hidden="true" class="preview-images-input">

  <div class="page-head">
    <div class="page-head-text">
      <span class="crumb"><a href="<?= e($urls['list']) ?>"><?= $isSeller ? '내 전자책 판매' : '전자책 관리' ?></a> / <?= $isEdit ? '수정' : '새 전자책 등록' ?></span>
      <h1><?= $isEdit ? e($book['title']) : '새 전자책 등록' ?>
<?php if ($isEdit): ?>        <span class="status book-<?= e($book['status']) ?>"><?= e(BOOK_STATUS[$book['status']]) ?></span>
<?php endif; ?></h1>
    </div>
    <div class="head-actions">
<?php if ($isEdit): ?>      <a class="btn btn-ghost" href="/books/<?= (int) $book['id'] ?>" target="_blank" rel="noopener"><?= $status === 'on_sale' ? '스토어에서 보기' : '미리 보기' ?></a>
<?php endif; ?>
      <button type="submit" name="intent" value="draft" class="btn btn-outline">임시저장</button>
<?php if ($isSeller): ?>
      <button type="submit" name="intent" value="save" class="btn btn-primary"><?= $status === 'on_sale' ? '고치고 다시 승인 요청' : '승인 요청' ?></button>
<?php else: ?>
      <button type="submit" name="intent" value="save" class="btn btn-primary"><?= $isEdit && $book['status'] !== 'draft' ? '저장하기' : '등록하기' ?></button>
<?php endif; ?>
    </div>
  </div>

  <div class="form-status" role="status" aria-live="polite" hidden></div>
<?php if ($isSeller): ?>
  <div class="review-banner review-<?= e($status) ?>">
<?php if ($status === 'review'): ?>    <strong>승인 대기 중</strong> 관리자가 확인하고 승인하면 스토어에서 판매돼요.
<?php elseif ($status === 'rejected'): ?>    <strong>반려됐어요</strong> <?= trim((string) $book['review_memo']) !== '' ? '사유: ' . e($book['review_memo']) . ' · ' : '' ?>고친 뒤 다시 승인 요청해 주세요.
<?php elseif ($status === 'on_sale'): ?>    <strong>판매 중</strong> 내용을 고치고 승인 요청하면 다시 승인될 때까지 스토어에서 잠시 내려가요.
<?php else: ?>    <strong>판매 수수료 <?= seller_commission() ?>%</strong> 책을 올리고 승인 요청하면 관리자가 확인한 뒤 판매를 시작해요. 판매되면 판매가에서 수수료를 뺀 금액이 정산돼요.
<?php endif; ?>
  </div>
<?php elseif ($seller): ?>
  <div class="review-banner review-<?= e($status) ?>">
    <strong>회원이 올린 책</strong> 판매자 <?= e($seller['name']) ?> (<?= e($seller['email']) ?>) · 지금 상태: <?= e(BOOK_STATUS[$status]) ?>
<?php if ($status === 'review' || $status === 'rejected'): ?>
    <div class="review-actions">
      <button type="submit" form="review-approve" class="btn btn-primary btn-sm">승인하고 판매 시작</button>
      <input type="text" form="review-reject" name="review_memo" maxlength="500" placeholder="반려 사유(판매자에게 보여요)" aria-label="반려 사유" value="<?= e((string) $book['review_memo']) ?>">
      <button type="submit" form="review-reject" class="btn btn-ghost btn-sm">반려</button>
    </div>
<?php endif; ?>
  </div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert" role="alert">
<?php foreach ($errors as $err): ?>    <p><?= e($err) ?></p>
<?php endforeach; ?>
<?php if (!empty($_FILES['book_file']['name']) || !empty($_FILES['cover']['name'])): ?>    <p>파일은 보안상 다시 선택해 주세요.</p>
<?php endif; ?>
  </div>
<?php endif; ?>

  <div class="form-grid">
    <div class="form-main">
      <section class="card stack-lg" aria-labelledby="sec-basic">
        <h2 id="sec-basic">기본 정보</h2>
        <div class="field">
          <label for="f-title">제목 <span class="req" aria-hidden="true">*</span></label>
          <input id="f-title" name="title" type="text" maxlength="200" required placeholder="전자책 제목" value="<?= e($form['title']) ?>">
        </div>
        <div class="grid-2">
          <div class="field">
            <label for="f-author">저자 <span class="req" aria-hidden="true">*</span></label>
            <input id="f-author" name="author" type="text" maxlength="120" required placeholder="저자 이름" value="<?= e($form['author']) ?>">
          </div>
          <div class="field">
            <label for="f-cat">카테고리 <span class="req" aria-hidden="true">*</span></label>
            <select id="f-cat" name="category" required>
              <option value="">선택하세요</option>
<?php foreach ($categories as $c): ?>
              <option<?= $form['category'] === $c ? ' selected' : '' ?>><?= e($c) ?></option>
<?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="f-price">판매가 <span class="req" aria-hidden="true">*</span></label>
            <div class="input-suffix">
              <input id="f-price" name="price" type="text" inputmode="numeric" required placeholder="0" value="<?= $form['price'] !== '' ? e(number_format((int) $form['price'])) : '' ?>" data-number>
              <span>원</span>
            </div>
<?php if ($mode === 'admin' && !$seller): ?>
            <p class="field-help">0원으로 하면 무료 책이 돼요. 회원이면 결제 없이 바로 읽을 수 있어요.</p>
<?php else: ?>
            <p class="field-help">100원 이상으로 정해 주세요. 무료(0원) 책은 관리자만 올릴 수 있어요.</p>
<?php endif; ?>
          </div>
          <div class="field">
            <label for="f-pages">분량</label>
            <div class="input-suffix">
              <input id="f-pages" name="pages" type="text" inputmode="numeric" placeholder="비우면 자동 계산" value="<?= $form['pages'] !== null ? (int) $form['pages'] : '' ?>" data-number>
              <span>쪽</span>
            </div>
          </div>
        </div>
        <div class="field">
          <label for="f-desc">책 소개</label>
          <textarea id="f-desc" name="description" rows="5" placeholder="상세 페이지 상단에 보여줄 소개글을 입력하세요."><?= e($form['description']) ?></textarea>
        </div>
      </section>

      <section class="card stack" aria-labelledby="sec-preview">
        <div class="card-intro">
          <h2 id="sec-preview">미리보기 설정</h2>
          <p class="muted">구매 전 독자가 상세 페이지에서 무료로 읽을 수 있는 범위예요.</p>
        </div>
        <fieldset class="segmented">
          <legend class="sr-only">미리보기 방식</legend>
          <input type="radio" id="pm-auto" name="preview_mode" value="auto" class="sr-only"<?= $form['preview_mode'] !== 'manual' ? ' checked' : '' ?>>
          <label for="pm-auto">앞부분 자동 공개</label>
          <input type="radio" id="pm-manual" name="preview_mode" value="manual" class="sr-only"<?= $form['preview_mode'] === 'manual' ? ' checked' : '' ?>>
          <label for="pm-manual">직접 입력</label>
        </fieldset>
        <div class="preview-auto" data-mode-panel="auto">
          <div class="inline-field">
            <label for="f-preview-pages">처음부터</label>
            <input id="f-preview-pages" name="preview_pages" type="text" inputmode="numeric" class="input-short" value="<?= (int) $form['preview_pages'] ?>" data-number>
            <span>쪽까지 공개</span>
          </div>
          <p class="field-help">EPUB은 한 쪽을 약 <?= (int) config('chars_per_page') ?>자로 계산해 본문을 보여주고, PDF는 앞 페이지를 이미지로 만들어 보여줘요. 원본 파일은 공개되지 않아요.</p>
        </div>
        <div class="field" data-mode-panel="manual">
          <label for="f-preview-text">미리보기 본문</label>
          <textarea id="f-preview-text" name="preview_text" rows="7" placeholder="미리보기로 보여줄 본문을 붙여넣으세요. 빈 줄로 문단을 나눠요."><?= e($form['preview_text']) ?></textarea>
        </div>
<?php if ($currentPreview !== ''): ?>
        <p class="field-help">지금 미리보기: <?= e($currentPreview) ?> · <a href="/books/<?= (int) $book['id'] ?>#preview" target="_blank" rel="noopener">확인하기</a></p>
<?php endif; ?>
      </section>
    </div>

    <div class="form-side">
      <section class="card stack" aria-labelledby="sec-cover">
        <h2 id="sec-cover">표지 이미지</h2>
        <label for="f-cover" class="dropzone dropzone-cover<?= $form['cover_path'] !== '' ? ' has-file' : '' ?>" data-dropzone>
          <img class="dropzone-img" alt="" src="<?= e($form['cover_path']) ?>"<?= $form['cover_path'] === '' ? ' hidden' : '' ?>>
          <span class="dropzone-empty">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="M21 15l-5-5L5 21"></path></svg>
            <span class="dropzone-title">표지를 끌어다 놓거나 클릭해서 선택</span>
            <span class="dropzone-help">JPG · PNG, 3:4 비율 권장</span>
          </span>
          <input id="f-cover" name="cover" type="file" accept="image/png,image/jpeg,image/webp" class="visually-hidden-input">
        </label>
        <input type="hidden" name="remove_cover" value="">
        <button type="button" class="btn btn-ghost btn-sm" data-remove-cover<?= $form['cover_path'] === '' ? ' hidden' : '' ?>>표지 지우기</button>
        <p class="field-help">표지가 없으면 제목과 저자로 만든 기본 표지가 보여요.</p>
      </section>

      <section class="card stack" aria-labelledby="sec-file">
        <h2 id="sec-file">전자책 파일 <span class="req" aria-hidden="true">*</span></h2>
        <label for="f-file" class="dropzone dropzone-file" data-dropzone>
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"></path><path d="M7 9l5-5 5 5"></path><path d="M4 20h16"></path></svg>
          <span class="dropzone-title"><?= $form['file_path'] !== '' ? '다른 파일로 바꾸기' : 'EPUB · PDF 파일 업로드' ?></span>
          <input id="f-file" name="book_file" type="file" accept=".epub,.pdf,application/epub+zip,application/pdf" class="visually-hidden-input">
        </label>
        <div class="file-row" data-file-row<?= $form['file_path'] === '' ? ' hidden' : '' ?>>
          <div class="file-badge"><?= e($form['file_format'] !== '' ? $form['file_format'] : 'FILE') ?></div>
          <div class="file-meta">
            <span class="file-name"><?= e($form['file_name']) ?></span>
            <span class="file-state"><?= $form['file_path'] !== '' ? '업로드 완료 · ' . e(fmt_bytes($form['file_size'])) : '' ?></span>
          </div>
          <button type="button" class="icon-btn" data-clear-file aria-label="선택한 파일 빼기" hidden>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
          </button>
        </div>
        <p class="field-help">최대 <?= (int) config('max_book_mb') ?>MB · 서버 업로드 한도 <?= e(ini_get('upload_max_filesize')) ?></p>
      </section>

<?php if (!$isSeller): ?>
      <section class="card switch-card">
        <div>
          <label for="f-publish" class="switch-label">판매 중으로 공개</label>
          <span class="field-help" data-publish-help><?= $publish ? '등록 즉시 스토어에 노출돼요.' : '스토어에 노출되지 않아요.' ?></span>
        </div>
        <input type="checkbox" id="f-publish" name="publish" value="1" role="switch" class="switch"<?= $publish ? ' checked' : '' ?>>
      </section>
<?php endif; ?>

<?php if ($isEdit): ?>
      <section class="card stack danger-card">
        <h2>전자책 삭제</h2>
        <p class="field-help">주문 기록이 없는 책만 지울 수 있어요.<?= $isSeller ? '' : ' 판매를 멈추려면 ‘판매 중으로 공개’를 끄세요.' ?></p>
        <button type="submit" form="delete-form" class="btn btn-danger btn-sm">이 전자책 삭제</button>
      </section>
<?php endif; ?>
    </div>
  </div>
</form>
<?php if ($isEdit): ?>
<form method="post" action="<?= e($urls['delete']) ?>" id="delete-form" data-confirm="‘<?= e($book['title']) ?>’을(를) 삭제할까요? 파일과 리뷰도 함께 지워져요.">
  <?= csrf_field() ?>
</form>
<?php endif; ?>
<?php if (!$isSeller && $seller && $isEdit): ?>
<form method="post" action="/admin/books/<?= (int) $book['id'] ?>/review" id="review-approve" data-confirm="이 책을 승인하고 스토어에서 판매할까요?">
  <?= csrf_field() ?><input type="hidden" name="action" value="approve">
</form>
<form method="post" action="/admin/books/<?= (int) $book['id'] ?>/review" id="review-reject">
  <?= csrf_field() ?><input type="hidden" name="action" value="reject">
</form>
<?php endif; ?>
