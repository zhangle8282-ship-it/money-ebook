<?php /** 그린청소 관리자 › 블로그 글쓰기. 변수: $post, $form, $errors */
$action = $post ? '/admin/blog/' . (int) $post['id'] . '/edit' : '/admin/blog/new';
$date = $form['published_at'] ? substr($form['published_at'], 0, 10) : date('Y-m-d');
$host = base_url();
?>
<div class="page-head">
  <div class="page-head-text">
    <h1><?= $post ? '글 고치기' : '새 글 쓰기' ?></h1>
    <p class="muted">오른쪽 ‘검색 점검’이 모두 초록색이 되면 검색에 잘 나오는 글이에요.</p>
  </div>
  <div class="head-actions">
<?php if ($post): ?>    <a class="btn btn-outline" href="<?= e(blog_url($post)) ?>" target="_blank" rel="noopener"><?= blog_is_public($post) ? '글 보기 ↗' : '미리보기 ↗' ?></a>
<?php endif; ?>
    <button type="submit" form="blog-form" class="btn btn-primary">저장하기</button>
  </div>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" id="blog-form" class="form-grid blog-grid" data-blog-form data-upload-url="/admin/blog/upload">
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card stack-lg" aria-label="글">
      <div class="field">
        <label for="b-title">제목</label>
        <input id="b-title" name="title" type="text" maxlength="200" required class="title-input" value="<?= e($form['title']) ?>" placeholder="예: 금왕 사무실 정기청소, 주 3회면 충분할까요?">
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
        <textarea id="b-body" name="body" rows="22" class="editor-area" placeholder="첫 문단에 지역과 청소 종류를 자연스럽게 넣어 주세요. 예: 충북 음성 금왕읍에서 사무실 정기청소를 맡은 이야기예요."><?= e($form['body']) ?></textarea>
        <details class="editor-help"><summary>쓰는 법 보기</summary>
          <p><code>## 소제목</code> · <code>### 작은 제목</code> · <code>- 목록</code> · <code>&gt; 인용</code> · <code>**굵게**</code> · <code>[글자](https://주소)</code> · 사진은 ‘사진 넣기’ 단추로 넣어요. 빈 줄로 문단을 나눠요.</p>
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
        <input type="radio" id="b-st-d" name="status" value="draft" class="sr-only"<?= $form['status'] === 'draft' ? ' checked' : '' ?>><label for="b-st-d">임시저장</label>
      </fieldset>
      <div class="field"><label for="b-date">작성일</label><input id="b-date" name="published_date" type="date" value="<?= e($date) ?>"><p class="field-help">앞날로 정하면 그날부터 공개돼요.</p></div>
    </section>

    <section class="card stack" aria-labelledby="b-cover">
      <h2 id="b-cover">대표 사진</h2>
      <div class="photo-slot">
        <div class="photo-preview"><?= $form['cover'] !== '' ? '<img src="' . e($form['cover']) . '" alt="">' : '<span>사진 없음</span>' ?></div>
        <input name="cover" type="file" accept="image/jpeg,image/png,image/webp" class="file-input" data-resize aria-label="대표 사진 올리기">
<?php if ($form['cover'] !== ''): ?>        <label class="check-row small-check"><input type="checkbox" name="remove_cover" value="1"> <span>대표 사진 지우기</span></label>
<?php endif; ?>
        <p class="field-help">블로그 목록과 카카오톡·검색 공유 이미지에 쓰여요.</p>
      </div>
    </section>

    <section class="card stack" aria-labelledby="b-seo">
      <h2 id="b-seo">검색 노출</h2>
      <div class="field"><label for="b-summary">검색 설명 <span class="sub" data-count="summary">0/160</span></label><textarea id="b-summary" name="summary" rows="3" maxlength="300" placeholder="검색 결과 제목 아래에 나오는 글이에요. 비우면 본문 앞부분이 쓰여요."><?= e($form['summary']) ?></textarea></div>
      <div class="field"><label for="b-keywords">키워드 (쉼표로)</label><input id="b-keywords" name="keywords" type="text" maxlength="300" value="<?= e($form['keywords']) ?>" placeholder="예: 금왕사무실정기청소, 충북음성청소업체"></div>
      <div class="field"><label for="b-seo-title">검색 제목 (선택)</label><input id="b-seo-title" name="seo_title" type="text" maxlength="200" value="<?= e($form['seo_title']) ?>" placeholder="비우면 ‘글 제목 | 그린청소’"></div>
      <div class="field"><label for="b-slug">주소 (선택)</label><input id="b-slug" name="slug" type="text" maxlength="120" value="<?= e($form['slug']) ?>" placeholder="비우면 제목으로 만들어요"></div>
      <div class="serp" aria-label="검색 결과 미리보기">
        <span class="serp-url"><?= e(preg_replace('~^https?://~', '', $host)) ?>/blog/<?= $post ? (int) $post['id'] : '새글' ?>-<span data-serp="slug"></span></span>
        <span class="serp-title" data-serp="title"></span>
        <span class="serp-desc" data-serp="desc"></span>
      </div>
      <ul class="seo-checks" data-seo-checks>
        <li data-check="region">제목에 지역 이름(음성·금왕·대소·진천·혁신도시)</li>
        <li data-check="title_len">제목 길이 15~40자</li>
        <li data-check="desc_len">검색 설명 50~160자</li>
        <li data-check="keyword">키워드 넣기 + 본문에 키워드 쓰기</li>
        <li data-check="body_len">본문 800자 이상</li>
        <li data-check="heading">소제목(##) 2개 이상</li>
        <li data-check="image">사진 1장 이상</li>
      </ul>
    </section>
<?php if ($post): ?>
    <p class="danger-zone"><button type="submit" form="blog-delete" class="btn btn-danger btn-sm">이 글 지우기</button></p>
<?php endif; ?>
  </aside>
</form>
<?php if ($post): ?>
<form method="post" action="/admin/blog/<?= (int) $post['id'] ?>/delete" id="blog-delete" data-confirm="‘<?= e($post['title']) ?>’ 글을 지울까요? 되돌릴 수 없어요."><?= csrf_field() ?></form>
<?php endif; ?>
