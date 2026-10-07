<?php /** 청소 담당: 지금 누가 배치됐는지 + 바로 바꾸기. 변수: $c(청소), $back(배치한 뒤 돌아올 주소) */
$who = contract_partner($c, 'byeong');
$choices = cleaner_choices();
$current = $c['byeong_partner_id'] ? (string) (int) $c['byeong_partner_id'] : '';
?>
<div class="who">
<?php if ($who): ?>  <span class="who-chip"><?= e($who['name']) ?></span><span class="sub"><?= $who['phone'] !== '' ? e($who['phone']) . ' · ' : '' ?><?= !empty($who['worker_id']) ? '인력 배치' : '청소 담당 파트너' ?></span>
<?php elseif ($c['byeong_name'] !== ''): ?>  <span class="who-chip is-typed"><?= e($c['byeong_name']) ?></span><span class="sub">적어 둔 이름 · 아래에서 골라 배치해 주세요</span>
<?php else: ?>  <span class="who-empty">아직 배치 안 됨</span>
<?php endif; ?>
</div>
<form method="post" action="/admin/contracts/<?= (int) $c['id'] ?>/assign" class="assign-form">
  <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
  <select name="byeong" aria-label="‘<?= e($c['name']) ?>’ 청소 담당">
    <option value=""><?= $current !== '' ? '— 청소 담당 비우기 —' : '— 사람 고르기 —' ?></option>
<?php if ($choices['workers']): ?>    <optgroup label="인력 배치">
<?php foreach ($choices['workers'] as $o): ?>      <option value="<?= e($o[0]) ?>"<?= $o[0] === $current ? ' selected' : '' ?>><?= e($o[1]) ?></option>
<?php endforeach; ?>    </optgroup>
<?php endif; ?>
<?php if ($choices['others']): ?>    <optgroup label="그 밖의 청소 담당 파트너">
<?php foreach ($choices['others'] as $o): ?>      <option value="<?= e($o[0]) ?>"<?= $o[0] === $current ? ' selected' : '' ?>><?= e($o[1]) ?></option>
<?php endforeach; ?>    </optgroup>
<?php endif; ?>
  </select>
  <button type="submit" class="btn btn-primary btn-sm"><?= $current !== '' ? '바꾸기' : '배치' ?></button>
</form>
