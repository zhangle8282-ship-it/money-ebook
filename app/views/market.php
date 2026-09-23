<?php
/** 나의 마켓: 독립 마켓 운영 안내, 요금, 신청, 내 신청 내역. */
$store = setting('store_name');
?>
<section class="market-hero wrap">
  <p class="eyebrow">나의 마켓</p>
  <h1 class="hero-title">나만의 전자책 마켓을<br>내 도메인으로 운영하세요</h1>
  <p class="hero-text">지금 보고 계신 <?= e(josa($store, "과", "와")) ?> 같은 전자책 판매 사이트를, 내 도메인을 가진 <strong>독립된 사이트</strong>로 열어 드려요. 책·회원·주문·매출이 모두 내 마켓에서 따로 관리돼요.</p>
  <?= view('_market_tabs', array('tab' => 'apply')) ?>
</section>

<section class="wrap feature-grid" aria-label="나의 마켓에 들어 있는 것">
  <div class="feature">
    <h2>독립된 내 사이트</h2>
    <p>내 도메인(예: mybook.com)으로 열리고, 스토어 이름·책·회원·주문을 다른 마켓과 섞이지 않게 따로 운영해요.</p>
  </div>
  <div class="feature">
    <h2>전자책 판매 기능 그대로</h2>
    <p>표지와 EPUB·PDF 등록, 무료 미리보기, 무통장 입금 주문, 리뷰까지 바로 쓸 수 있어요.</p>
  </div>
  <div class="feature">
    <h2>기기에 맞춘 뷰어</h2>
    <p>구매자는 PC·태블릿·모바일에서 글자 크기가 알맞게 맞춰진 뷰어로 읽고, 다른 기기에서도 이어 읽어요.</p>
  </div>
  <div class="feature">
    <h2>내 관리자 화면</h2>
    <p>책 등록, 입금 확인, 리뷰 관리, 스토어 설정을 내 마켓의 관리자 화면에서 직접 해요.</p>
  </div>
</section>

<section class="wrap" aria-labelledby="price-title">
  <div class="section-head">
    <h2 id="price-title">운영 기간과 요금</h2>
    <span>월 <?= won(MARKET_MONTHLY) ?> · 오래 할수록 할인돼요</span>
  </div>
  <div class="plan-grid">
<?php foreach ($plans as $p): ?>
    <div class="plan-card<?= $p['months'] === 12 ? ' is-pick' : '' ?>">
      <div class="plan-top">
        <span class="plan-months"><?= $p['months'] ?>개월</span>
<?php if ($p['discount']): ?>        <span class="plan-off"><?= $p['discount'] ?>% 할인</span>
<?php endif; ?>
      </div>
      <div class="plan-monthly">월 <strong><?= won($p['monthly']) ?></strong><?php if ($p['discount']): ?> <s><?= won(MARKET_MONTHLY) ?></s><?php endif; ?></div>
      <div class="plan-total">결제금액 <?= won($p['total']) ?></div>
      <div class="plan-save"><?= $p['saving'] ? won($p['saving']) . ' 아껴요' : '할인 없음' ?></div>
      <a class="btn btn-outline btn-block btn-sm" href="/market?months=<?= $p['months'] ?>#apply">이 기간으로 신청</a>
    </div>
<?php endforeach; ?>
  </div>
</section>

<section class="wrap">
  <div class="free-box">
    <div>
      <h2>무료로 운영해 보고 싶다면?</h2>
      <p>추천인이 되어 내 홍보 링크로 이 사이트를 알려 보세요. 그 링크로 들어와 신청한 마켓 결제금액의 <strong><?= REFERRAL_RATE ?>%</strong>가 수익으로 쌓이고, 쌓인 수익은 출금 신청할 수 있어요. 추천인 코드는 신청 후 관리자가 승인하면 나와요.</p>
    </div>
    <a class="btn btn-primary" href="/market/referral">추천인 신청하기</a>
  </div>
</section>

<section id="apply" class="page wrap-narrow market-apply" aria-labelledby="apply-title">
  <h2 id="apply-title" class="page-title">마켓 운영 신청</h2>
<?php if (!$user): ?>
  <div class="info-box">
    <p>마켓 운영 신청은 회원만 할 수 있어요. 로그인하거나 가입한 뒤 신청해 주세요.</p>
    <div class="page-actions start">
      <a class="btn btn-primary" href="/login?next=<?= e(rawurlencode('/market#apply')) ?>">로그인하고 신청하기</a>
      <a class="btn btn-outline" href="/signup?next=<?= e(rawurlencode('/market#apply')) ?>">회원가입</a>
    </div>
  </div>
<?php else: ?>
<?php if ($errors): ?>
  <div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
  <form method="post" action="/market#apply" class="checkout-form">
    <?= csrf_field() ?>
    <fieldset class="plan-choices">
      <legend class="field-legend">운영 기간 <span class="req" aria-hidden="true">*</span></legend>
<?php foreach ($plans as $p): ?>
      <label class="plan-choice">
        <input type="radio" name="months" value="<?= $p['months'] ?>"<?= $form['months'] === $p['months'] ? ' checked' : '' ?> required>
        <span class="plan-choice-name"><?= $p['months'] ?>개월<?php if ($p['discount']): ?> <em><?= $p['discount'] ?>% 할인</em><?php endif; ?></span>
        <span class="plan-choice-price"><?= won($p['total']) ?><small>월 <?= won($p['monthly']) ?></small></span>
      </label>
<?php endforeach; ?>
    </fieldset>
    <div class="field">
      <label for="domain">희망 도메인</label>
      <input id="domain" name="domain" type="text" inputmode="url" autocomplete="off" placeholder="예: mybook.com" value="<?= e($form['domain']) ?>">
      <span class="field-help">쓰고 싶은 주소를 적어 주세요. 아직 없다면 비워 두고, 입금 확인 후 함께 정해도 돼요.</span>
    </div>
    <div class="grid-2-public">
      <div class="field">
        <label for="market_name">마켓 이름</label>
        <input id="market_name" name="market_name" type="text" maxlength="100" placeholder="예: 책방 한 권" value="<?= e($form['market_name']) ?>">
      </div>
      <div class="field">
        <label for="phone">연락처</label>
        <input id="phone" name="phone" type="tel" maxlength="40" placeholder="010-0000-0000" autocomplete="tel" value="<?= e($form['phone']) ?>">
      </div>
      <div class="field">
        <label for="depositor">입금자명 <span class="req" aria-hidden="true">*</span></label>
        <input id="depositor" name="depositor" type="text" maxlength="30" required value="<?= e($form['depositor']) ?>">
      </div>
    </div>
    <div class="pay-method">
      <strong>무통장 입금</strong>
<?php if (bank_ready()): ?>
      <span><?= e(setting('bank_name')) ?> <?= e(setting('bank_account')) ?> · 예금주 <?= e(setting('bank_holder')) ?></span>
      <span class="muted">입금이 확인되면 그날부터 운영 기간이 시작되고, 도메인 연결과 마켓 설치를 도와드려요.</span>
<?php else: ?>
      <span class="line-warn">입금 계좌가 아직 등록되지 않아 지금은 신청할 수 없어요.</span>
<?php endif; ?>
    </div>
    <label class="check">
      <input type="checkbox" name="agree" value="1" required>
      <span>신청 내용을 확인했어요. (<a href="/terms" target="_blank" rel="noopener">이용약관</a>)</span>
    </label>
    <button type="submit" class="btn btn-primary btn-lg btn-block"<?= bank_ready() ? '' : ' disabled' ?>>신청하기</button>
  </form>
<?php endif; ?>
</section>

<?php if ($apps): ?>
<section id="my-apps" class="page wrap-narrow" aria-labelledby="apps-title">
  <h2 id="apps-title" class="block-title">내 신청 내역</h2>
  <div class="market-apps">
<?php foreach ($apps as $a): ?>
    <article class="market-app">
      <div class="market-app-head">
        <span class="status market-<?= e($a['status']) ?>"><?= e(MARKET_STATUS[$a['status']]) ?></span>
        <strong>마켓 운영 <?= (int) $a['months'] ?>개월</strong>
        <span class="muted"><?= e($a['app_no']) ?> · <?= e(fmt_date($a['created_at'])) ?></span>
      </div>
      <dl class="market-app-info">
        <div><dt>결제금액</dt><dd><?= won($a['total']) ?><?= $a['discount'] ? ' (월 ' . won($a['monthly_price']) . ', ' . (int) $a['discount'] . '% 할인)' : '' ?></dd></div>
        <div><dt>희망 도메인</dt><dd><?= $a['domain'] !== '' ? e($a['domain']) : '상담 후 결정' ?></dd></div>
<?php if ($a['market_name'] !== ''): ?>        <div><dt>마켓 이름</dt><dd><?= e($a['market_name']) ?></dd></div>
<?php endif; ?>
<?php if ($a['referral_code'] !== ''): ?>        <div><dt>추천인 코드</dt><dd><?= e($a['referral_code']) ?></dd></div>
<?php endif; ?>
<?php if ($a['status'] === 'paid'): ?>        <div><dt>운영 기간</dt><dd><strong><?= e(fmt_date($a['starts_at'])) ?> ~ <?= e(fmt_date($a['ends_at'])) ?></strong></dd></div>
<?php endif; ?>
      </dl>
<?php if ($a['status'] === 'pending'): ?>
      <div class="bank-box compact">
        <dl>
          <div><dt>입금 계좌</dt><dd><?= e($a['bank_name']) ?> <span id="acc-<?= (int) $a['id'] ?>"><?= e($a['bank_account']) ?></span> <button type="button" class="btn-chip" data-copy="#acc-<?= (int) $a['id'] ?>">복사</button></dd></div>
          <div><dt>예금주</dt><dd><?= e($a['bank_holder']) ?></dd></div>
          <div><dt>입금액</dt><dd><strong><?= won($a['total']) ?></strong></dd></div>
          <div><dt>입금자명</dt><dd><?= e($a['depositor']) ?></dd></div>
        </dl>
      </div>
<?php endif; ?>
<?php if (trim((string) $a['admin_note']) !== ''): ?>
      <div class="admin-note"><strong>운영자 안내</strong><?= paragraphs($a['admin_note']) ?></div>
<?php endif; ?>
<?php if ($a['status'] === 'pending'): ?>
      <form method="post" action="/market/<?= e($a['app_no']) ?>/cancel" data-confirm="이 신청을 취소할까요?" class="market-app-actions">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-text">신청 취소</button>
      </form>
<?php endif; ?>
    </article>
<?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
