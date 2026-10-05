<?php /** 관리자 › 도급 정산 › 월별 정산. 변수: $month, $rows, $sum, $year, $yearSummary, $contractCount */
$prev = month_shift($month, -1);
$next = month_shift($month, 1);
$pending = $sum['count'] - $sum['done'];
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>도급 정산</h1>
    <p class="muted">청소비용에서 <strong>세금 <?= CONTRACT_TAX_RATE ?>%</strong>를 먼저 빼고, 남은 금액에서 <strong>도급비용</strong>을 뺀 뒤 <strong>갑 · 을</strong>이 나눠요. 달마다 확인하고 ‘정산 완료’를 눌러 기록하세요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/contracts/new">+ 새 청소</a>
</div>
<?= view('admin/_contracts_tabs', array('tab' => 'month')) ?>

<section class="role-strip" aria-label="갑 · 을이 하는 일">
<?php foreach (CONTRACT_ROLE_SIDES as $side => $label): ?>
  <div class="role-strip-col role-<?= $side ?>">
    <span class="role-badge"><?= $label ?></span>
    <ul><?php foreach ($roles[$side] as $task): ?><li><?= e($task) ?></li><?php endforeach; ?><?php if (!$roles[$side]): ?><li class="muted">정한 일 없음</li><?php endif; ?></ul>
  </div>
<?php endforeach; ?>
  <a class="role-strip-edit" href="/admin/contracts/roles">역할 고치기 ›</a>
</section>

<div class="toolbar month-bar">
  <div class="month-nav">
    <a class="btn btn-outline btn-sm" href="/admin/contracts?month=<?= e($prev) ?>" aria-label="이전 달">‹ <?= (int) substr($prev, 5, 2) ?>월</a>
    <strong class="month-now"><?= e(month_label($month)) ?></strong>
    <a class="btn btn-outline btn-sm" href="/admin/contracts?month=<?= e($next) ?>" aria-label="다음 달"><?= (int) substr($next, 5, 2) ?>월 ›</a>
<?php if ($month !== date('Y-m')): ?>    <a class="month-today" href="/admin/contracts">이번 달로</a>
<?php endif; ?>
  </div>
  <div class="month-actions">
<?php if ($rows): ?>    <a class="btn btn-outline btn-sm" href="/admin/contracts?month=<?= e($month) ?>&amp;format=csv">엑셀(CSV) 내려받기</a>
<?php endif; ?>
<?php if ($pending > 0): ?>
    <form method="post" action="/admin/contracts/settle" data-confirm="<?= e(month_label($month)) ?> 정산 <?= $pending ?>건을 모두 완료로 처리할까요?">
      <?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="action" value="done_all">
      <button type="submit" class="btn btn-primary btn-sm">모두 정산 완료 (<?= $pending ?>건)</button>
    </form>
<?php endif; ?>
  </div>
</div>

<div class="settle-cards">
  <div class="card settle-card"><span>청소비용</span><strong><?= won($sum['fee']) ?></strong><small><?= $sum['count'] ?>곳</small></div>
  <div class="card settle-card"><span>세금 <?= CONTRACT_TAX_RATE ?>% (세금계산서)</span><strong class="minus">− <?= won($sum['tax']) ?></strong></div>
  <div class="card settle-card"><span>도급비용</span><strong class="minus">− <?= won($sum['contract_amount']) ?></strong></div>
  <div class="card settle-card"><span>나눌 금액</span><strong><?= won($sum['base']) ?></strong></div>
  <div class="card settle-card is-gap"><span>갑 받는 돈</span><strong><?= won($sum['gap_amount']) ?></strong></div>
  <div class="card settle-card is-eul"><span>을 받는 돈</span><strong><?= won($sum['eul_amount']) ?></strong><small>정산 완료 <?= $sum['done'] ?> / <?= $sum['count'] ?></small></div>
</div>

<section class="card flush">
<?php if ($rows): ?>
  <div class="table-wrap">
  <table class="table contract-table">
    <thead><tr>
      <th scope="col">청소</th><th scope="col" class="num">청소비용</th><th scope="col" class="num">세금 <?= CONTRACT_TAX_RATE ?>%</th>
      <th scope="col" class="num">도급비용</th><th scope="col" class="num">나눌 금액</th><th scope="col" class="num">갑</th><th scope="col" class="num">을</th><th scope="col">정산</th>
    </tr></thead>
    <tbody>
<?php foreach ($rows as $r): $c = $r['contract']; $done = $r['status'] === 'done'; ?>
      <tr class="<?= $done ? 'is-done' : '' ?>">
        <td>
          <a class="strong" href="/admin/contracts/<?= (int) $c['id'] ?>/edit"><?= e($c['name']) ?></a>
          <div class="sub"><?= $c['client'] !== '' ? e($c['client']) . ' · ' : '' ?>도급 <?= $r['contract_rate'] ?>% · 갑:을 <?= $r['gap_rate'] ?>:<?= 100 - $r['gap_rate'] ?><?= $r['invoice'] ? '' : ' · 세금계산서 없음' ?></div>
<?php if ($r['memo'] !== ''): ?>          <div class="sub memo-line">메모: <?= e($r['memo']) ?></div>
<?php endif; ?>
<?php if (!$done): ?>
          <details class="fee-edit">
            <summary>이번 달 금액 바꾸기</summary>
            <form method="post" action="/admin/contracts/settle" class="inline-edit">
              <?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="save">
              <label class="sr-only" for="fee-<?= (int) $c['id'] ?>">이번 달 청소비용</label>
              <input id="fee-<?= (int) $c['id'] ?>" name="fee" type="text" inputmode="numeric" value="<?= e(number_format($r['fee'])) ?>" data-money>
              <input name="memo" type="text" maxlength="300" placeholder="메모 (예: 반달 작업)" value="<?= e($r['memo']) ?>">
              <button type="submit" class="btn btn-outline btn-sm">저장</button>
            </form>
<?php if ($r['saved']): ?>
            <form method="post" action="/admin/contracts/settle"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="reset"><button type="submit" class="link-btn">계약 조건대로 되돌리기</button></form>
<?php endif; ?>
          </details>
<?php endif; ?>
        </td>
        <td class="num"><?= won($r['fee']) ?></td>
        <td class="num minus"><?= $r['tax'] ? '− ' . won($r['tax']) : '-' ?></td>
        <td class="num minus">− <?= won($r['contract_amount']) ?></td>
        <td class="num"><?= won($r['base']) ?></td>
        <td class="num gap-col"><strong><?= won($r['gap_amount']) ?></strong><?php if ($c['gap_name'] !== ''): ?><div class="sub"><?= e($c['gap_name']) ?></div><?php endif; ?></td>
        <td class="num eul-col"><strong><?= won($r['eul_amount']) ?></strong><?php if ($c['eul_name'] !== ''): ?><div class="sub"><?= e($c['eul_name']) ?></div><?php endif; ?></td>
        <td class="settle-cell">
<?php if ($done): ?>
          <span class="status status-paid">정산 완료</span>
          <div class="sub"><?= e(fmt_date($r['settled_at'], 'm.d')) ?></div>
          <form method="post" action="/admin/contracts/settle" data-confirm="정산 전으로 되돌릴까요?"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="undo"><button type="submit" class="link-btn">되돌리기</button></form>
<?php else: ?>
          <span class="status status-pending">정산 전</span>
          <form method="post" action="/admin/contracts/settle"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="done"><button type="submit" class="btn btn-primary btn-sm">정산 완료</button></form>
<?php endif; ?>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
    <tfoot><tr>
      <th scope="row">합계 <span class="sub"><?= $sum['count'] ?>곳</span></th>
      <td class="num"><?= won($sum['fee']) ?></td><td class="num minus">− <?= won($sum['tax']) ?></td><td class="num minus">− <?= won($sum['contract_amount']) ?></td>
      <td class="num"><?= won($sum['base']) ?></td><td class="num gap-col"><strong><?= won($sum['gap_amount']) ?></strong></td><td class="num eul-col"><strong><?= won($sum['eul_amount']) ?></strong></td>
      <td><?= $sum['done'] ?> / <?= $sum['count'] ?> 완료</td>
    </tr></tfoot>
  </table>
  </div>
<?php elseif (!$contractCount): ?>
  <div class="empty-card"><p>아직 등록한 청소가 없어요.</p><p class="sub">청소 이름, 월 청소비용, 도급비율, 갑·을 비율을 넣으면 달마다 정산표가 자동으로 만들어져요.</p><a class="btn btn-primary" href="/admin/contracts/new">+ 첫 청소 추가하기</a></div>
<?php else: ?>
  <p class="muted pad"><?= e(month_label($month)) ?>에 진행 중인 청소가 없어요.</p>
<?php endif; ?>
</section>

<section class="card flush" aria-labelledby="year-title">
  <div class="card-head pad-head"><h2 id="year-title"><?= $year ?>년 한눈에 보기</h2></div>
  <div class="table-wrap">
  <table class="table year-table">
    <thead><tr><th scope="col">월</th><th scope="col" class="num">청소</th><th scope="col" class="num">청소비용</th><th scope="col" class="num">세금</th><th scope="col" class="num">도급비용</th><th scope="col" class="num">갑</th><th scope="col" class="num">을</th><th scope="col">정산</th></tr></thead>
    <tbody>
<?php $yt = array('fee' => 0, 'tax' => 0, 'contract_amount' => 0, 'gap_amount' => 0, 'eul_amount' => 0);
$hasPlanned = false;
foreach ($yearSummary as $ym => $t): foreach ($yt as $k => $v) { $yt[$k] += $t[$k]; }
    $future = $ym > date('Y-m');
    $hasPlanned = $hasPlanned || ($future && $t['count']); ?>
      <tr class="<?= $ym === $month ? 'is-current' : '' ?><?= !$t['count'] ? ' is-empty' : '' ?>">
        <td><a href="/admin/contracts?month=<?= e($ym) ?>"><?= (int) substr($ym, 5, 2) ?>월</a></td>
        <td class="num"><?= $t['count'] ? $t['count'] . '곳' : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['fee']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['tax']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['contract_amount']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['gap_amount']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['eul_amount']) : '-' ?></td>
        <td><?php if (!$t['count']): ?>-<?php elseif ($t['done'] === $t['count']): ?><span class="status status-paid">완료</span><?php elseif ($future): ?><span class="sub">예정</span><?php else: ?><?= $t['done'] ?> / <?= $t['count'] ?><?php endif; ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
    <tfoot><tr><th scope="row"><?= $year ?>년 합계<?= $hasPlanned ? ' <span class="sub">(예정 포함)</span>' : '' ?></th><td></td><td class="num"><?= won($yt['fee']) ?></td><td class="num"><?= won($yt['tax']) ?></td><td class="num"><?= won($yt['contract_amount']) ?></td><td class="num"><strong><?= won($yt['gap_amount']) ?></strong></td><td class="num"><strong><?= won($yt['eul_amount']) ?></strong></td><td></td></tr></tfoot>
  </table>
  </div>
</section>
