<?php /** 관리자 › 마켓 운영 › 추천 정산 › 추천인 한 명. 변수: $ref, $balance, $items, $salesTotal, $paidCount, $monthly, $withdrawals */
$back = '/admin/market/referrers/' . (int) $ref['user_id'];
$bankSet = $ref['bank_name'] !== '' && $ref['bank_account'] !== '' && $ref['bank_holder'] !== '';
?>
<?= view('admin/_market_tabs', array('tab' => $tab)) ?>
<p class="back-link"><a href="/admin/market/settlement">‹ 추천 정산 목록</a></p>

<section class="card ref-head">
  <div>
    <h2><?= e($ref['user_name'] ?? '(탈퇴)') ?> <span class="status referrer-<?= e($ref['status']) ?>"><?= e(REFERRER_STATUS[$ref['status']] ?? $ref['status']) ?></span><?php if ($ref['code']): ?> <span class="code-pill"><?= e($ref['code']) ?></span><?php endif; ?></h2>
    <p class="sub"><?= e($ref['user_email'] ?? '') ?> · <?= e(fmt_date($ref['created_at'])) ?> 신청<?= $ref['decided_at'] ? ' · ' . e(fmt_date($ref['decided_at'])) . ' 처리' : '' ?></p>
  </div>
  <p class="ref-bank"><?= $bankSet ? '출금 계좌 <strong>' . e($ref['bank_name']) . ' ' . e($ref['bank_account']) . '</strong> (' . e($ref['bank_holder']) . ')' : '<span class="warn-text">출금 계좌를 아직 등록하지 않았어요</span>' ?></p>
</section>

<div class="grid-2 settle-grid">
  <section class="card stack" aria-labelledby="calc-title">
    <h2 id="calc-title">정산 계산</h2>
    <dl class="calc">
      <div><dt>누적 수익<small>결제 완료 <?= (int) $paidCount ?>건 · 결제금액 <?= won($salesTotal) ?> × <?= REFERRAL_RATE ?>%</small></dt><dd><?= won($balance['earned']) ?></dd></div>
      <div class="minus"><dt>지급 완료</dt><dd>− <?= won($balance['paid']) ?></dd></div>
      <div class="minus"><dt>출금 신청 중</dt><dd>− <?= won($balance['requested']) ?></dd></div>
      <div class="total"><dt>출금 가능</dt><dd><?= won($balance['available']) ?></dd></div>
    </dl>
<?php if ($balance['expected']): ?>
    <p class="field-help">입금 대기 중인 추천 신청의 예상 수익 <?= won($balance['expected']) ?>은 입금을 확인하면 더해져요.</p>
<?php endif; ?>
  </section>

  <form method="post" action="<?= e($back) ?>" class="card stack" aria-labelledby="payout-title" data-confirm="계좌로 보낸 금액을 지급 완료로 기록할까요?">
    <?= csrf_field() ?><input type="hidden" name="action" value="payout"><input type="hidden" name="back" value="<?= e($back) ?>">
    <h2 id="payout-title">바로 지급하기</h2>
    <p class="field-help">출금 신청이 없어도, 계좌로 보낸 뒤 여기서 지급 완료로 기록할 수 있어요(월 정산 등). 출금 가능 금액까지만 기록돼요.</p>
    <div class="field">
      <label for="payout-amount">지급한 금액</label>
      <div class="input-suffix"><input id="payout-amount" name="amount" type="text" inputmode="numeric" value="<?= $balance['available'] ? e(number_format($balance['available'])) : '' ?>" placeholder="0"><span>원</span></div>
    </div>
    <div class="field">
      <label for="payout-memo">메모 (추천인에게 보여요)</label>
      <input id="payout-memo" name="admin_memo" type="text" maxlength="500" placeholder="예: <?= e(date('Y년 n월')) ?> 정산">
    </div>
    <button type="submit" class="btn btn-primary"<?= $balance['available'] > 0 ? '' : ' disabled' ?>>지급 완료로 기록</button>
  </form>
</div>

<section class="card flush" aria-labelledby="w-title">
  <div class="card-head pad-head"><h2 id="w-title">출금 · 지급 내역</h2></div>
<?php if ($withdrawals): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">신청일</th><th scope="col" class="num">금액</th><th scope="col">보낼 계좌</th><th scope="col">상태 · 처리</th></tr></thead>
    <tbody>
<?php foreach ($withdrawals as $w):
    list(, $room) = withdrawal_room($w); ?>
      <tr>
        <td><?= e(fmt_date($w['created_at'], 'Y.m.d H:i')) ?></td>
        <td class="num"><strong><?= won($w['amount']) ?></strong></td>
        <td><?= e($w['bank_name']) ?> <span class="mono"><?= e($w['bank_account']) ?></span><div class="sub">예금주 <?= e($w['bank_holder']) ?></div></td>
        <td>
<?php if ($w['status'] === 'requested'): ?>
          <form method="post" action="/admin/market/withdrawals/<?= (int) $w['id'] ?>" class="decide-form">
            <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
            <input type="text" name="admin_memo" maxlength="500" placeholder="메모 (선택)" aria-label="메모">
            <button type="submit" name="action" value="paid" class="btn btn-primary btn-sm" data-confirm-click="<?= e(won($w['amount'])) ?>을 보냈나요?"<?= (int) $w['amount'] > $room ? ' disabled title="잔액이 모자라요"' : '' ?>>지급 완료</button>
            <button type="submit" name="action" value="rejected" class="btn btn-ghost btn-sm">반려</button>
          </form>
<?php else: ?>
          <span class="status withdraw-<?= e($w['status']) ?>"><?= e(WITHDRAW_STATUS[$w['status']]) ?></span>
          <div class="sub"><?= e(fmt_date($w['processed_at'], 'Y.m.d H:i')) ?><?= trim((string) $w['admin_memo']) !== '' ? ' · ' . e($w['admin_memo']) : '' ?></div>
<?php endif; ?>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <p class="muted pad">출금 신청이나 지급 기록이 없어요.</p>
<?php endif; ?>
</section>

<section class="card flush" aria-labelledby="m-title">
  <div class="card-head pad-head"><h2 id="m-title">월별 정산</h2></div>
<?php if ($monthly): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">월</th><th scope="col" class="num">결제 건수</th><th scope="col" class="num">결제금액</th><th scope="col" class="num">발생 수익</th><th scope="col" class="num">지급액</th></tr></thead>
    <tbody>
<?php foreach ($monthly as $ym => $m): ?>
      <tr>
        <td><?= (int) substr($ym, 0, 4) ?>년 <?= (int) substr($ym, 5, 2) ?>월</td>
        <td class="num"><?= number_format($m['count']) ?>건</td>
        <td class="num"><?= won($m['sales']) ?></td>
        <td class="num"><?= won($m['commission']) ?></td>
        <td class="num"><?= won($m['payout']) ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <p class="muted pad">아직 결제된 추천 실적이 없어요.</p>
<?php endif; ?>
</section>

<section class="card flush" aria-labelledby="i-title">
  <div class="card-head pad-head"><h2 id="i-title">추천 상품 내역 <span class="sub">(<?= count($items) ?>건)</span></h2></div>
<?php if ($items): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">신청</th><th scope="col">가입자</th><th scope="col">상품</th><th scope="col" class="num">결제금액</th><th scope="col" class="num">추천 수익</th><th scope="col">상태</th></tr></thead>
    <tbody>
<?php foreach ($items as $a): ?>
      <tr>
        <td><a href="/admin/market?status=<?= e($a['status']) ?>" class="mono"><?= e($a['app_no']) ?></a><div class="sub"><?= e(fmt_date($a['created_at'], 'Y.m.d')) ?></div></td>
        <td><?= e($a['user_name'] ?? '(탈퇴)') ?><div class="sub"><?= e($a['user_email'] ?? '') ?></div></td>
        <td><?= e(market_product_name($a)) ?></td>
        <td class="num"><?= won($a['total']) ?></td>
        <td class="num"><?= $a['status'] === 'cancelled' ? '<span class="sub">-</span>' : won($a['commission']) ?></td>
        <td><span class="status market-<?= e($a['status']) ?>"><?= e(MARKET_STATUS[$a['status']] ?? $a['status']) ?></span><?php if ($a['status'] === 'paid' && $a['paid_at']): ?><div class="sub"><?= e(fmt_date($a['paid_at'], 'Y.m.d')) ?> 입금 확인</div><?php elseif ($a['status'] === 'pending'): ?><div class="sub">입금 확인 뒤 적립</div><?php endif; ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <p class="muted pad">이 추천인의 홍보 링크로 들어온 솔루션 신청이 아직 없어요.</p>
<?php endif; ?>
</section>
