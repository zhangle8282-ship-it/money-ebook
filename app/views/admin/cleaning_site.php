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
    <?= $text('gc_seo_title', '검색 제목', array('help' => '예: 충북음성청소업체 (30자 안팎이 좋아요)')) ?>
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

<?php $tgLast = telegram_last(); $tgChats = telegram_chats(); $tgConnected = telegram_token_ok(gc('tg_token')) && $tgChats; $tgCand = telegram_candidates(); $tgBotLink = gc('tg_bot') !== '' ? 'https://t.me/' . gc('tg_bot') : ''; ?>
<section class="card stack-lg" id="telegram" aria-labelledby="s-tg">
  <div class="card-head submit-head">
    <div class="card-intro"><h2 id="s-tg">텔레그램 알림</h2><p class="muted">새 견적 문의가 들어오면 텔레그램으로도 바로 알려 드려요. 메일 알림은 그대로 함께 가요.</p></div>
<?php if ($tgConnected): ?>    <?= gc('tg_on') === '1' ? '<span class="status status-paid">켜짐</span>' : '<span class="status status-cancelled">꺼짐</span>' ?>
<?php else: ?>    <span class="status status-cancelled">연결 안 됨</span>
<?php endif; ?>
  </div>
<?php if ($tgConnected): ?>
  <div class="tg-block">
    <h3 class="tg-sub">받는 곳 <span class="sub"><?= count($tgChats) ?>곳 — 새 견적 문의가 오면 모두에게 알려요</span></h3>
    <ul class="tg-chats">
<?php foreach ($tgChats as $c): ?>
      <li>
        <span class="tg-chat-name"><?= e($c['title']) ?></span>
        <span class="sub"><?= ($c['type'] ?? 'private') === 'private' ? '개인' : '단체방' ?></span>
        <form method="post" action="/admin/site" data-confirm="‘<?= e($c['title']) ?>’은(는) 이제 알림을 받지 않아요. 뺄까요?"><?= csrf_field() ?><input type="hidden" name="action" value="tg_remove"><input type="hidden" name="chat_id" value="<?= e($c['id']) ?>"><button type="submit" class="btn btn-ghost btn-sm">빼기</button></form>
      </li>
<?php endforeach; ?>
    </ul>
  </div>
  <div class="tg-block">
    <h3 class="tg-sub">다른 사람도 받게 하기</h3>
    <ol class="engine-steps">
      <li>받을 분에게 봇 주소를 보내 주세요. 그분이 열어서 <b>시작</b>을 누르면 돼요. <?php if ($tgBotLink !== ''): ?><span class="mono"><?= e($tgBotLink) ?></span> <button type="button" class="btn btn-outline btn-sm" data-copy-text="<?= e($tgBotLink) ?>">봇 주소 복사</button><?php endif; ?></li>
      <li>여럿이 한 방에서 받으려면 단체방에 봇(@<?= e(gc('tg_bot')) ?>)을 초대하고 단체방에 <code>/start</code>를 보내세요.</li>
      <li>아래 <b>받는 사람 찾기</b>를 누르고, 나온 이름 옆 <b>추가</b>를 눌러요.</li>
    </ol>
    <form method="post" action="/admin/site"><?= csrf_field() ?><input type="hidden" name="action" value="tg_find"><button type="submit" class="btn btn-outline btn-sm">받는 사람 찾기</button></form>
<?php if ($tgCand['list']): ?>
    <ul class="tg-chats tg-candidates">
<?php foreach ($tgCand['list'] as $c): ?>
      <li>
        <span class="tg-chat-name"><?= e($c['title']) ?></span>
        <span class="sub"><?= ($c['type'] ?? 'private') === 'private' ? '개인' : '단체방' ?> · 봇에게 말을 건 곳</span>
        <form method="post" action="/admin/site"><?= csrf_field() ?><input type="hidden" name="action" value="tg_add"><input type="hidden" name="chat_id" value="<?= e($c['id']) ?>"><button type="submit" class="btn btn-primary btn-sm">추가</button></form>
      </li>
<?php endforeach; ?>
    </ul>
<?php elseif ($tgCand['at']): ?>
    <p class="sub"><?= e(fmt_date($tgCand['at'], 'm.d H:i')) ?>에 찾아봤는데 새로 말을 건 곳이 없었어요.</p>
<?php endif; ?>
  </div>
  <dl class="tg-info">
    <div><dt>보내는 봇</dt><dd><?= gc('tg_bot') !== '' ? '@' . e(gc('tg_bot')) : '-' ?> <span class="sub">토큰 <?= e(telegram_token_masked(gc('tg_token'))) ?></span></dd></div>
<?php if ($tgLast): ?>    <div><dt>마지막 알림</dt><dd><?= e(fmt_date($tgLast['at'], 'm.d H:i')) ?> · <?= e($tgLast['what']) ?> · <?= $tgLast['ok'] ? '<span class="status status-paid">보냄</span>' : '<span class="status status-pending">못 보냄</span> <span class="sub">' . e($tgLast['error']) . '</span>' ?></dd></div>
<?php endif; ?>
  </dl>
  <div class="tg-actions">
    <form method="post" action="/admin/site"><?= csrf_field() ?><input type="hidden" name="action" value="tg_test"><button type="submit" class="btn btn-outline btn-sm">시험 메시지 보내기 (모두)</button></form>
    <form method="post" action="/admin/site"><?= csrf_field() ?><input type="hidden" name="action" value="tg_toggle"><button type="submit" class="btn btn-outline btn-sm"><?= gc('tg_on') === '1' ? '알림 끄기' : '알림 켜기' ?></button></form>
    <form method="post" action="/admin/site" data-confirm="텔레그램 연결을 끊고 토큰과 받는 곳을 모두 지울까요?"><?= csrf_field() ?><input type="hidden" name="action" value="tg_clear"><button type="submit" class="btn btn-ghost btn-sm">연결 끊기</button></form>
  </div>
<?php else: ?>
<?php if ($tgLast && !$tgLast['ok']): ?>  <div class="alert tg-alert" role="alert"><p><b><?= e(fmt_date($tgLast['at'], 'm.d H:i')) ?> <?= e($tgLast['what']) ?> 실패</b> · <?= e($tgLast['error']) ?></p></div>
<?php endif; ?>
  <ol class="engine-steps">
    <li>텔레그램에서 <b>@BotFather</b>를 찾아 <code>/newbot</code>을 보내요. 봇 이름과 아이디(끝이 <code>bot</code>, 예: greenclean_alert_bot)를 정하면 <b>봇 토큰</b>을 줘요. 토큰은 비밀번호처럼 다른 사람에게 알려 주지 마세요.</li>
    <li>방금 만든 봇을 찾아 <b>시작</b>을 누르고 아무 말이나 한 번 보내요. 여러 사람이 함께 받으려면 단체방을 만들어 봇을 초대하고 단체방에 한마디 하세요.</li>
    <li>아래에 토큰을 붙여 넣고 <b>연결 확인</b>을 누르면, 대화방을 찾아 시험 메시지를 보내요.</li>
  </ol>
  <form method="post" action="/admin/site" class="tg-connect">
    <?= csrf_field() ?><input type="hidden" name="action" value="tg_connect">
    <div class="field"><label for="tg-token">봇 토큰</label><input id="tg-token" name="tg_token" type="password" autocomplete="off" spellcheck="false" placeholder="<?= gc('tg_token') !== '' ? '저장된 토큰 ' . e(telegram_token_masked(gc('tg_token'))) . ' (바꿀 때만 넣기)' : '예: 123456789:AAH…' ?>"></div>
    <div><button type="submit" class="btn btn-primary">연결 확인</button></div>
  </form>
<?php endif; ?>
<?php $tgCheck = json_decode(gc('tg_check'), true); ?>
  <div class="tg-check">
    <form method="post" action="/admin/site"><?= csrf_field() ?><input type="hidden" name="action" value="tg_check"><button type="submit" class="btn btn-ghost btn-sm">서버 연결 점검</button></form>
<?php if (is_array($tgCheck) && !empty($tgCheck['lines'])): ?>
    <p class="sub"><?= e(fmt_date($tgCheck['at'], 'm.d H:i')) ?> 점검: <?= e(implode(' · ', $tgCheck['lines'])) ?></p>
<?php else: ?>
    <p class="sub">연결이 안 될 때 눌러 보세요. 이 서버에서 텔레그램까지 연결되는지 확인해요.</p>
<?php endif; ?>
  </div>
</section>
