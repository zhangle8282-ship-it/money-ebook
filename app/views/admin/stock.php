<?php /** 관리자 › 블로그 › 사진 창고. 변수: $photos, $show(unused | used), $counts(stock_counts), $used */
$kindName = function ($k) {
    return AUTO_KINDS[$k][0] ?? '기타';
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>블로그</h1>
    <p class="muted">실제 현장 사진을 모아 두는 곳이에요. 자동 글이 올라갈 때 글 종류(사무실 · 공장 · 화장실 · 건물상가)에 맞는 <b>아직 안 쓴 사진</b>을 먼저 올린 순서대로 하나씩 대표 사진으로 써요. 한 번 쓴 사진은 다시 쓰지 않아요.</p>
  </div>
</div>
<?= view('admin/_blog_tabs', array('tab' => 'stock')) ?>

<form method="post" action="/admin/blog/stock" enctype="multipart/form-data" class="card stack-lg" aria-labelledby="st-up">
  <?= csrf_field() ?><input type="hidden" name="action" value="upload">
  <h2 id="st-up">사진 올리기</h2>
  <div class="grid-2">
    <div class="field">
      <span class="field-label-strong">어떤 현장 사진인가요?</span>
      <fieldset class="segmented stock-kinds">
        <legend class="sr-only">사진 종류</legend>
<?php $first = true; foreach (AUTO_KINDS + array('etc' => array('기타 (어느 글에나)')) as $k => $kk): ?>
        <input type="radio" id="st-k-<?= $k ?>" name="kind" value="<?= $k ?>" class="sr-only"<?= $first ? ' checked' : '' ?>><label for="st-k-<?= $k ?>"><?= e($kk[0]) ?></label>
<?php $first = false; endforeach; ?>
      </fieldset>
    </div>
    <div class="field"><label for="st-memo">메모 (선택)</label><input id="st-memo" name="memo" type="text" maxlength="100" placeholder="예: 금왕 ○○공장 휴게실, 2026년 10월"></div>
  </div>
  <div class="field">
    <label for="st-files">사진 고르기 (여러 장 한 번에)</label>
    <input id="st-files" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="file-input">
    <p class="field-help">휴대폰 사진을 그대로 골라도 돼요. 화질은 지키고 용량만 줄여서(긴 변 <?= IMAGE_MAX_SIDE['stock'] ?>px) 넣고, 사진 속 위치 정보는 지워요. 한 번에 20장까지 올릴 수 있어요.</p>
  </div>
  <div><button type="submit" class="btn btn-primary">사진 창고에 넣기</button></div>
</form>

<section class="card stack" aria-labelledby="st-list">
  <div class="card-head submit-head">
    <h2 id="st-list">사진 <span class="sub">(안 쓴 사진 <?= (int) $counts['all'] ?>장 · 쓴 사진 <?= (int) $used ?>장)</span></h2>
    <nav class="tabs" aria-label="보기">
      <a href="/admin/blog/stock"<?= $show === 'unused' ? ' aria-current="page"' : '' ?>>안 쓴 사진</a>
      <a href="/admin/blog/stock?show=used"<?= $show === 'used' ? ' aria-current="page"' : '' ?>>쓴 사진</a>
    </nav>
  </div>
  <ul class="stock-left">
<?php foreach (AUTO_KINDS as $k => $kk): $n = (int) $counts[$k]; ?>    <li class="<?= $n < 3 ? 'is-low' : '' ?>"><span><?= e($kk[0]) ?></span><b><?= $n ?>장</b></li>
<?php endforeach; ?>    <li><span>기타</span><b><?= (int) $counts['etc'] ?>장</b></li>
  </ul>
<?php if ($photos): ?>
  <ul class="stock-grid">
<?php foreach ($photos as $p): ?>
    <li>
      <a href="<?= e($p['path']) ?>" target="_blank" rel="noopener" class="stock-img"><img src="<?= e($p['path']) ?>" alt="<?= e($kindName($p['kind'])) ?> 현장 사진" loading="lazy"></a>
      <div class="stock-meta">
        <span class="stock-kind"><?= e($kindName($p['kind'])) ?></span>
        <span class="sub"><?= e(public_image_info($p['path'])) ?></span>
<?php if ($p['memo'] !== ''): ?>        <span class="sub"><?= e($p['memo']) ?></span>
<?php endif; ?>
<?php if ($p['used_post_id']): ?>
        <span class="sub">쓴 글: <a href="/admin/blog/<?= (int) $p['post_id'] ?>/edit"><?= e(str_cut((string) $p['post_title'], 30)) ?></a></span>
<?php else: ?>
        <form method="post" action="/admin/blog/stock" class="stock-actions">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <label class="sr-only" for="st-kind-<?= (int) $p['id'] ?>">종류 바꾸기</label>
          <select id="st-kind-<?= (int) $p['id'] ?>" name="kind">
<?php foreach (AUTO_KINDS + array('etc' => array('기타')) as $k => $kk): ?>            <option value="<?= $k ?>"<?= $p['kind'] === $k ? ' selected' : '' ?>><?= e($kk[0]) ?></option>
<?php endforeach; ?>
          </select>
          <button type="submit" name="action" value="kind" class="btn btn-outline btn-sm">바꾸기</button>
          <button type="submit" name="action" value="delete" class="link-btn danger-link" data-confirm-click="이 사진을 지울까요?">지우기</button>
        </form>
<?php endif; ?>
      </div>
    </li>
<?php endforeach; ?>
  </ul>
<?php else: ?>
  <p class="muted"><?= $show === 'used' ? '아직 글에 쓴 사진이 없어요.' : '아직 올린 사진이 없어요. 위에서 현장 사진을 올려 주세요.' ?></p>
<?php endif; ?>
</section>
