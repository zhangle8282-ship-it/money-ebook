<?php
/** 나의 마켓 › 추천인 · 수익: 추천인 신청, 코드·홍보 링크, 추천으로 가입된 상품, 출금 계좌, 출금 신청. */
$status = $ref ? $ref['status'] : '';
?>
<section class="market-hero wrap">
  <p class="eyebrow">나의 마켓</p>
  <h1 class="hero-title">추천하고 수익 받기</h1>
  <p class="hero-text">내 추천인 코드로 이 사이트를 홍보하면, 그 코드로 신청한 마켓 결제금액의 <strong><?= REFERRAL_RATE ?>%</strong>가 내 수익으로 쌓여요. 수익으로 내 마켓 운영비를 채우거나 출금할 수 있어요.</p>
  <?= view('_market_tabs', array('tab' => 'referral')) ?>
</section>

<section class="page wrap-narrow referral">
<?php if (!$ref || $status === 'rejected'): ?>
  <ol class="steps">
    <li><strong>추천인 신청</strong><span>아래에서 신청하면 관리자가 확인해요.</span></li>
    <li><strong>승인되면 코드 발급</strong><span>나만의 추천인 코드와 홍보 링크가 생겨요.</span></li>
    <li><strong>홍보하고 수익 받기</strong><span>내 코드로 신청한 마켓이 결제되면 <?= REFERRAL_RATE ?>%가 쌓이고, 출금 신청할 수 있어요.</span></li>
  </ol>
<?php if ($status === 'rejected'): ?>
  <div class="alert" role="status"><p>지난 추천인 신청이 승인되지 않았어요.<?= trim((string) $ref['admin_memo']) !== '' ? ' 사유: ' . e($ref['admin_memo']) : '' ?> 내용을 보완해 다시 신청할 수 있어요.</p></div>
<?php endif; ?>
  <form method="post" action="/market/referral" class="checkout-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="apply">
    <div class="field">
      <label for="intro">어떻게 홍보할 계획인가요? <span class="muted">(선택)</span></label>
      <textarea id="intro" name="intro" rows="4" maxlength="1000" placeholder="예: 블로그와 인스타그램에서 전자책 작가들에게 소개할게요."></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block">추천인 신청하기</button>
  </form>

<?php elseif ($status === 'pending'): ?>
  <div class="info-box center">
    <span class="status status-pending">승인 대기</span>
    <h2>추천인 신청을 확인하고 있어요</h2>
    <p class="muted">관리자가 승인하면 이 화면에 추천인 코드와 홍보 링크가 나와요. (<?= e(fmt_date($ref['created_at'])) ?> 신청)</p>
  </div>

<?php else: ?>
  <div class="code-box">
    <div class="code-row">
      <span class="code-label">내 추천인 코드</span>
      <strong class="code-value" id="ref-code"><?= e($ref['code']) ?></strong>
      <button type="button" class="btn-chip" data-copy="#ref-code">복사</button>
    </div>
    <div class="code-row">
      <span class="code-label">홍보 링크</span>
      <span class="code-link" id="ref-link"><?= e($shareUrl) ?></span>
      <button type="button" class="btn-chip" data-copy="#ref-link">복사</button>
    </div>
    <p class="muted">이 링크로 들어온 사람이 마켓 운영을 신청하면 코드가 자동으로 들어가요. 신청서에 코드를 직접 적어도 돼요.</p>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><span>누적 수익</span><strong><?= won($balance['earned']) ?></strong></div>
    <div class="stat-card"><span>입금 대기 중</span><strong><?= won($balance['expected']) ?></strong></div>
    <div class="stat-card"><span>출금 완료</span><strong><?= won($balance['paid']) ?></strong></div>
    <div class="stat-card is-main"><span>출금 가능</span><strong><?= won($balance['available']) ?></strong></div>
  </div>
<?php if ($balance['requested']): ?>  <p class="muted small">출금 신청 중인 <?= won($balance['requested']) ?>은 출금 가능 금액에서 빠져 있어요.</p>
<?php endif; ?>

  <h2 class="block-title">추천으로 가입된 상품</h2>
<?php if ($referred): ?>
  <div class="data-table-wrap">
    <table class="data-table">
      <thead><tr><th scope="col">신청일</th><th scope="col">가입자</th><th scope="col">상품</th><th scope="col" class="num">결제금액</th><th scope="col">상태</th><th scope="col" class="num">내 수익</th></tr></thead>
      <tbody>
<?php foreach ($referred as $a): ?>
        <tr>
          <td><?= e(fmt_date($a['created_at'])) ?></td>
          <td><?= e(mask_name($a['user_name'])) ?></td>
          <td>마켓 운영 <?= (int) $a['months'] ?>개월</td>
          <td class="num"><?= won($a['total']) ?></td>
          <td><span class="status market-<?= e($a['status']) ?>"><?= $a['status'] === 'paid' ? '결제 완료' : e(MARKET_STATUS[$a['status']]) ?></span></td>
          <td class="num"><?= $a['status'] === 'cancelled' ? '-' : won($a['commission']) ?><?= $a['status'] === 'pending' ? '<small>입금 후 적립</small>' : '' ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="empty-reviews">아직 내 코드로 가입한 상품이 없어요. 홍보 링크를 나눠 보세요.</p>
<?php endif; ?>

  <h2 class="block-title">출금 계좌</h2>
  <form method="post" action="/market/referral" class="card-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="bank">
    <div class="grid-3-public">
      <div class="field"><label for="bank_name">은행</label><input id="bank_name" name="bank_name" type="text" maxlength="30" required placeholder="예: 국민은행" value="<?= e($ref['bank_name']) ?>"></div>
      <div class="field"><label for="bank_account">계좌번호</label><input id="bank_account" name="bank_account" type="text" inputmode="numeric" maxlength="40" required value="<?= e($ref['bank_account']) ?>"></div>
      <div class="field"><label for="bank_holder">예금주</label><input id="bank_holder" name="bank_holder" type="text" maxlength="30" required value="<?= e($ref['bank_holder']) ?>"></div>
    </div>
    <button type="submit" class="btn btn-outline">계좌 저장</button>
  </form>

  <h2 class="block-title">출금 신청</h2>
<?php $bankSet = $ref['bank_name'] !== '' && $ref['bank_account'] !== '' && $ref['bank_holder'] !== ''; ?>
  <form method="post" action="/market/referral" class="card-form" data-confirm="출금을 신청할까요?">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="withdraw">
    <div class="withdraw-row">
      <div class="field">
        <label for="amount">출금할 금액</label>
        <div class="input-suffix-public">
          <input id="amount" name="amount" type="text" inputmode="numeric" required value="<?= $balance['available'] ?: '' ?>" placeholder="<?= number_format(WITHDRAW_MIN) ?>">
          <span>원</span>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"<?= $bankSet && $balance['available'] >= WITHDRAW_MIN ? '' : ' disabled' ?>>출금 신청</button>
    </div>
    <p class="field-help">
<?php if (!$bankSet): ?>      출금 계좌를 먼저 저장해 주세요.
<?php elseif ($balance['available'] < WITHDRAW_MIN): ?>      출금 가능 금액이 <?= won(WITHDRAW_MIN) ?> 이상이면 신청할 수 있어요.
<?php else: ?>      <?= e($ref['bank_name']) ?> <?= e($ref['bank_account']) ?> (<?= e($ref['bank_holder']) ?>)로 보내 드려요. 최소 <?= won(WITHDRAW_MIN) ?>부터 신청할 수 있어요.
<?php endif; ?>
    </p>
  </form>

<?php if ($withdrawals): ?>
  <h2 class="block-title">출금 내역</h2>
  <div class="data-table-wrap">
    <table class="data-table">
      <thead><tr><th scope="col">신청일</th><th scope="col" class="num">금액</th><th scope="col">받는 계좌</th><th scope="col">상태</th></tr></thead>
      <tbody>
<?php foreach ($withdrawals as $w): ?>
        <tr>
          <td><?= e(fmt_date($w['created_at'])) ?></td>
          <td class="num"><?= won($w['amount']) ?></td>
          <td><?= e($w['bank_name']) ?> <?= e($w['bank_account']) ?></td>
          <td><span class="status withdraw-<?= e($w['status']) ?>"><?= e(WITHDRAW_STATUS[$w['status']]) ?></span><?php if (trim((string) $w['admin_memo']) !== ''): ?><small><?= e($w['admin_memo']) ?></small><?php endif; ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php endif; ?>
</section>
