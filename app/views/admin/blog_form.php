<?php /** 그린청소 관리자 › 블로그 글쓰기. 변수: $post, $form, $errors, $fromDraft(초안을 불러왔는지) */
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
<?php if (!empty($fromDraft)): ?>
<div class="notice-box"><p>준비된 초안을 불러왔어요. 우리 업체와 다른 내용은 고치고, <b>현장 사진</b>을 ‘사진 넣기’로 1장 이상 넣은 뒤 저장해 주세요. 직접 겪은 이야기를 한두 줄 더하면 검색에 더 잘 나와요.</p></div>
<?php endif; ?>
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
        <label id="b-body-label">본문</label>
        <div class="rich" data-rich data-paste-url="/admin/blog/paste">
          <div class="rich-bar" role="toolbar" aria-label="본문 꾸미기">
            <select data-rich-block aria-label="문단 모양">
              <option value="p">본문</option>
              <option value="h2">소제목</option>
              <option value="h3">작은 제목</option>
              <option value="blockquote">인용</option>
            </select>
            <select data-rich-size aria-label="글자 크기">
              <option value="">크기</option>
              <option value="2">작게</option>
              <option value="3">보통</option>
              <option value="4">크게</option>
              <option value="6">아주 크게</option>
            </select>
            <span class="rich-sep" aria-hidden="true"></span>
            <button type="button" data-cmd="bold" title="굵게 (Ctrl+B)"><b>B</b></button>
            <button type="button" data-cmd="italic" title="기울임 (Ctrl+I)"><i>I</i></button>
            <button type="button" data-cmd="underline" title="밑줄 (Ctrl+U)"><u>U</u></button>
            <button type="button" data-cmd="strikeThrough" title="취소선"><s>S</s></button>
            <span class="rich-pop-wrap">
              <button type="button" data-pop="color" title="글자색"><span class="rich-a">가</span><span class="rich-swatch" data-swatch="color"></span></button>
              <div class="rich-pop" data-pop-panel="color" hidden>
<?php foreach (array('#000000', '#555555', '#999999', '#E03131', '#F76707', '#F59F00', '#2F9E44', '#2F7D5C', '#1971C2', '#7048E8', '#C2255C', '#FFFFFF') as $c): ?>                <button type="button" data-color="<?= $c ?>" style="background:<?= $c ?>" aria-label="글자색 <?= $c ?>"></button>
<?php endforeach; ?>
              </div>
            </span>
            <span class="rich-pop-wrap">
              <button type="button" data-pop="hilite" title="형광펜"><span class="rich-a rich-hl">가</span></button>
              <div class="rich-pop" data-pop-panel="hilite" hidden>
<?php foreach (array('#FFF59D', '#FFE0B2', '#C8E6C9', '#B3E5FC', '#F8BBD0', '#E1BEE7') as $c): ?>                <button type="button" data-hilite="<?= $c ?>" style="background:<?= $c ?>" aria-label="형광펜 <?= $c ?>"></button>
<?php endforeach; ?>
                <button type="button" data-hilite="transparent" class="rich-none" aria-label="형광펜 없애기">없음</button>
              </div>
            </span>
            <span class="rich-sep" aria-hidden="true"></span>
            <button type="button" data-cmd="justifyLeft" title="왼쪽 정렬"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 10h10M4 14h16M4 18h10"/></svg></button>
            <button type="button" data-cmd="justifyCenter" title="가운데 정렬"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 10h10M4 14h16M7 18h10"/></svg></button>
            <button type="button" data-cmd="justifyRight" title="오른쪽 정렬"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M10 10h10M4 14h16M10 18h10"/></svg></button>
            <button type="button" data-cmd="insertUnorderedList" title="글머리 목록">• 목록</button>
            <button type="button" data-cmd="insertOrderedList" title="번호 목록">1. 목록</button>
            <span class="rich-sep" aria-hidden="true"></span>
            <button type="button" data-rich-link title="링크">링크</button>
            <button type="button" data-rich-image title="사진 넣기">사진</button>
            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden data-rich-file>
            <span class="rich-pop-wrap">
              <button type="button" data-pop="emoji" title="이모지">😊</button>
              <div class="rich-pop rich-emoji" data-pop-panel="emoji" hidden>
<?php foreach (array('😀', '😊', '😍', '🥰', '😆', '😂', '🤗', '😎', '🤔', '😮', '😢', '🙏', '👍', '👏', '💪', '🙌', '❤️', '💚', '💙', '⭐', '✨', '🌟', '🔥', '🎉', '✅', '☑️', '❗', '❓', '📌', '📍', '📞', '💬', '🧹', '🧽', '🧼', '🫧', '🪣', '🚽', '🚿', '🏢', '🏬', '🏭', '🏠', '🗓️', '⏰', '🌿', '🍀', '☀️') as $em): ?>                <button type="button" data-emoji="<?= $em ?>"><?= $em ?></button>
<?php endforeach; ?>
              </div>
            </span>
            <button type="button" data-cmd="insertHorizontalRule" title="구분선">―</button>
            <button type="button" data-cmd="removeFormat" title="서식 지우기">서식 지우기</button>
            <span class="editor-status" data-md-status></span>
          </div>
          <div class="rich-area" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="b-body-label" data-rich-area data-placeholder="첫 문단에 지역과 청소 종류를 자연스럽게 넣어 주세요. 네이버 블로그 글을 복사해 붙여 넣으면 굵게 · 색 · 크기 · 이모지 · 사진이 그대로 들어와요."><?= rich_clean_html($form['body']) ?></div>
          <textarea name="body" hidden><?= e($form['body']) ?></textarea>
          <input type="hidden" name="format" value="html">
        </div>
        <p class="field-help">네이버 블로그 · 웹페이지에서 복사해 붙여 넣으면 서식이 그대로 들어오고, 사진은 이 홈페이지로 가져와요. 서식 없이 글자만 붙이려면 Ctrl+Shift+V(맥은 ⌘+Shift+V)로 붙여 넣으세요.</p>
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
        <li data-check="heading">소제목 2개 이상</li>
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
