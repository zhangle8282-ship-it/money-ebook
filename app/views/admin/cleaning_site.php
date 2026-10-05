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
    <?= $text('gc_channeltalk_key', '채널톡 플러그인 키', array('placeholder' => '예: 1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', 'help' => '채널톡 관리자 화면의 설정에서 <b>플러그인 키(Plugin Key)</b>를 복사해 넣으세요. 넣으면 홈페이지 오른쪽 아래에 채팅 상담 버튼이 떠 있어요. 비워 두면 숨겨져요.')) ?>
    <?= $text('gc_kakao_url', '카카오톡 채널 상담 주소', array('placeholder' => 'https://pf.kakao.com/_xxxxxx/chat', 'help' => '카카오톡 채널 관리자센터 › 채널 홍보 › 채널 URL 끝에 <b>/chat</b>을 붙여 넣으세요. 비워 두면 카카오톡 단추가 숨겨져요.')) ?>
    <div class="grid-2">
      <?= $text('gc_tagline', '첫 화면 윗줄 문구', array('help' => '예: 음성 · 진천 · 충북혁신도시 정기청소 전문')) ?>
      <?= $text('gc_area', '서비스 지역 (바닥글)') ?>
    </div>
  </section>

  <section class="card stack-lg" id="seo" aria-labelledby="s-seo">
    <div class="card-intro"><h2 id="s-seo">검색 노출 (SEO)</h2><p class="muted">네이버 · 구글 검색 결과에 보이는 첫 화면 제목과 설명이에요. 사람들이 검색할 말을 앞쪽에 넣으세요.</p></div>
    <?= $text('gc_seo_title', '검색 제목', array('help' => '예: 충북음성청소업체 | 그린청소 (30자 안팎이 좋아요)')) ?>
    <div class="field"><label for="f-gc_seo_desc">검색 설명</label><textarea id="f-gc_seo_desc" name="gc_seo_desc" rows="3" maxlength="200"><?= $v('gc_seo_desc') ?></textarea><p class="field-help">검색 결과 제목 아래에 보이는 글이에요. 80~160자가 좋아요.</p></div>
    <?= $text('gc_seo_keywords', '키워드 (쉼표로)', array('help' => '예: 충북음성청소업체, 금왕사무실정기청소, 음성공장청소')) ?>
    <p class="field-help">네이버 · 구글 사이트 확인 코드와 사이트맵 · RSS 제출은 <a href="/admin/search">검색 등록</a> 메뉴에서 해요.</p>
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
    <?= $text('gc_notify_email', '알림 받을 이메일', array('type' => 'email', 'placeholder' => gc('email') !== '' ? gc('email') : '예: name@naver.com', 'help' => '비워 두면 메일은 보내지 않고, 관리자 › 견적 문의에서만 볼 수 있어요.')) ?>
    <div><button type="submit" form="test-mail" class="btn btn-outline"<?= gc('notify_email') === '' ? ' disabled' : '' ?>>시험 메일 보내기</button></div>
  </section>
</form>
<form method="post" action="/admin/site" id="test-mail"><?= csrf_field() ?><input type="hidden" name="action" value="test_mail"></form>
