<?php /** 관리자 › 도급 정산 › 파트너 역할. 변수: $roles */
$descs = array('gap' => '계약 · 세금 · 고객 응대', 'eul' => '홈페이지 · 홍보 · 인원 운영', 'byeong' => '청소담당자 · 현장 작업');
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>도급 정산</h1>
    <p class="muted">대표파트너 · 운영파트너 · 청소 담당 파트너가 각자 맡은 일을 한눈에 정리해요. 일을 더하거나 빼고, 순서를 바꾸거나 다른 파트너에게 넘길 수 있어요.</p>
  </div>
</div>
<?= view('admin/_contracts_tabs', array('tab' => 'roles')) ?>
<div class="role-board">
<?php foreach (CONTRACT_ROLE_SIDES as $side => $label): $list = $roles[$side]; ?>
  <section class="card role-card role-<?= $side ?>" aria-labelledby="role-<?= $side ?>">
    <div class="role-head">
      <span class="role-badge" title="<?= e($label) ?>"><?= e(CONTRACT_ROLE_SHORT[$side]) ?></span>
      <div><h2 id="role-<?= $side ?>"><?= e($label) ?>가 하는 일</h2><p class="sub"><?= e($descs[$side]) ?> · <?= count($list) ?>개</p></div>
    </div>
<?php if ($list): ?>
    <ol class="role-list">
<?php foreach ($list as $i => $task): ?>
      <li>
        <span class="role-no"><?= $i + 1 ?></span>
        <span class="role-task"><?= e($task) ?></span>
        <span class="role-actions">
          <form method="post" action="/admin/contracts/roles"><?= csrf_field() ?><input type="hidden" name="side" value="<?= $side ?>"><input type="hidden" name="index" value="<?= $i ?>">
            <button type="submit" name="action" value="up" class="icon-btn" aria-label="‘<?= e($task) ?>’ 위로"<?= $i === 0 ? ' disabled' : '' ?>>↑</button>
            <button type="submit" name="action" value="down" class="icon-btn" aria-label="‘<?= e($task) ?>’ 아래로"<?= $i === count($list) - 1 ? ' disabled' : '' ?>>↓</button>
<?php foreach (CONTRACT_ROLE_SIDES as $to => $toLabel): if ($to === $side) { continue; } ?>
            <button type="submit" name="action" value="move_<?= $to ?>" class="icon-btn move-btn" title="<?= e($toLabel) ?>에게 넘기기" aria-label="‘<?= e($task) ?>’ <?= e($toLabel) ?>에게 넘기기">→ <?= e(CONTRACT_ROLE_SHORT[$to]) ?></button>
<?php endforeach; ?>
            <button type="submit" name="action" value="delete" class="icon-btn del-btn" aria-label="‘<?= e($task) ?>’ 빼기" data-confirm-click="‘<?= e($task) ?>’을(를) <?= e($label) ?>가 하는 일에서 뺄까요?">×</button>
          </form>
        </span>
      </li>
<?php endforeach; ?>
    </ol>
<?php else: ?>
    <p class="muted role-empty">아직 정한 일이 없어요.</p>
<?php endif; ?>
    <form method="post" action="/admin/contracts/roles" class="role-add">
      <?= csrf_field() ?><input type="hidden" name="side" value="<?= $side ?>"><input type="hidden" name="action" value="add">
      <label class="sr-only" for="add-<?= $side ?>"><?= e($label) ?>가 할 일</label>
      <input id="add-<?= $side ?>" name="task" type="text" maxlength="40" placeholder="예: <?= array('gap' => '입금 확인', 'eul' => '청소 사진 올리기', 'byeong' => '청소 일지 쓰기')[$side] ?>" required>
      <button type="submit" class="btn btn-primary btn-sm">+ 더하기</button>
    </form>
  </section>
<?php endforeach; ?>
</div>
<form method="post" action="/admin/contracts/roles" class="role-reset" data-confirm="파트너 역할을 처음 목록으로 되돌릴까요? 더하거나 바꾼 내용은 사라져요.">
  <?= csrf_field() ?><input type="hidden" name="action" value="reset">
  <button type="submit" class="link-btn">처음 목록으로 되돌리기</button>
</form>
