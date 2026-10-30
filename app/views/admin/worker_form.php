<?php /** 그린청소 관리자 › 인력 배치 › 추가 · 고치기. 변수: $worker, $form, $errors, $picked(고른 지역), $other(기타 지역 글자) */
$formRole = worker_role($form);
$action = $worker ? '/admin/workers/' . (int) $worker['id'] . '/edit' : '/admin/workers/new';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1><?= $worker ? '인력 정보 고치기' : '새 사람 추가' ?></h1>
    <p class="muted">청소 담당은 이름 · 커버 가능한 지역 · 원하는 방식을 꼭 넣어 주세요. 대표 · 운영팀은 이름만 있으면 돼요.</p>
  </div>
  <a class="btn btn-ghost" href="/admin/workers">목록으로</a>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" class="card stack-lg worker-form" data-worker-form>
  <?= csrf_field() ?>
  <fieldset class="field team-picker">
    <legend>역할 <span class="req">*</span></legend>
    <div class="region-checks">
<?php foreach (WORKER_ROLES as $key => $label): ?>
      <input type="radio" id="role-<?= e($key) ?>" name="role" value="<?= e($key) ?>"<?= $formRole === $key ? ' checked' : '' ?>><label for="role-<?= e($key) ?>"><?= e($label) ?></label>
<?php endforeach; ?>
    </div>
    <p class="field-help" data-partner-only<?= $formRole === 'byeong' ? ' hidden' : '' ?>>저장하면 정기청소 · 일회성 정산의 대표 · 운영 칸에서 바로 고를 수 있어요. 지급 계좌는 정기청소 정산 › 파트너 · 계좌에서 넣어요.</p>
  </fieldset>

  <div class="grid-2">
    <div class="field"><label for="w-name">이름 <span class="req">*</span></label><input id="w-name" name="name" type="text" maxlength="60" required value="<?= e($form['name']) ?>" placeholder="예: 김민수" autocomplete="off"></div>
    <div class="field"><label for="w-phone">연락처</label><input id="w-phone" name="phone" type="tel" inputmode="tel" maxlength="40" value="<?= e($form['phone']) ?>" placeholder="010-0000-0000" autocomplete="off"></div>
  </div>

  <fieldset class="field team-picker" data-cleaner-only<?= $formRole === 'byeong' ? '' : ' hidden' ?>>
    <legend>구성 <span class="sub">(혼자인지, 누구와 함께 일하는지)</span></legend>
    <div class="region-checks">
<?php foreach (WORKER_TEAMS as $key => $label): ?>
      <input type="radio" id="t-<?= e($key) ?>" name="team" value="<?= e($key) ?>"<?= ($form['team'] ?? '') === $key ? ' checked' : '' ?>><label for="t-<?= e($key) ?>"><?= e($label) ?></label>
<?php endforeach; ?>
      <input type="radio" id="t-none" name="team" value=""<?= ($form['team'] ?? '') === '' ? ' checked' : '' ?>><label for="t-none" class="team-none">모름</label>
    </div>
    <div class="field team-note"><label for="w-team-note">기타 관계</label><input id="w-team-note" name="team_note" type="text" maxlength="30" value="<?= e($form['team_note'] ?? '') ?>" placeholder="‘기타’를 골랐을 때 적어요 (예: 자매, 이웃, 부자)"></div>
  </fieldset>

  <fieldset class="field region-picker" data-cleaner-only<?= $formRole === 'byeong' ? '' : ' hidden' ?>>
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

  <fieldset class="field method-picker" data-cleaner-only<?= $formRole === 'byeong' ? '' : ' hidden' ?>>
    <legend>원하는 방식 <span class="req">*</span> <span class="sub">(둘 다 골라도 돼요)</span></legend>
    <div class="method-options">
<?php $chosen = worker_methods($form['method']); foreach (WORKER_METHODS as $key => $m): ?>
      <label class="method-option"><input type="checkbox" name="methods[]" value="<?= e($key) ?>"<?= in_array($key, $chosen, true) ? ' checked' : '' ?>><span><strong><?= e($m[0]) ?></strong><small><?= e($m[1]) ?></small></span></label>
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
<form method="post" action="/admin/workers/<?= (int) $worker['id'] ?>/delete" id="worker-delete" data-confirm="‘<?= e($worker['name']) ?>’ 님 정보를 지울까요?<?php $jobs = worker_assignments()[(int) $worker['id']] ?? array(); if ($jobs): ?> 맡고 있는 정기청소(<?= e(implode(', ', array_column($jobs, 1))) ?>)의 <?= e(WORKER_ROLES[worker_role($worker)]) ?>에서도 빠져요.<?php endif; ?> 되돌릴 수 없어요."><?= csrf_field() ?></form>
<?php endif; ?>
