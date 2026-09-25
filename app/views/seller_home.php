<?php
/** 나의 마켓 › 내 전자책 판매: 내 책(승인 상태), 판매 내역, 정산 금액, 정산 계좌, 정산 신청. */
$bankSet = $account && $account['bank_name'] !== '' && $account['bank_account'] !== '' && $account['bank_holder'] !== '';
?>
<section class="market-hero wrap">
  <p class="eyebrow">나의 마켓</p>
  <h1 class="hero-title">내 전자책 판매</h1>
  <p class="hero-text">내가 쓴 전자책을 올려 이 스토어에서 팔 수 있어요. 올린 책은 <strong>관리자가 승인하면</strong> 판매가 시작되고, 팔리면 판매가에서 <strong>수수료 <?= seller_commission() ?>%</strong>를 뺀 금액이 정산돼요.</p>
  <?= view('_market_tabs', array('tab' => 'sell')) ?>
</section>

<section class="page wrap-narrow referral">
  <div class="section-head">
    <h2 class="block-title flush-top">내 전자책</h2>
    <a class="btn btn-primary btn-sm" href="/market/sell/new">새 전자책 올리기</a>
  </div>
<?php if ($books): ?>
  <ul class="line-items">
<?php foreach ($books as $b): ?>
    <li class="line-item">
      <a href="/market/sell/<?= (int) $b['id'] ?>/edit" class="line-cover" tabindex="-1" aria-hidden="true"><?= cover_html($b, 'thumb') ?></a>
      <div class="line-body">
        <a class="line-title" href="/market/sell/<?= (int) $b['id'] ?>/edit"><?= e($b['title']) ?></a>
        <span class="line-sub"><?= $b['price'] ? won($b['price']) : '가격 미입력' ?> · 판매 <?= (int) $b['sold'] ?>권<?= $b['review_count'] ? ' · ★ ' . number_format($b['avg_rating'], 1) : '' ?></span>
<?php if ($b['status'] === 'rejected' && trim((string) $b['review_memo']) !== ''): ?>        <span class="line-warn">반려 사유: <?= e($b['review_memo']) ?></span>
<?php endif; ?>
      </div>
      <span class="status book-status-<?= e($b['status']) ?>"><?= e(BOOK_STATUS[$b['status']]) ?></span>
    </li>
<?php endforeach; ?>
  </ul>
<?php else: ?>
  <div class="info-box center">
    <h2>아직 올린 전자책이 없어요</h2>
    <p class="muted">EPUB이나 PDF 파일과 표지를 올리고 승인을 요청해 보세요.</p>
    <a class="btn btn-primary" href="/market/sell/new">새 전자책 올리기</a>
  </div>
<?php endif; ?>

  <div class="stat-grid">
    <div class="stat-card"><span>누적 정산 금액</span><strong><?= won($balance['earned']) ?></strong></div>
    <div class="stat-card"><span>입금 대기 중</span><strong><?= won($balance['expected']) ?></strong></div>
    <div class="stat-card"><span>정산 완료</span><strong><?= won($balance['paid']) ?></strong></div>
    <div class="stat-card is-main"><span>정산 가능</span><strong><?= won($balance['available']) ?></strong></div>
  </div>

  <h2 class="block-title">판매 내역</h2>
<?php if ($sales): ?>
  <div class="data-table-wrap">
    <table class="data-table">
      <thead><tr><th scope="col">주문일</th><th scope="col">전자책</th><th scope="col" class="num">판매가</th><th scope="col" class="num">수수료</th><th scope="col" class="num">내 정산 금액</th><th scope="col">상태</th></tr></thead>
      <tbody>
<?php foreach ($sales as $s): ?>
        <tr>
          <td><?= e(fmt_date($s['ordered_at'])) ?></td>
          <td><?= e($s['title']) ?></td>
          <td class="num"><?= won($s['price']) ?></td>
          <td class="num"><?= (int) $s['commission_rate'] ?>%</td>
          <td class="num"><?= won($s['seller_amount']) ?></td>
          <td><span class="status <?= $s['order_status'] === 'paid' ? 'withdraw-paid' : 'withdraw-requested' ?>"><?= $s['order_status'] === 'paid' ? '결제 완료' : '입금 대기' ?></span></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="empty-reviews">아직 판매된 전자책이 없어요.</p>
<?php endif; ?>

  <h2 class="block-title">정산 계좌</h2>
  <form method="post" action="/market/sell" class="card-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="bank">
    <div class="grid-3-public">
      <div class="field"><label for="bank_name">은행</label><input id="bank_name" name="bank_name" type="text" maxlength="30" required placeholder="예: 국민은행" value="<?= e($account['bank_name'] ?? '') ?>"></div>
      <div class="field"><label for="bank_account">계좌번호</label><input id="bank_account" name="bank_account" type="text" inputmode="numeric" maxlength="40" required value="<?= e($account['bank_account'] ?? '') ?>"></div>
      <div class="field"><label for="bank_holder">예금주</label><input id="bank_holder" name="bank_holder" type="text" maxlength="30" required value="<?= e($account['bank_holder'] ?? '') ?>"></div>
    </div>
    <button type="submit" class="btn btn-outline">계좌 저장</button>
  </form>

  <h2 class="block-title">정산 신청</h2>
  <form method="post" action="/market/sell" class="card-form" data-confirm="정산을 신청할까요?">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="withdraw">
    <div class="withdraw-row">
      <div class="field">
        <label for="amount">정산받을 금액</label>
        <div class="input-suffix-public">
          <input id="amount" name="amount" type="text" inputmode="numeric" required value="<?= $balance['available'] ?: '' ?>" placeholder="<?= number_format(WITHDRAW_MIN) ?>">
          <span>원</span>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"<?= $bankSet && $balance['available'] >= WITHDRAW_MIN ? '' : ' disabled' ?>>정산 신청</button>
    </div>
    <p class="field-help">
<?php if (!$bankSet): ?>      정산 계좌를 먼저 저장해 주세요.
<?php elseif ($balance['available'] < WITHDRAW_MIN): ?>      정산 가능 금액이 <?= won(WITHDRAW_MIN) ?> 이상이면 신청할 수 있어요.
<?php else: ?>      <?= e($account['bank_name']) ?> <?= e($account['bank_account']) ?> (<?= e($account['bank_holder']) ?>)로 보내 드려요.
<?php endif; ?>
    </p>
  </form>

<?php if ($withdrawals): ?>
  <h2 class="block-title">정산 내역</h2>
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
</section>
