<?php
/** 블로그 글 중간에 자동으로 들어가는 ‘그린청소 청소 범위’. 변수: $kind(먼저 보여 줄 종류: office|building|restroom) */
$scope = cleaning_scope();
$kind = array_key_exists($kind ?? '', CLEANING_KINDS) ? $kind : 'office';
?>
<aside class="g-post-scope" aria-labelledby="ps-title">
  <div class="g-post-scope-head">
    <div><span class="g-kicker">— <?= e(gc('name')) ?> 청소 범위 —</span><h2 id="ps-title" class="g-post-scope-title">어디까지 청소해 주나요?</h2></div>
    <div class="g-post-tabs" role="tablist" aria-label="청소 종류">
<?php foreach (CLEANING_KINDS as $key => $label): $on = $key === $kind; ?>
      <button type="button" role="tab" id="tab-<?= $key ?>" aria-controls="panel-<?= $key ?>" aria-selected="<?= $on ? 'true' : 'false' ?>"<?= $on ? '' : ' tabindex="-1"' ?> data-tab><?= e($label) ?></button>
<?php endforeach; ?>
    </div>
  </div>
<?php foreach (CLEANING_KINDS as $key => $label): ?>
  <div class="g-post-scope-panel" role="tabpanel" id="panel-<?= $key ?>" aria-labelledby="tab-<?= $key ?>"<?= $key === $kind ? '' : ' data-hidden' ?>>
    <ul class="g-post-checks">
<?php foreach ($scope[$key] as $item): ?>
      <li><span class="g-check" aria-hidden="true">✓</span><?= e($item) ?></li>
<?php endforeach; ?>
    </ul>
    <p class="g-post-scope-foot">기본 항목 밖의 요청도 견적 때 함께 정해요. <a href="#quote" data-pick-kind="<?= e($key) ?>"><?= e($label) ?> 청소 견적 받기 →</a></p>
  </div>
<?php endforeach; ?>
</aside>
