<?php
/** 관리자 › 디자인. 변수: $values, $errors */
$store = setting('store_name');
$fontSelect = function ($name, $label) use ($values) {
    $out = '<div class="field"><label for="' . $name . '">' . e($label) . '</label><select id="' . $name . '" name="' . $name . '" data-design>';
    $groups = array('기본 글씨체' => DESIGN_BASE_FONTS, '올린 글씨체' => array_diff_key(design_fonts(), DESIGN_BASE_FONTS));
    foreach ($groups as $group => $fonts) {
        if (!$fonts) {
            continue;
        }
        $out .= '<optgroup label="' . e($group) . '">';
        foreach ($fonts as $key => $f) {
            $out .= '<option value="' . e($key) . '" data-family="' . e(font_stack($key)) . '"' . ($values[$name] === $key ? ' selected' : '')
                . ' style="font-family:' . e(font_stack($key)) . '">' . e($f[0]) . '</option>';
        }
        $out .= '</optgroup>';
    }
    return $out . '</select></div>';
};
$range = function ($name, $label, $unit = 'px') use ($values) {
    $r = DESIGN_RANGES[$name];
    return '<div class="field range-field"><label for="' . $name . '">' . e($label) . ' <output for="' . $name . '" data-out="' . $name . '">' . (int) $values[$name] . $unit . '</output></label>'
        . '<input id="' . $name . '" name="' . $name . '" type="range" min="' . $r[0] . '" max="' . $r[1] . '" value="' . (int) $values[$name] . '" data-design data-unit="' . $unit . '"></div>';
};
$color = function ($name, $label, $presets) use ($values) {
    $out = '<div class="field"><span class="field-label-strong">' . e($label) . '</span><div class="color-row">'
        . '<input type="color" id="' . $name . '" name="' . $name . '" value="' . e($values[$name]) . '" data-design aria-label="' . e($label) . ' 직접 고르기">';
    foreach ($presets as $hex => $title) {
        $out .= '<button type="button" class="swatch" style="background:' . $hex . '" data-swatch="' . $name . '" data-color="' . $hex . '" title="' . e($title) . '" aria-label="' . e($label) . ' ' . e($title) . '"></button>';
    }
    return $out . '</div></div>';
};
$areaPresets = array('#F6F4EF' => '기본 미색', '#FFFFFF' => '흰색', '#1D1C1A' => '먹색', '#2E5E4E' => '초록', '#1F3A5F' => '남색', '#8C4A3C' => '벽돌색', '#E9D8A6' => '모래색');
$mainPresets = array('#F6F4EF' => '기본 미색', '#FFFFFF' => '흰색', '#F4F6F8' => '연한 회색', '#FAF6EE' => '연한 베이지', '#F1F5F2' => '연한 초록', '#F7F2F7' => '연한 분홍');
?>
<link rel="stylesheet" href="<?= e(design_fonts_url(true)) ?>">
<style><?= font_face_css(true) ?></style>
<div class="page-head">
  <div class="page-head-text">
    <h1>디자인</h1>
    <p class="muted">스토어의 상단 영역(헤더)·본문 영역·하단 영역(푸터) 모양을 정해요. 오른쪽 미리보기에서 바로 확인할 수 있어요.</p>
  </div>
  <div class="head-actions">
    <button type="submit" form="design-reset" class="btn btn-ghost">기본값으로 되돌리기</button>
    <button type="submit" form="design-form" class="btn btn-primary">저장하기</button>
  </div>
</div>
<?= view('admin/_design_tabs', array('tab' => 'design')) ?>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<p class="field-help design-font-note">글씨체는 기본 글씨체와 직접 올린 글씨체 중에서 골라요. 다른 글씨체를 쓰고 싶으면 <a href="/admin/design/fonts">글씨체</a> 탭에서 파일을 올려 추가하세요.</p>

<form method="post" action="/admin/design" enctype="multipart/form-data" id="design-form" class="form-grid design-grid">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="form-main">
    <section class="card stack-lg" aria-labelledby="d-logo">
      <h2 id="d-logo">로고</h2>
      <fieldset class="segmented">
        <legend class="sr-only">로고 종류</legend>
        <input type="radio" id="logo-text" name="design_logo_type" value="text" class="sr-only" data-design<?= $values['design_logo_type'] !== 'image' ? ' checked' : '' ?>>
        <label for="logo-text">텍스트 로고</label>
        <input type="radio" id="logo-image" name="design_logo_type" value="image" class="sr-only" data-design<?= $values['design_logo_type'] === 'image' ? ' checked' : '' ?>>
        <label for="logo-image">이미지 로고</label>
      </fieldset>
      <div class="stack" data-logo-panel="text">
        <p class="field-help">스토어 이름(<?= e($store) ?>)을 글씨로 보여 줘요. 이름은 설정에서 바꿔요.</p>
        <div class="grid-2">
          <?= $fontSelect('design_logo_font', '로고 글씨체') ?>
          <?= $range('design_logo_size', '로고 글씨 크기') ?>
        </div>
      </div>
      <div class="stack" data-logo-panel="image">
        <div class="logo-upload">
          <div class="logo-current"><img id="logo-img" src="<?= e($values['design_logo_image']) ?>" alt=""<?= $values['design_logo_image'] === '' ? ' hidden' : '' ?>><span class="muted" id="logo-empty"<?= $values['design_logo_image'] !== '' ? ' hidden' : '' ?>>아직 올린 로고가 없어요</span></div>
          <div class="field">
            <label for="logo_image">로고 이미지 (PNG·JPG·WEBP, 가로로 긴 이미지 권장)</label>
            <input id="logo_image" name="logo_image" type="file" accept="image/png,image/jpeg,image/webp" class="file-input">
          </div>
<?php if ($values['design_logo_image'] !== ''): ?>
          <label class="check-row"><input type="checkbox" name="remove_logo" value="1"> <span>올린 로고 이미지 지우기</span></label>
<?php endif; ?>
        </div>
        <?= $range('design_logo_height', '로고 높이') ?>
      </div>
    </section>

    <section class="card stack-lg" aria-labelledby="d-header">
      <h2 id="d-header">상단 영역 (헤더)</h2>
      <?= $color('design_header_bg', '배경색', $areaPresets) ?>
      <?= $range('design_header_h', '높이 (위아래 크기)') ?>
      <div class="grid-2">
        <?= $fontSelect('design_header_font', '메뉴 글씨체') ?>
        <?= $range('design_header_size', '메뉴 글씨 크기') ?>
      </div>
      <p class="field-help">어두운 배경을 고르면 글씨가 자동으로 밝아져요.</p>
    </section>

    <section class="card stack-lg" aria-labelledby="d-main">
      <h2 id="d-main">본문 영역</h2>
      <?= $color('design_main_bg', '배경색', $mainPresets) ?>
      <div class="grid-2">
        <?= $fontSelect('design_body_font', '본문 글씨체') ?>
        <?= $fontSelect('design_heading_font', '제목 글씨체') ?>
      </div>
      <?= $range('design_body_size', '본문 글씨 크기') ?>
      <p class="field-help">글씨 크기를 바꾸면 본문의 제목·설명·버튼 글씨가 같은 비율로 커지거나 작아져요. 본문 배경은 글씨가 잘 보이도록 밝은 색만 쓸 수 있어요.</p>
    </section>

    <section class="card stack-lg" aria-labelledby="d-footer">
      <h2 id="d-footer">하단 영역 (푸터)</h2>
      <?= $color('design_footer_bg', '배경색', $areaPresets) ?>
      <div class="grid-2">
        <?= $fontSelect('design_footer_font', '글씨체') ?>
        <?= $range('design_footer_size', '글씨 크기') ?>
      </div>
    </section>
  </div>

  <aside class="form-side design-side">
    <section class="card stack" aria-labelledby="d-preview">
      <h2 id="d-preview">미리보기</h2>
      <div class="design-preview" id="design-preview">
        <div class="dp-header">
          <span class="dp-logo"><img id="dp-logo-img" src="<?= e($values['design_logo_image']) ?>" alt=""><span id="dp-logo-text"><?= e($store) ?></span></span>
          <span class="dp-nav"><b>전체</b><span>신간</span><span>베스트</span></span>
        </div>
        <div class="dp-main">
          <div class="dp-title">오늘 읽을 한 권을 찾아보세요</div>
          <p class="dp-text">구매 전에 본문 일부를 무료로 미리 읽어볼 수 있어요.</p>
          <div class="dp-cards"><i></i><i></i><i></i></div>
        </div>
<?php $dpBiz = array_slice(business_lines(), 0, 3); ?>
        <div class="dp-footer">
          <div class="dp-foot-top"><b><?= e($store) ?></b><span>이용약관 · 개인정보처리방침</span></div>
          <div class="dp-foot-biz"><?php if ($dpBiz): foreach ($dpBiz as $i => $b): ?><?= $i ? ' | ' : '' ?><?= e($b[1] . ' ' . $b[2]) ?><?php endforeach; ?> …<?php else: ?>상호 · 대표 · 사업자등록번호 …<?php endif; ?></div>
        </div>
      </div>
      <p class="field-help">실제 화면은 저장한 뒤 <a href="/" target="_blank" rel="noopener">스토어</a>에서 확인하세요.</p>
    </section>
  </aside>
</form>
<form method="post" action="/admin/design" id="design-reset" data-confirm="디자인을 처음 모습으로 되돌릴까요? 올린 로고 이미지도 지워져요.">
  <?= csrf_field() ?><input type="hidden" name="action" value="reset">
</form>
