<?php /** 관리자 › 마켓 운영 › 추천 정산. 변수: $period, $months, $rows, $totals */
list($key, , , $label) = $period;
$unpaid = $totals['earned'] - $totals['paid'];
?>
<?= view('admin/_market_tabs', array('tab' => $tab)) ?>
<div class="toolbar">
  <form method="get" action="/admin/market/settlement" class="settle-filter">
    <label for="month">정산 기간</label>
    <select id="month" name="month">
      <option value="all"<?= $key === 'all' ? ' selected' : '' ?>>전체 기간</option>
<?php foreach ($months as $m): ?>
      <option value="<?= e($m) ?>"<?= $key === $m ? ' selected' : '' ?>><?= (int) substr($m, 0, 4) ?>년 <?= (int) substr($m, 5, 2) ?>월</option>
<?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">보기</button>
  </form>
  <a class="btn btn-outline btn-sm" href="/admin/market/settlement?month=<?= e($key) ?>&amp;format=csv">엑셀(CSV) 내려받기</a>
</div>
<p class="muted small settle-note">추천 수익은 솔루션 입금을 확인한 날 생기고, 신청할 때 정해진 금액(결제금액의 <?= REFERRAL_RATE ?>%)으로 계산해요. <strong>출금 가능 = 누적 수익 − 지급 완료 − 출금 신청 중</strong></p>

<div class="stats settle-stats">
  <div class="stat card"><span class="stat-label"><?= e($label) ?> 추천 결제</span><strong class="stat-value"><?= number_format($totals['period_count']) ?>건</strong><span class="sub">결제금액 <?= won($totals['period_sales']) ?></span></div>
  <div class="stat card"><span class="stat-label"><?= e($label) ?> 발생 수익</span><strong class="stat-value"><?= won($totals['period_commission']) ?></strong><span class="sub">추천인에게 줄 금액</span></div>
  <div class="stat card"><span class="stat-label"><?= e($label) ?> 지급</span><strong class="stat-value"><?= won($totals['period_payout']) ?></strong><span class="sub">지급 완료 처리한 금액</span></div>
  <a class="stat card" href="/admin/market/withdrawals?status=requested"><span class="stat-label">아직 안 준 금액 (전체)</span><strong class="stat-value"><?= won($unpaid) ?></strong><span class="sub">출금 신청 중 <?= won($totals['requested']) ?></span></a>
</div>

<section class="card flush">
<?php if ($rows): ?>
  <div class="table-wrap">
  <table class="table settle-table">
    <thead>
      <tr>
        <th scope="col">추천인</th>
        <th scope="col" class="num"><?= e($label) ?> 결제</th>
        <th scope="col" class="num"><?= e($label) ?> 수익</th>
        <th scope="col" class="num"><?= e($label) ?> 지급</th>
        <th scope="col" class="num">누적 수익</th>
        <th scope="col" class="num">지급 완료</th>
        <th scope="col" class="num">신청 중</th>
        <th scope="col" class="num">출금 가능</th>
        <th scope="col"><span class="sr-only">상세</span></th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($rows as $r): $b = $r['balance']; ?>
      <tr>
        <td><a href="/admin/market/referrers/<?= (int) $r['user_id'] ?>" class="strong"><?= e($r['user_name'] ?? '(탈퇴)') ?></a><?php if ($r['code']): ?> <span class="code-pill small-pill"><?= e($r['code']) ?></span><?php endif; ?><div class="sub"><?= e($r['user_email'] ?? '') ?><?= $r['status'] !== 'approved' ? ' · ' . e(REFERRER_STATUS[$r['status']] ?? $r['status']) : '' ?></div></td>
        <td class="num"><?= number_format($r['period_count']) ?>건<div class="sub"><?= won($r['period_sales']) ?></div></td>
        <td class="num"><?= won($r['period_commission']) ?></td>
        <td class="num"><?= won($r['period_payout']) ?></td>
        <td class="num"><?= won($b['earned']) ?></td>
        <td class="num"><?= won($b['paid']) ?></td>
        <td class="num"><?= $b['requested'] ? '<span class="warn-text">' . won($b['requested']) . '</span>' : won(0) ?></td>
        <td class="num"><strong><?= won($b['available']) ?></strong></td>
        <td class="actions"><a class="btn btn-outline btn-sm" href="/admin/market/referrers/<?= (int) $r['user_id'] ?>">정산</a></td>
      </tr>
<?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th scope="row">합계</th>
        <td class="num"><?= number_format($totals['period_count']) ?>건<div class="sub"><?= won($totals['period_sales']) ?></div></td>
        <td class="num"><?= won($totals['period_commission']) ?></td>
        <td class="num"><?= won($totals['period_payout']) ?></td>
        <td class="num"><?= won($totals['earned']) ?></td>
        <td class="num"><?= won($totals['paid']) ?></td>
        <td class="num"><?= won($totals['requested']) ?></td>
        <td class="num"><strong><?= won($totals['available']) ?></strong></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  </div>
<?php else: ?>
  <p class="muted pad">아직 승인된 추천인이나 추천 실적이 없어요.</p>
<?php endif; ?>
</section>
