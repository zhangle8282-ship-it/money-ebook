<?php /** 관리자 › 디자인 › 글씨체. 변수: $fonts, $form, $errors */
$accept = '.woff2,.woff,.ttf,.otf';
?>
<style><?= font_face_css(true) ?></style>
<link rel="stylesheet" href="<?= e(design_fonts_url(true)) ?>">
<div class="page-head">
  <div class="page-head-text">
    <h1>디자인</h1>
    <p class="muted">기본 글씨체 말고 쓰고 싶은 글씨체가 있으면 파일을 직접 올려요. 올린 글씨체는 ‘디자인 설정’의 글씨체 목록에 나와요.</p>
  </div>
</div>
<?= view('admin/_design_tabs', array('tab' => 'fonts')) ?>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<div class="form-grid">
  <div class="form-main">
    <section class="card flush" aria-labelledby="f-list">
      <div class="card-head pad-head"><h2 id="f-list">올린 글씨체</h2></div>
<?php if ($fonts): ?>
      <div class="table-wrap">
      <table class="table font-table">
        <thead><tr><th scope="col">글씨체 · 미리보기</th><th scope="col">파일</th><th scope="col">쓰는 곳</th><th scope="col" class="actions"><span class="sr-only">관리</span></th></tr></thead>
        <tbody>
<?php foreach ($fonts as $f):
    $key = 'u' . (int) $f['id'];
    $usedAt = array();
    foreach (DESIGN_FONT_KEYS as $setting => $label) {
        if (setting($setting) === $key) {
            $usedAt[] = $label;
        }
    }
?>
          <tr>
            <td>
              <strong><?= e($f['name']) ?></strong> <span class="sub"><?= e(FONT_KINDS[$f['kind']] ?? '') ?></span>
              <div class="font-sample" style="font-family:<?= e(font_stack($key)) ?>">오늘 읽을 한 권을 찾아보세요 <b>굵은 글씨</b> ABC 123</div>
            </td>
            <td><?= e(strtoupper(pathinfo($f['file_regular'], PATHINFO_EXTENSION))) ?> · <?= e(fmt_bytes($f['size'])) ?><div class="sub"><?= $f['file_bold'] !== '' ? '보통 + 굵은 파일' : '보통 파일만' ?></div></td>
            <td><?= $usedAt ? e(implode(', ', $usedAt)) : '<span class="sub">쓰지 않음</span>' ?></td>
            <td class="actions">
              <form method="post" action="/admin/design/fonts/<?= (int) $f['id'] ?>/delete" data-confirm="‘<?= e($f['name']) ?>’ 글씨체를 지울까요?<?= $usedAt ? ' 이 글씨체를 쓰던 곳은 기본 글씨체로 바뀌어요.' : '' ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-sm">삭제</button>
              </form>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
<?php else: ?>
      <p class="muted pad">아직 올린 글씨체가 없어요. ‘글씨체 올리기’에서 글씨체 파일을 올려 주세요.</p>
<?php endif; ?>
    </section>

    <section class="card stack" aria-labelledby="f-base">
      <h2 id="f-base">기본 글씨체</h2>
      <p class="field-help">아래 글씨체는 따로 올리지 않아도 바로 쓸 수 있어요. 누구나 상업적으로 무료로 쓸 수 있는 글꼴(SIL 오픈 폰트 라이선스)이에요.</p>
      <ul class="base-fonts">
<?php foreach (DESIGN_BASE_FONTS as $key => $f): ?>
        <li><span class="font-sample" style="font-family:<?= e(font_stack($key)) ?>"><?= e($f[0]) ?></span></li>
<?php endforeach; ?>
      </ul>
    </section>
  </div>

  <aside class="form-side">
    <form method="post" action="/admin/design/fonts" enctype="multipart/form-data" class="card stack-lg" aria-labelledby="f-add">
      <?= csrf_field() ?>
      <h2 id="f-add">글씨체 올리기</h2>
      <div class="field">
        <label for="font_name">글씨체 이름</label>
        <input id="font_name" name="font_name" type="text" maxlength="40" required value="<?= e($form['font_name']) ?>" placeholder="예: 스토어 제목체">
      </div>
      <div class="field">
        <label for="font_kind">글씨 모양</label>
        <select id="font_kind" name="font_kind">
<?php foreach (FONT_KINDS as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $form['font_kind'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
        <p class="field-help">글씨체 파일에 없는 글자는 비슷한 모양의 기본 글씨체로 보여 줘요.</p>
      </div>
      <div class="field">
        <label for="font_regular">보통 굵기 파일</label>
        <input id="font_regular" name="font_regular" type="file" accept="<?= $accept ?>" class="file-input" required>
      </div>
      <div class="field">
        <label for="font_bold">굵은 파일 (선택)</label>
        <input id="font_bold" name="font_bold" type="file" accept="<?= $accept ?>" class="file-input">
        <p class="field-help">없으면 제목처럼 굵은 글씨는 보통 파일을 굵게 만들어 보여 줘요.</p>
      </div>
      <p class="field-help">WOFF2 · WOFF · TTF · OTF, 파일 하나에 <?= FONT_MAX_MB ?>MB까지. 한글 글씨체는 파일이 커서 화면이 늦게 뜰 수 있으니 웹용 WOFF2 파일을 권장해요.</p>
      <label class="check-row"><input type="checkbox" name="license_ok" value="1" required> <span>이 글씨체를 웹사이트에 올려 써도 되는 권리(라이선스)가 있어요.<small>무료 글꼴도 웹 폰트 사용이나 상업적 사용을 막아 둔 경우가 있어요. 글씨체를 만든 곳의 사용 범위를 먼저 확인해 주세요. 올린 글씨체의 사용 책임은 사이트 운영자에게 있어요.</small></span></label>
      <button type="submit" class="btn btn-primary btn-block">글씨체 올리기</button>
    </form>
  </aside>
</div>
