<?php
/** 나의 마켓: 독립 마켓 운영 안내, 요금, 신청, 내 신청 내역. */
$store = setting('store_name');
?>
<section class="market-hero wrap">
  <p class="eyebrow">나의 마켓</p>
  <h1 class="hero-title">전자책 마켓 솔루션으로<br>나만의 사이트를 운영하세요</h1>
  <p class="hero-text">지금 보고 계신 <?= e(josa($store, "과", "와")) ?> 같은 전자책 판매 사이트를 <strong>솔루션으로 판매</strong>해요. 한 번 결제하면 내 서버호스팅과 도메인에 설치해 <strong>독립된 사이트</strong>로 운영할 수 있어요. 서버호스팅과 도메인은 별도로 준비해요.</p>
  <?= view('_market_tabs', array('tab' => 'apply')) ?>
</section>

<section class="wrap feature-grid" aria-label="나의 마켓에 들어 있는 것">
  <div class="feature">
    <h2>독립된 내 사이트</h2>
    <p>내 서버호스팅과 도메인(예: mybook.com)에 설치해, 스토어 이름·책·회원·주문을 따로 운영해요.</p>
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
    <h2 id="price-title">솔루션 가격</h2>
    <span>한 번 결제 · 서버호스팅·도메인 별도</span>
  </div>
  <div class="product-grid">
<?php foreach (MARKET_PRODUCTS as $key => $p): ?>
    <div class="product-card<?= $key === 'affiliate' ? ' is-pick' : '' ?>">
      <h3 class="product-name"><?= e($p['name']) ?></h3>
      <p class="product-summary"><?= e($p['summary']) ?></p>
      <div class="product-price"><strong><?= won($p['price']) ?></strong><span>+ 서버호스팅 · 도메인 별도</span></div>
      <ul class="product-features">
<?php foreach ($p['features'] as $f): ?>        <li><?= e($f) ?></li>
<?php endforeach; ?>
      </ul>
      <a class="btn <?= $key === 'affiliate' ? 'btn-primary' : 'btn-outline' ?> btn-block" href="/market?product=<?= e($key) ?>#apply">이 솔루션 신청</a>
    </div>
<?php endforeach; ?>
  </div>
  <div class="hosting-note">
    <div>
      <strong>서버호스팅과 도메인이 필요해요</strong>
      <p>솔루션은 내 서버호스팅에 설치돼요. 아직 없다면 카페24에서 웹호스팅(PHP)과 도메인을 준비해 주세요.</p>
<?php if (setting('cafe24_code') !== ''): ?>      <p class="cafe24-code">가입·신청할 때 제휴코드에 <strong id="cafe24-code"><?= e(setting('cafe24_code')) ?></strong>을(를) 넣어 주세요. <button type="button" class="btn-chip" data-copy="#cafe24-code">복사</button></p>
<?php endif; ?>
    </div>
    <a class="btn btn-outline" href="<?= e(cafe24_url()) ?>" target="_blank" rel="noopener sponsored">카페24에서 준비하기 ↗</a>
  </div>
</section>

<section class="wrap">
  <div class="free-box">
    <div>
      <h2>무료로 운영해 보고 싶다면?</h2>
      <p>추천인이 되어 내 홍보 링크로 이 사이트를 알려 보세요. 그 링크로 들어와 신청한 솔루션 결제금액의 <strong><?= REFERRAL_RATE ?>%</strong>가 수익으로 쌓이고, 쌓인 수익은 출금 신청할 수 있어요. 추천인 코드는 신청 후 관리자가 승인하면 나와요.</p>
    </div>
    <a class="btn btn-primary" href="/market/referral">추천인 신청하기</a>
  </div>
</section>

<section id="apply" class="page wrap-narrow market-apply" aria-labelledby="apply-title">
  <h2 id="apply-title" class="page-title">솔루션 신청</h2>
<?php if (!$user): ?>
  <div class="info-box">
    <p>솔루션 신청은 회원만 할 수 있어요. 로그인하거나 가입한 뒤 신청해 주세요.</p>
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
      <legend class="field-legend">솔루션 <span class="req" aria-hidden="true">*</span></legend>
<?php foreach (MARKET_PRODUCTS as $key => $p): ?>
      <label class="plan-choice">
        <input type="radio" name="product" value="<?= e($key) ?>"<?= $form['product'] === $key ? ' checked' : '' ?> required>
        <span class="plan-choice-name"><?= e($p['name']) ?></span>
        <span class="plan-choice-price"><?= won($p['price']) ?><small>서버호스팅·도메인 별도</small></span>
      </label>
<?php endforeach; ?>
    </fieldset>
    <div class="field">
      <label for="domain">희망 도메인</label>
      <input id="domain" name="domain" type="text" inputmode="url" autocomplete="off" placeholder="예: mybook.com" value="<?= e($form['domain']) ?>">
      <span class="field-help">이미 가지고 있거나 쓰고 싶은 주소를 적어 주세요. 도메인은 별도로 준비해요.</span>
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
    <fieldset class="hosting-box">
      <legend class="field-legend">서버호스팅 정보 <span class="muted">(선택 · 설치에만 써요)</span></legend>
      <div class="field">
        <label for="hosting_url">서버호스팅 주소</label>
        <input id="hosting_url" name="hosting_url" type="text" autocomplete="off" placeholder="예: FTP 주소 또는 호스팅 관리 주소" value="<?= e($form['hosting_url']) ?>">
      </div>
      <div class="grid-2-public">
        <div class="field">
          <label for="hosting_id">아이디</label>
          <input id="hosting_id" name="hosting_id" type="text" autocomplete="off" value="<?= e($form['hosting_id']) ?>">
        </div>
        <div class="field">
          <label for="hosting_pw">비밀번호</label>
          <input id="hosting_pw" name="hosting_pw" type="password" autocomplete="new-password">
        </div>
      </div>
      <p class="field-help">비밀번호는 암호화해서 보관하고, 설치가 끝나면 지워요. 설치 뒤에는 호스팅 비밀번호를 바꿔 주세요. 아직 호스팅이 없다면 비워 두고, 나중에 아래 ‘내 신청 내역’에서 넣어도 돼요. <a href="<?= e(cafe24_url()) ?>" target="_blank" rel="noopener sponsored">카페24에서 호스팅 준비하기 ↗</a><?php if (setting('cafe24_code') !== ''): ?> (제휴코드 <?= e(setting('cafe24_code')) ?>)<?php endif; ?></p>
    </fieldset>
    <div class="pay-method">
      <strong>무통장 입금</strong>
<?php if (bank_ready()): ?>
      <span><?= e(setting('bank_name')) ?> <?= e(setting('bank_account')) ?> · 예금주 <?= e(setting('bank_holder')) ?></span>
      <span class="muted">입금이 확인되면 운영자가 연락드려 내 서버호스팅에 솔루션 설치를 도와드려요.</span>
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
        <strong><?= e(market_product_name($a)) ?></strong>
        <span class="muted"><?= e($a['app_no']) ?> · <?= e(fmt_date($a['created_at'])) ?></span>
      </div>
      <dl class="market-app-info">
        <div><dt>결제금액</dt><dd><?= won($a['total']) ?><?= $a['discount'] ? ' (월 ' . won($a['monthly_price']) . ', ' . (int) $a['discount'] . '% 할인)' : '' ?></dd></div>
        <div><dt>희망 도메인</dt><dd><?= $a['domain'] !== '' ? e($a['domain']) : '아직 없음' ?></dd></div>
        <div><dt>서버호스팅</dt><dd><?= has_hosting_info($a) ? e($a['hosting_url'] !== '' ? $a['hosting_url'] : '입력됨') . ($a['hosting_id'] !== '' ? ' · ' . e($a['hosting_id']) : '') . ((string) $a['hosting_pw'] !== '' ? ' · 비밀번호 저장됨' : '') : '아직 없음' ?></dd></div>
<?php if ($a['market_name'] !== ''): ?>        <div><dt>마켓 이름</dt><dd><?= e($a['market_name']) ?></dd></div>
<?php endif; ?>
<?php if ($a['referral_code'] !== ''): ?>        <div><dt>추천인 코드</dt><dd><?= e($a['referral_code']) ?></dd></div>
<?php endif; ?>
<?php if ($a['status'] === 'paid' && $a['starts_at']): ?>        <div><dt>운영 기간</dt><dd><strong><?= e(fmt_date($a['starts_at'])) ?> ~ <?= e(fmt_date($a['ends_at'])) ?></strong></dd></div>
<?php elseif ($a['status'] === 'paid'): ?>        <div><dt>결제일</dt><dd><?= e(fmt_date($a['paid_at'])) ?></dd></div>
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
<?php if ($a['status'] !== 'cancelled'): ?>
      <details class="hosting-edit">
        <summary><?= has_hosting_info($a) ? '서버호스팅 정보 고치기' : '서버호스팅 정보 넣기' ?></summary>
        <form method="post" action="/market/<?= e($a['app_no']) ?>/hosting" class="hosting-edit-form">
          <?= csrf_field() ?>
          <div class="field"><label for="hu-<?= (int) $a['id'] ?>">서버호스팅 주소</label><input id="hu-<?= (int) $a['id'] ?>" name="hosting_url" type="text" autocomplete="off" value="<?= e($a['hosting_url']) ?>"></div>
          <div class="grid-2-public">
            <div class="field"><label for="hi-<?= (int) $a['id'] ?>">아이디</label><input id="hi-<?= (int) $a['id'] ?>" name="hosting_id" type="text" autocomplete="off" value="<?= e($a['hosting_id']) ?>"></div>
            <div class="field"><label for="hp-<?= (int) $a['id'] ?>">비밀번호</label><input id="hp-<?= (int) $a['id'] ?>" name="hosting_pw" type="password" autocomplete="new-password" placeholder="<?= (string) $a['hosting_pw'] !== '' ? '바꿀 때만 입력' : '' ?>"></div>
          </div>
          <button type="submit" class="btn btn-outline btn-sm">저장</button>
        </form>
      </details>
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
