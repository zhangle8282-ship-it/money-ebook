<?php /** 그린청소 관리자 › 홈페이지 관리(연락처·카카오톡·사업자 정보·알림). 변수: $values, $errors */
$v = function ($key) use ($values) {
    return e(isset($values[$key]) && is_string($values[$key]) ? $values[$key] : '');
};
$text = function ($key, $label, $opts = array()) use ($v) {
    return '<div class="field"><label for="f-' . $key . '">' . e($label) . '</label>'
        . '<input id="f-' . $key . '" name="' . $key . '" type="' . ($opts['type'] ?? 'text') . '" value="' . $v($key) . '"'
        . (isset($opts['placeholder']) ? ' placeholder="' . e($opts['placeholder']) . '"' : '') . (!empty($opts['required']) ? ' required' : '') . '>'
        . (isset($opts['help']) ? '<p class="field-help">' . $opts['help'] . '</p>' : '') . '</div>';
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>홈페이지 관리</h1>
    <p class="muted">전화번호, 카카오톡 상담 주소, 바닥글 사업자 정보를 바꿔요. 저장하면 홈페이지에 바로 반영돼요.</p>
  </div>
  <button type="submit" form="site-form" class="btn btn-primary">저장하기</button>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="/admin/site" id="site-form" class="settings">
  <?= csrf_field() ?>
  <section class="card stack-lg" aria-labelledby="s-contact">
    <h2 id="s-contact">연락처 · 상담</h2>
    <div class="grid-2">
      <?= $text('gc_name', '업체 이름', array('required' => true)) ?>
      <?= $text('gc_phone', '대표 전화번호', array('required' => true, 'type' => 'tel', 'help' => '머리말·문의 띠·바닥글·모바일 전화 단추에 쓰여요.')) ?>
    </div>
    <?= $text('gc_kakao_url', '카카오톡 채널 상담 주소', array('placeholder' => 'https://pf.kakao.com/_xxxxxx/chat', 'help' => '카카오톡 채널 관리자센터 › 채널 홍보 › 채널 URL 끝에 <b>/chat</b>을 붙여 넣으세요. 비워 두면 카카오톡 단추가 숨겨져요.')) ?>
    <div class="grid-2">
      <?= $text('gc_tagline', '첫 화면 윗줄 문구', array('help' => '예: 음성 · 진천 · 충북혁신도시 정기청소 전문')) ?>
      <?= $text('gc_area', '서비스 지역 (바닥글)') ?>
    </div>
  </section>

  <section class="card stack-lg" aria-labelledby="s-biz">
    <div class="card-intro"><h2 id="s-biz">사업자 정보</h2><p class="muted">홈페이지 바닥글에 보여요. 빈칸은 빠져요.</p></div>
    <div class="grid-2">
      <?= $text('gc_owner', '대표') ?>
      <?= $text('gc_biz_number', '사업자등록번호', array('placeholder' => '000-00-00000')) ?>
      <?= $text('gc_biz_type', '업태') ?>
      <?= $text('gc_biz_item', '종목') ?>
      <?= $text('gc_email', '이메일', array('type' => 'email')) ?>
    </div>
    <?= $text('gc_address', '사업장 주소') ?>
  </section>

  <section class="card stack-lg" id="notify" aria-labelledby="s-notify">
    <div class="card-intro"><h2 id="s-notify">새 문의 알림</h2><p class="muted">견적 문의가 들어오면 이 주소로 메일을 보내 드려요. 처음 몇 통은 스팸함에 들어갈 수 있으니 확인해 주세요.</p></div>
    <?= $text('gc_notify_email', '알림 받을 이메일', array('type' => 'email', 'placeholder' => gc('email') !== '' ? gc('email') : '예: name@naver.com')) ?>
  </section>
</form>
