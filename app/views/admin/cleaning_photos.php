<?php /** 그린청소 관리자 › 사진 관리. 변수: $photos, $errors */
$slot = function ($field, $current, $label, $help = '') {
    $out = '<div class="photo-slot"><div class="photo-preview">';
    $out .= $current !== '' ? '<img src="' . e($current) . '" alt="' . e($label) . '">' : '<span>사진 없음</span>';
    $out .= '</div><div class="field"><label for="p-' . $field . '">' . e($label) . '</label>'
        . '<input id="p-' . $field . '" name="' . $field . '" type="file" accept="image/jpeg,image/png,image/webp" class="file-input" data-resize></div>';
    if ($current !== '') {
        $out .= '<label class="check-row small-check"><input type="checkbox" name="remove_' . $field . '" value="1"> <span>지우기</span></label>';
    }
    return $out . ($help !== '' ? '<p class="field-help">' . e($help) . '</p>' : '') . '</div>';
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>사진 관리</h1>
    <p class="muted">홈페이지에 보일 사진을 올려요. 사진이 없는 구역은 홈페이지에서 자동으로 숨겨져요. 큰 사진은 올릴 때 알맞은 크기로 줄여서 올라가요.</p>
  </div>
  <button type="submit" form="photo-form" class="btn btn-primary">저장하기</button>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="/admin/photos" enctype="multipart/form-data" id="photo-form" class="settings" data-photo-form>
  <?= csrf_field() ?>
  <section class="card stack-lg" aria-labelledby="ph-site">
    <div class="card-intro"><h2 id="ph-site">작업 현장 사진</h2><p class="muted">첫 화면 바로 아래 한 줄로 보여요(최대 <?= CLEANING_SITE_PHOTOS ?>장). 실제 작업 모습이면 좋아요.</p></div>
    <div class="photo-grid">
<?php for ($i = 0; $i < CLEANING_SITE_PHOTOS; $i++): ?>
      <?= $slot('site_' . $i, $photos['site'][$i], '사진 ' . ($i + 1)) ?>
<?php endfor; ?>
    </div>
  </section>

  <section class="card stack-lg" aria-labelledby="ph-ba">
    <div class="card-intro"><h2 id="ph-ba">청소 전후</h2><p class="muted">‘청소 전’과 ‘청소 후’ 사진이 둘 다 있는 묶음만 보여요. 같은 자리에서 같은 방향으로 찍은 사진이 좋아요.</p></div>
<?php for ($i = 0; $i < CLEANING_BA_PAIRS; $i++): $p = $photos['ba'][$i]; ?>
    <div class="ba-pair">
      <div class="field"><label for="ba-title-<?= $i ?>">묶음 <?= $i + 1 ?> 제목</label><input id="ba-title-<?= $i ?>" name="ba_title_<?= $i ?>" type="text" maxlength="40" value="<?= e($p['title']) ?>"></div>
      <div class="photo-grid photo-grid-2">
        <?= $slot('ba_before_' . $i, $p['before'], '청소 전') ?>
        <?= $slot('ba_after_' . $i, $p['after'], '청소 후') ?>
      </div>
    </div>
<?php endfor; ?>
  </section>

  <section class="card stack-lg" aria-labelledby="ph-map">
    <div class="card-intro"><h2 id="ph-map">서비스 지역 지도</h2><p class="muted">없으면 음성·진천·혁신도시를 표시한 그림이 대신 보여요.</p></div>
    <div class="photo-grid photo-grid-2"><?= $slot('map', $photos['map'], '지도 이미지') ?></div>
  </section>
</form>
