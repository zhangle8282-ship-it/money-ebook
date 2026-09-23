<?php /** 관리자 · 설정(스토어, 입금 계좌, 사업자 정보, 약관, 비밀번호). */
$v = function ($key) use ($values) {
    return e(isset($values[$key]) && is_string($values[$key]) ? $values[$key] : '');
};
$text = function ($id, $label, $key, $opts = array()) use ($v) {
    $help = isset($opts['help']) ? '<span class="field-help">' . e($opts['help']) . '</span>' : '';
    return '<div class="field"><label for="' . $id . '">' . e($label) . '</label>'
        . '<input id="' . $id . '" name="' . $key . '" type="text" value="' . $v($key) . '"'
        . (isset($opts['placeholder']) ? ' placeholder="' . e($opts['placeholder']) . '"' : '')
        . (!empty($opts['required']) ? ' required' : '') . '>' . $help . '</div>';
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>설정</h1>
  </div>
  <button type="submit" form="settings-form" class="btn btn-primary">저장하기</button>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<form method="post" action="/admin/settings" id="settings-form" class="settings">
  <?= csrf_field() ?>
  <section class="card stack-lg" aria-labelledby="set-store">
    <h2 id="set-store">스토어</h2>
    <div class="grid-2">
      <?= $text('s-name', '스토어 이름', 'store_name', array('required' => true)) ?>
      <?= $text('s-cats', '카테고리', 'categories', array('help' => '쉼표로 구분해요. 홈 화면 분류와 등록 화면 선택지에 쓰여요.')) ?>
    </div>
    <?= $text('s-hero', '홈 제목', 'hero_title') ?>
    <?= $text('s-hero-text', '홈 소개 문구', 'hero_text') ?>
  </section>

  <section class="card stack-lg" id="bank" aria-labelledby="set-bank">
    <div class="card-intro">
      <h2 id="set-bank">무통장 입금 계좌</h2>
      <p class="muted">주문 완료 화면에 안내돼요. 계좌를 바꿔도 이미 접수된 주문의 안내는 그대로예요.</p>
    </div>
    <div class="grid-3">
      <?= $text('s-bank', '은행', 'bank_name', array('placeholder' => '예: 국민은행')) ?>
      <?= $text('s-account', '계좌번호', 'bank_account', array('placeholder' => '000-0000-0000')) ?>
      <?= $text('s-holder', '예금주', 'bank_holder') ?>
    </div>
    <div class="inline-field">
      <label for="s-days">입금 기한</label>
      <span>주문일로부터</span>
      <input id="s-days" name="deposit_days" type="number" min="1" max="14" class="input-short" value="<?= $v('deposit_days') ?>">
      <span>일</span>
    </div>
  </section>

  <section class="card stack-lg" id="biz" aria-labelledby="set-biz">
    <div class="card-intro">
      <h2 id="set-biz">사업자 정보</h2>
      <p class="muted">온라인으로 판매할 때 화면 하단에 표시해야 하는 정보예요(전자상거래법).</p>
    </div>
    <div class="grid-2">
      <?= $text('s-biz-name', '상호', 'biz_name') ?>
      <?= $text('s-biz-owner', '대표자', 'biz_owner') ?>
      <?= $text('s-biz-number', '사업자등록번호', 'biz_number', array('placeholder' => '000-00-00000')) ?>
      <?= $text('s-biz-mail', '통신판매업 신고번호', 'biz_mail_order') ?>
      <?= $text('s-biz-phone', '고객센터 전화', 'biz_phone') ?>
      <?= $text('s-biz-email', '고객센터 이메일', 'biz_email') ?>
    </div>
    <?= $text('s-biz-address', '사업장 주소', 'biz_address') ?>
  </section>

  <section class="card stack-lg" aria-labelledby="set-docs">
    <h2 id="set-docs">약관</h2>
    <div class="field">
      <label for="s-terms">이용약관</label>
      <textarea id="s-terms" name="terms_text" rows="10"><?= $v('terms_text') ?></textarea>
    </div>
    <div class="field">
      <label for="s-privacy">개인정보처리방침</label>
      <textarea id="s-privacy" name="privacy_text" rows="10"><?= $v('privacy_text') ?></textarea>
    </div>
  </section>
</form>

<form method="post" action="/admin/settings" class="card stack-lg settings" aria-labelledby="set-pw">
  <?= csrf_field() ?>
  <input type="hidden" name="form" value="password">
  <h2 id="set-pw">관리자 비밀번호 바꾸기</h2>
  <div class="grid-3">
    <div class="field"><label for="pw-now">지금 비밀번호</label><input id="pw-now" name="current_password" type="password" required autocomplete="current-password"></div>
    <div class="field"><label for="pw-new">새 비밀번호</label><input id="pw-new" name="new_password" type="password" required minlength="10" autocomplete="new-password"></div>
    <div class="field"><label for="pw-new2">새 비밀번호 확인</label><input id="pw-new2" name="new_password2" type="password" required minlength="10" autocomplete="new-password"></div>
  </div>
  <div><button type="submit" class="btn btn-outline">비밀번호 바꾸기</button></div>
</form>
