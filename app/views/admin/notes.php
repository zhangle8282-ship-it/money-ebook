<?php /** 관리자 › 블로그 › 경험 노트. 변수: $form, $edit, $errors, $show(unused | used), $notes, $left, $used */
$kindName = function ($k) {
    return AUTO_KINDS[$k][0] ?? '기타';
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>블로그</h1>
    <p class="muted">현장에서 직접 겪은 일을 적어 두면, 자동 글이 이 노트를 <b>먼저</b> 바탕으로 써요. 직접 겪은 이야기는 검색 사이트가 가장 높게 치고, 글마다 내용이 자연스럽게 달라져 누락도 줄어요. 노트에 없는 사실은 지어내지 않아요.</p>
  </div>
</div>
<?= view('admin/_blog_tabs', array('tab' => 'notes')) ?>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<form method="post" action="/admin/blog/notes<?= $edit ? '?edit=' . (int) $edit['id'] : '' ?>" enctype="multipart/form-data" class="card stack-lg" aria-labelledby="n-form">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <h2 id="n-form"><?= $edit ? '경험 노트 고치기' : '경험 노트 적기' ?></h2>
  <div class="field">
    <span class="field-label-strong">어떤 현장이었나요?</span>
    <fieldset class="segmented stock-kinds">
      <legend class="sr-only">현장 종류</legend>
<?php foreach (AUTO_KINDS as $k => $kk): ?>      <input type="radio" id="n-k-<?= $k ?>" name="kind" value="<?= $k ?>" class="sr-only"<?= $form['kind'] === $k ? ' checked' : '' ?>><label for="n-k-<?= $k ?>"><?= e($kk[0]) ?></label>
<?php endforeach; ?>
    </fieldset>
  </div>
  <div class="grid-3">
    <div class="field"><label for="n-region">지역</label><input id="n-region" name="region" type="text" maxlength="40" list="n-regions" value="<?= e($form['region']) ?>" placeholder="예: 금왕, 진천, 충북혁신도시"><datalist id="n-regions"><?php foreach (AUTO_REGIONS as $r): ?><option value="<?= e($r) ?>"><?php endforeach; ?></datalist></div>
    <div class="field"><label for="n-date">작업한 날 (선택)</label><input id="n-date" name="work_date" type="date" value="<?= e($form['work_date']) ?>"></div>
    <div class="field"><label for="n-title">한 줄 제목</label><input id="n-title" name="title" type="text" maxlength="120" required value="<?= e($form['title']) ?>" placeholder="예: 금왕 ○○공장 휴게실 첫 정기청소"></div>
  </div>
  <div class="field">
    <label for="n-body">겪은 일 (편하게, 말하듯이)</label>
    <textarea id="n-body" name="body" rows="9" maxlength="5000" required placeholder="아래 질문 중 아는 것만 적어도 돼요."><?= e($form['body']) ?></textarea>
    <ul class="note-guide">
      <li>어떤 곳이었나요? (평수 · 인원 · 층수 · 업종)</li>
      <li>처음 갔을 때 어떤 상태였나요? 어떤 문제를 부탁받았나요?</li>
      <li>어떻게 청소했나요? (순서 · 도구 · 시간 · 몇 명이서)</li>
      <li>끝나고 어땠나요? 고객이 한 말이나 반응은?</li>
      <li>지금은 주 몇 회 하나요? 다른 분께 해 주고 싶은 팁은?</li>
    </ul>
  </div>
  <div class="field">
    <label for="n-photos">이 현장 사진 (선택, 여러 장)</label>
    <input id="n-photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="file-input">
    <p class="field-help">여기 올린 사진은 사진 창고에 이 노트와 함께 들어가고, 이 노트로 쓴 글의 대표 사진이 돼요. 화질은 지키고 용량만 줄여요.</p>
  </div>
  <div class="note-actions"><button type="submit" class="btn btn-primary"><?= $edit ? '고친 내용 저장' : '노트 저장하기' ?></button><?php if ($edit): ?> <a class="btn btn-ghost" href="/admin/blog/notes">취소</a><?php endif; ?></div>
</form>

<section class="card stack" aria-labelledby="n-list">
  <div class="card-head submit-head">
    <h2 id="n-list">노트 <span class="sub">(아직 안 쓴 노트 <?= (int) $left ?>개 · 쓴 노트 <?= (int) $used ?>개)</span></h2>
    <nav class="tabs" aria-label="보기">
      <a href="/admin/blog/notes"<?= $show === 'unused' ? ' aria-current="page"' : '' ?>>안 쓴 노트</a>
      <a href="/admin/blog/notes?show=used"<?= $show === 'used' ? ' aria-current="page"' : '' ?>>쓴 노트</a>
    </nav>
  </div>
<?php if ($notes): ?>
  <ul class="note-list">
<?php foreach ($notes as $n): ?>
    <li>
      <div class="note-head"><span class="stock-kind"><?= e($kindName($n['kind'])) ?></span><strong><?= e($n['title']) ?></strong><span class="sub"><?= e(trim($n['region'] . ($n['work_date'] !== '' ? ' · ' . str_replace('-', '.', $n['work_date']) : ''), ' ·')) ?><?= (int) $n['photos'] ? ' · 사진 ' . (int) $n['photos'] . '장' : '' ?></span></div>
      <p class="note-body"><?= e(str_cut((string) $n['body'], 180)) ?></p>
<?php if ($n['used_post_id']): ?>
      <p class="sub">쓴 글: <a href="/admin/blog/<?= (int) $n['used_post_id'] ?>/edit"><?= e((string) $n['post_title']) ?></a></p>
<?php else: ?>
      <div class="note-actions">
        <a class="btn btn-outline btn-sm" href="/admin/blog/notes?edit=<?= (int) $n['id'] ?>">고치기</a>
        <form method="post" action="/admin/blog/notes" data-confirm="이 경험 노트를 지울까요? 함께 올린 사진(아직 안 쓴 것)도 지워져요."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $n['id'] ?>"><button type="submit" class="link-btn danger-link">지우기</button></form>
      </div>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ul>
<?php else: ?>
  <p class="muted"><?= $show === 'used' ? '아직 글에 쓴 노트가 없어요.' : '아직 적은 노트가 없어요. 위에서 첫 노트를 적어 보세요. 일주일에 2~3개만 적어도 글이 훨씬 좋아져요.' ?></p>
<?php endif; ?>
</section>
