<?php /** 그린청소 관리자 › 검색어 페이지 쓰기. 변수: $page, $form, $errors */
$action = $page ? '/admin/pages/' . (int) $page['id'] . '/edit' : '/admin/pages/new';
$host = base_url();
?>
<div class="page-head">
  <div class="page-head-text">
    <h1><?= $page ? '검색어 페이지 고치기' : '새 검색어 페이지' ?></h1>
    <p class="muted">제목은 검색어 그대로(예: 음성 공장 청소), 본문은 그 지역 · 공간에 맞는 이야기로 채워요. 오른쪽 ‘검색 점검’이 모두 초록색이면 좋아요.</p>
  </div>
  <div class="head-actions">
<?php if ($page): ?>    <a class="btn btn-outline" href="<?= e(landing_url($page)) ?>" target="_blank" rel="noopener"><?= $page['status'] === 'published' ? '페이지 보기 ↗' : '미리보기 ↗' ?></a>
<?php endif; ?>
    <button type="submit" form="blog-form" class="btn btn-primary">저장하기</button>
  </div>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" id="blog-form" class="form-grid blog-grid" data-blog-form data-slug-join data-upload-url="/admin/blog/upload">
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card stack-lg" aria-label="페이지">
      <div class="field">
        <label for="b-title">제목 (검색어)</label>
        <input id="b-title" name="title" type="text" maxlength="200" required class="title-input" value="<?= e($form['title']) ?>" placeholder="예: 음성 공장 청소">
      </div>
      <div class="field">
        <label for="b-summary">첫 문단 · 검색 설명 <span class="sub" data-count="summary">0/160</span></label>
        <textarea id="b-summary" name="summary" rows="3" maxlength="300" placeholder="제목 바로 아래에 크게 보이고, 검색 결과 제목 아래 설명으로도 쓰여요. 지역 · 청소 종류 · 무료 견적을 넣어 주세요."><?= e($form['summary']) ?></textarea>
      </div>
      <div class="field">
        <label for="b-body">본문</label>
        <div class="editor-bar" role="toolbar" aria-label="본문 꾸미기">
          <button type="button" data-md="h2">소제목</button>
          <button type="button" data-md="h3">작은 제목</button>
          <button type="button" data-md="bold"><b>굵게</b></button>
          <button type="button" data-md="list">• 목록</button>
          <button type="button" data-md="quote">“ 인용</button>
          <button type="button" data-md="link">링크</button>
          <button type="button" data-md="image">사진 넣기</button>
          <input type="file" accept="image/jpeg,image/png,image/webp" hidden data-md-file>
          <span class="editor-status" data-md-status></span>
        </div>
        <textarea id="b-body" name="body" rows="22" class="editor-area" placeholder="그 지역 · 공간에서 무엇을 청소하는지, 언제 · 얼마나 자주 하는지, 견적은 어떻게 받는지 적어 주세요."><?= e($form['body']) ?></textarea>
        <details class="editor-help"><summary>쓰는 법 보기</summary>
          <p><code>## 소제목</code> · <code>### 작은 제목</code> · <code>- 목록</code> · <code>&gt; 인용</code> · <code>**굵게**</code> · <code>[글자](https://주소)</code> · 사진은 ‘사진 넣기’ 단추로 넣어요. 본문 아래에는 청소 범위 · 진행 순서 · 견적 문의가 자동으로 붙어요.</p>
        </details>
      </div>
    </section>
  </div>

  <aside class="form-side">
    <section class="card stack" aria-labelledby="b-pub">
      <h2 id="b-pub">공개</h2>
      <fieldset class="segmented">
        <legend class="sr-only">공개 여부</legend>
        <input type="radio" id="b-st-p" name="status" value="published" class="sr-only"<?= $form['status'] === 'published' ? ' checked' : '' ?>><label for="b-st-p">공개</label>
        <input type="radio" id="b-st-h" name="status" value="hidden" class="sr-only"<?= $form['status'] === 'hidden' ? ' checked' : '' ?>><label for="b-st-h">숨김</label>
      </fieldset>
      <div class="grid-2">
        <div class="field"><label for="b-kind">견적 종류</label><select id="b-kind" name="kind"><?php foreach (CLEANING_KINDS as $k => $label): ?><option value="<?= e($k) ?>"<?= $form['kind'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="b-sort">순서</label><input id="b-sort" name="sort" type="number" min="0" max="999" value="<?= (int) $form['sort'] ?>"></div>
      </div>
      <p class="field-help">견적 종류: ‘무료 견적 문의’를 누르면 미리 골라지고, 청소 범위 목록도 이 종류로 보여요. 순서: 첫 화면 · 바닥글에 작은 숫자부터 나와요.</p>
    </section>

    <section class="card stack" aria-labelledby="b-cover">
      <h2 id="b-cover">대표 사진</h2>
      <div class="photo-slot">
        <div class="photo-preview"><?= $form['cover'] !== '' ? '<img src="' . e($form['cover']) . '" alt="">' : '<span>사진 없음</span>' ?></div>
        <input name="cover" type="file" accept="image/jpeg,image/png,image/webp" class="file-input" data-resize aria-label="대표 사진 올리기">
<?php if ($form['cover'] !== ''): ?>        <label class="check-row small-check"><input type="checkbox" name="remove_cover" value="1"> <span>대표 사진 지우기</span></label>
<?php endif; ?>
        <p class="field-help">그 지역 · 공간의 실제 작업 사진이 가장 좋아요. 카카오톡 · 검색 공유 이미지에도 쓰여요.</p>
      </div>
    </section>

    <section class="card stack" aria-labelledby="b-seo">
      <h2 id="b-seo">검색 노출</h2>
      <div class="field"><label for="b-keywords">키워드 (쉼표로)</label><input id="b-keywords" name="keywords" type="text" maxlength="300" value="<?= e($form['keywords']) ?>" placeholder="예: 음성공장청소, 공장청소"></div>
      <div class="field"><label for="b-seo-title">검색 제목 (선택)</label><input id="b-seo-title" name="seo_title" type="text" maxlength="200" value="<?= e($form['seo_title']) ?>" placeholder="비우면 ‘제목 | <?= e(gc('name')) ?>’"></div>
      <div class="field"><label for="b-slug">주소 (선택)</label><input id="b-slug" name="slug" type="text" maxlength="120" value="<?= e($form['slug']) ?>" placeholder="비우면 제목을 붙여 써서 만들어요"></div>
      <div class="serp" aria-label="검색 결과 미리보기">
        <span class="serp-url"><?= e(display_url($host)) ?>/<span data-serp="slug"></span></span>
        <span class="serp-title" data-serp="title"></span>
        <span class="serp-desc" data-serp="desc"></span>
      </div>
      <ul class="seo-checks" data-seo-checks>
        <li data-check="region">제목에 지역 이름(음성 · 금왕 · 대소 · 진천 · 혁신도시)</li>
        <li data-check="desc_len">첫 문단 · 검색 설명 50~160자</li>
        <li data-check="keyword">키워드 넣기 + 본문에 키워드 쓰기</li>
        <li data-check="body_len">본문 800자 이상</li>
        <li data-check="heading">소제목(##) 2개 이상</li>
        <li data-check="image">사진 1장 이상</li>
      </ul>
    </section>
<?php if ($page): ?>
    <p class="danger-zone"><button type="submit" form="page-delete" class="btn btn-danger btn-sm">이 페이지 지우기</button></p>
<?php endif; ?>
  </aside>
</form>
<?php if ($page): ?>
<form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/delete" id="page-delete" data-confirm="‘<?= e($page['title']) ?>’ 페이지를 지울까요? 되돌릴 수 없어요."><?= csrf_field() ?></form>
<?php endif; ?>
