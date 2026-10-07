<?php /** 그린청소 관리자 › 인력 배치 › 추가 · 고치기. 변수: $worker, $form, $errors, $picked(고른 지역), $other(기타 지역 글자) */
$action = $worker ? '/admin/workers/' . (int) $worker['id'] . '/edit' : '/admin/workers/new';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1><?= $worker ? '인력 정보 고치기' : '새 사람 추가' ?></h1>
    <p class="muted">이름 · 커버 가능한 지역 · 원하는 방식은 꼭 넣어 주세요. 연락처와 메모는 있으면 좋아요.</p>
  </div>
  <a class="btn btn-ghost" href="/admin/workers">목록으로</a>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" class="card stack-lg worker-form">
  <?= csrf_field() ?>
  <div class="grid-2">
    <div class="field"><label for="w-name">이름 <span class="req">*</span></label><input id="w-name" name="name" type="text" maxlength="60" required value="<?= e($form['name']) ?>" placeholder="예: 김민수" autocomplete="off"></div>
    <div class="field"><label for="w-phone">연락처</label><input id="w-phone" name="phone" type="tel" inputmode="tel" maxlength="40" value="<?= e($form['phone']) ?>" placeholder="010-0000-0000" autocomplete="off"></div>
  </div>

  <fieldset class="field region-picker">
    <legend>커버 가능한 지역 <span class="req">*</span> <span class="sub">(여러 곳 고를 수 있어요)</span></legend>
<?php foreach (WORKER_REGIONS as $group => $list): ?>
    <div class="region-group">
      <span class="region-group-name"><?= e($group) ?></span>
      <div class="region-checks">
<?php foreach ($list as $r): $rid = 'r-' . md5($r); ?>
        <input type="checkbox" id="<?= $rid ?>" name="regions[]" value="<?= e($r) ?>"<?= in_array($r, $picked, true) ? ' checked' : '' ?>><label for="<?= $rid ?>"><?= e($r) ?></label>
<?php endforeach; ?>
      </div>
    </div>
<?php endforeach; ?>
    <div class="field"><label for="w-other">기타 지역</label><input id="w-other" name="regions_other" type="text" maxlength="300" value="<?= e($other) ?>" placeholder="목록에 없는 곳은 쉼표로 (예: 청주 오창, 충주)"></div>
  </fieldset>

  <fieldset class="field method-picker">
    <legend>원하는 방식 <span class="req">*</span></legend>
    <div class="method-options">
<?php foreach (WORKER_METHODS as $key => $m): ?>
      <label class="method-option"><input type="radio" name="method" value="<?= e($key) ?>"<?= $form['method'] === $key ? ' checked' : '' ?> required><span><strong><?= e($m[0]) ?></strong><small><?= e($m[1]) ?></small></span></label>
<?php endforeach; ?>
    </div>
  </fieldset>

  <div class="field"><label for="w-memo">메모</label><textarea id="w-memo" name="memo" rows="3" maxlength="1000" placeholder="예: 평일 오전 가능, 차량 있음, 공장 청소 경험"><?= e($form['memo']) ?></textarea></div>

  <div class="form-actions-row">
    <button type="submit" class="btn btn-primary"><?= $worker ? '저장하기' : '추가하기' ?></button>
    <a class="btn btn-ghost" href="/admin/workers">취소</a>
<?php if ($worker): ?>    <button type="submit" form="worker-delete" class="btn btn-danger btn-sm push-right">이 사람 지우기</button>
<?php endif; ?>
  </div>
</form>
<?php if ($worker): ?>
<form method="post" action="/admin/workers/<?= (int) $worker['id'] ?>/delete" id="worker-delete" data-confirm="‘<?= e($worker['name']) ?>’ 님 정보를 지울까요? 되돌릴 수 없어요."><?= csrf_field() ?></form>
<?php endif; ?>
