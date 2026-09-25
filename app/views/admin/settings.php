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

  <section class="card stack-lg" id="reading" aria-labelledby="set-reading">
    <div class="card-intro">
      <h2 id="set-reading">읽기 방식</h2>
      <p class="muted">구매자는 사이트 뷰어에서 PC·모바일·태블릿에 맞는 글자 크기로 읽어요. 읽던 위치는 계정에 저장돼 다른 기기에서도 이어져요.</p>
    </div>
    <label class="check-row">
      <input type="checkbox" name="allow_download" value="1"<?= ($values['allow_download'] ?? '0') === '1' ? ' checked' : '' ?>>
      <span>구매자가 EPUB·PDF 파일을 내려받을 수도 있게 하기<small>끄면 사이트 뷰어로만 읽을 수 있어요. (PDF는 뷰어가 파일을 불러와 보여 주므로 복사를 완전히 막지는 못해요)</small></span>
    </label>
  </section>

  <section class="card stack-lg" id="my-market" aria-labelledby="set-market">
    <div class="card-intro">
      <h2 id="set-market">나의 마켓</h2>
      <p class="muted">솔루션을 신청하는 고객에게 서버호스팅·도메인을 카페24에서 준비하도록 안내해요.</p>
    </div>
    <div class="grid-2">
      <?= $text('s-cafe24-code', '카페24 제휴코드 또는 제휴 링크', 'cafe24_code', array('help' => '제휴 링크(https://…)를 넣으면 고객 화면에는 hosting.cafe24.com 처럼 주소만 보이고, 누르면 제휴 링크로 가요. 코드만 넣으면 복사 버튼과 함께 보여요.')) ?>
      <?= $text('s-cafe24', '카페24 링크', 'cafe24_url', array('placeholder' => 'https://hosting.cafe24.com/', 'help' => '‘카페24에서 준비하기’ 버튼이 여는 주소예요. 왼쪽에 제휴 링크를 넣었다면 그 링크가 먼저 쓰여요.')) ?>
    </div>
  </section>

  <section class="card stack-lg" id="openmarket" aria-labelledby="set-open">
    <div class="card-intro">
      <h2 id="set-open">오픈마켓(판매자 입점)</h2>
      <p class="muted">켜면 회원이 ‘나의 마켓 › 내 전자책 판매’에서 자기 전자책을 올릴 수 있어요. 올린 책은 전자책 관리에서 승인해야 판매돼요.</p>
    </div>
    <label class="check-row">
      <input type="checkbox" name="seller_enabled" value="1"<?= ($values['seller_enabled'] ?? '0') === '1' ? ' checked' : '' ?>>
      <span>회원이 자기 전자책을 올려 판매할 수 있게 하기</span>
    </label>
    <div class="inline-field">
      <label for="s-commission">판매 수수료</label>
      <input id="s-commission" name="seller_commission" type="number" min="0" max="90" class="input-short" value="<?= $v('seller_commission') ?>">
      <span>% (판매가에서 떼고 나머지를 판매자에게 정산)</span>
    </div>
    <p class="field-help">수수료를 바꾸면 그 뒤에 들어온 주문부터 적용돼요. 이미 들어온 주문은 그때의 수수료로 정산해요.</p>
  </section>

  <section class="card stack-lg" id="biz" aria-labelledby="set-biz">
    <div class="card-intro">
      <h2 id="set-biz">사업자 정보</h2>
      <p class="muted">온라인으로 판매할 때 화면 하단(푸터)에 표시해야 하는 정보예요(전자상거래법). 적은 항목만 보이고, 빈칸은 빠져요.</p>
    </div>
    <div class="grid-2">
      <?= $text('s-biz-name', '상호', 'biz_name') ?>
      <?= $text('s-biz-owner', '대표자', 'biz_owner') ?>
      <?= $text('s-biz-number', '사업자등록번호', 'biz_number', array('placeholder' => '000-00-00000')) ?>
      <?= $text('s-biz-mail', '통신판매업 신고번호', 'biz_mail_order') ?>
      <?= $text('s-biz-phone', '고객센터 전화', 'biz_phone') ?>
      <?= $text('s-biz-email', '고객센터 이메일', 'biz_email') ?>
    </div>
    <?= $text('s-biz-address', '사업장 주소', 'biz_address', array('placeholder' => '예: 경기도 화성시 동탄구 동탄감배산로 143, 202동 1901호')) ?>
<?php
    $footerDesign = design();
    $footerColors = area_colors($footerDesign['design_footer_bg']);
    $fpStyle = '--fp-bg:' . $footerDesign['design_footer_bg'] . ';--fp-fg:' . $footerColors['fg'] . ';--fp-sub:' . $footerColors['sub'] . ';--fp-line:' . $footerColors['line']
        . ';--fp-font:' . font_stack($footerDesign['design_footer_font']) . ';--fp-logo-font:' . font_stack($footerDesign['design_logo_font']);
?>
    <div class="field">
      <span class="field-label-strong">스토어 하단(푸터) 미리보기</span>
      <link rel="stylesheet" href="<?= e(design_fonts_url()) ?>">
      <style><?= font_face_css() ?></style>
      <div class="footer-preview" id="footer-preview" style="<?= e($fpStyle) ?>">
        <div class="fp-top">
          <strong class="fp-name" data-fp-name><?= $v('store_name') ?></strong>
          <span class="fp-links"><span>이용약관</span><b>개인정보처리방침</b><span>관리자</span></span>
        </div>
        <ul class="fp-biz" data-fp-biz></ul>
        <p class="fp-copy">© <?= date('Y') ?> <span data-fp-name><?= $v('store_name') ?></span>. All rights reserved.</p>
      </div>
      <p class="field-help">입력하는 대로 바로 바뀌어요. 색·글씨체는 <a href="/admin/design">디자인</a>에서 정해요.</p>
    </div>
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

<form method="post" action="/admin/update" enctype="multipart/form-data" class="card stack-lg settings" id="update" aria-labelledby="set-update"
  data-confirm="프로그램을 새 버전으로 바꿀까요? 책·주문·회원 데이터는 그대로 유지돼요.">
  <?= csrf_field() ?>
  <div class="card-intro">
    <h2 id="set-update">프로그램 업데이트</h2>
    <p class="muted">지금 버전 <?= e(app_version()) ?> · 새 버전 설치 파일(zip)을 올리면 자동으로 바뀌어요. 책·주문·회원 데이터와 표지는 그대로예요.</p>
  </div>
  <div class="field">
    <label for="pkg">설치 파일(zip)</label>
    <input id="pkg" name="package" type="file" accept=".zip,application/zip" required class="file-input">
    <span class="field-help">내려받은 ebook-store-버전.zip 파일을 압축을 풀지 않고 그대로 올려요. 이전 프로그램은 서버의 app.bak 폴더에 보관돼요.</span>
  </div>
  <div><button type="submit" class="btn btn-outline">업로드해서 업데이트</button></div>
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
