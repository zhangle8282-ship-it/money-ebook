<?php /** 관리자 › 정기청소 정산 › 월별 정산. 변수: $month, $rows, $sum, $year, $yearSummary, $contractCount */
$prev = month_shift($month, -1);
$next = month_shift($month, 1);
$pending = $sum['count'] - $sum['done'];
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>정기청소 정산</h1>
    <p class="muted">청소비용에서 <strong>세금 <?= CONTRACT_TAX_RATE ?>%</strong>를 빼고, 남은 금액의 <strong>도급비율(예: 20%)은 대표파트너 · 운영파트너</strong>가 나누고(예: 60:40), <strong>나머지(예: 80%)는 청소 담당 파트너</strong>가 원천징수 <?= CONTRACT_WITHHOLDING ?>%를 떼고 받아요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/contracts/new">+ 새 청소</a>
</div>
<?= view('admin/_contracts_tabs', array('tab' => 'month')) ?>

<section class="role-strip" aria-label="파트너가 하는 일">
<?php foreach (CONTRACT_ROLE_SIDES as $side => $label): ?>
  <div class="role-strip-col role-<?= $side ?>">
    <span class="role-badge" title="<?= e($label) ?>"><?= e(CONTRACT_ROLE_SHORT[$side]) ?></span>
    <ul><?php foreach ($roles[$side] as $task): ?><li><?= e($task) ?></li><?php endforeach; ?><?php if (!$roles[$side]): ?><li class="muted">정한 일 없음</li><?php endif; ?></ul>
  </div>
<?php endforeach; ?>
  <a class="role-strip-edit" href="/admin/contracts/roles">역할 고치기 ›</a>
</section>

<?php if ($rows): $back = '/admin/contracts' . ($month !== date('Y-m') ? '?month=' . $month : '');
    $unassigned = count(array_filter($rows, function ($r) { return !$r['contract']['byeong_partner_id']; })); ?>
<section class="card assign-board" id="assign" aria-labelledby="assign-title">
  <div class="assign-board-head">
    <h2 id="assign-title">청소 담당 배치</h2>
    <span class="sub"><?= e(month_label($month)) ?> 청소 <?= count($rows) ?>곳<?= $unassigned ? ' · <b class="warn-text">배치 안 됨 ' . $unassigned . '곳</b>' : ' · 모두 배치됨' ?></span>
    <a class="assign-board-link" href="/admin/workers">인력 배치 보기 ›</a>
  </div>
  <p class="sub">사람을 고르고 <b>배치</b>를 누르면 바로 바뀌어요. 청소 담당은 청소마다 정해지고, 다음 달 정산에도 그대로 이어져요.</p>
  <ul class="assign-rows">
<?php foreach ($rows as $r): $c = $r['contract']; ?>
    <li class="assign-row<?= $c['byeong_partner_id'] ? '' : ' is-empty' ?>">
      <div class="assign-what"><a class="strong" href="/admin/contracts/<?= (int) $c['id'] ?>/edit"><?= e($c['name']) ?></a><span class="sub"><?= $c['client'] !== '' ? e($c['client']) . ' · ' : '' ?>청소 담당 실지급 <?= won($r['byeong_pay']) ?></span></div>
      <?= view('admin/_cleaner_assign', array('c' => $c, 'back' => $back)) ?>
    </li>
<?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

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
  <div class="card settle-card is-byeong"><span>청소 담당 파트너 실지급</span><strong><?= won($sum['byeong_pay']) ?></strong><small>몫 <?= won($sum['byeong_amount']) ?> − 원천징수 <?= CONTRACT_WITHHOLDING ?>% <?= won($sum['withholding_amount']) ?></small></div>
  <div class="card settle-card"><span>도급 몫 (대표 · 운영 수익)</span><strong><?= won($sum['contract_amount']) ?></strong></div>
  <div class="card settle-card is-gap"><span>대표파트너 받는 돈</span><strong><?= won($sum['gap_amount']) ?></strong></div>
  <div class="card settle-card is-eul"><span>운영파트너 받는 돈</span><strong><?= won($sum['eul_amount']) ?></strong><small>정산 완료 <?= $sum['done'] ?> / <?= $sum['count'] ?></small></div>
</div>

<?php if ($rows): ?>
<div class="settle-list">
<?php foreach ($rows as $r): $c = $r['contract']; $done = $r['status'] === 'done';
    $steps = settlement_steps($r); $doneSteps = 0;
    foreach ($steps as $k => $label) { if (!empty($r['step_' . $k])) { $doneSteps++; } } ?>
  <article class="card settle-item<?= $done ? ' is-done' : '' ?>">
    <header class="settle-item-head">
      <div>
        <a class="strong settle-name" href="/admin/contracts/<?= (int) $c['id'] ?>/edit"><?= e($c['name']) ?></a>
        <span class="sub"><?= $c['client'] !== '' ? e($c['client']) . ' · ' : '' ?>청소 담당 <?= $r['byeong_rate'] ?>% · 도급 <?= $r['contract_rate'] ?>% (대표:운영 <?= $r['gap_rate'] ?>:<?= 100 - $r['gap_rate'] ?>)<?= $r['invoice'] ? '' : ' · 세금계산서 없음' ?><?= $r['withholding'] ? '' : ' · 원천징수 없음' ?></span>
      </div>
<?php if ($done): ?>
      <span class="status status-paid">정산 완료 · <?= e(fmt_date($r['settled_at'], 'm.d H:i')) ?><?= $r['settled_by'] !== '' ? ' · ' . e($r['settled_by']) : '' ?></span>
<?php else: ?>
      <span class="status status-pending">정산 전 <?= $doneSteps ?>/<?= count($steps) ?></span>
<?php endif; ?>
    </header>
    <dl class="settle-amounts">
      <div><dt>청소비용</dt><dd><?= won($r['fee']) ?></dd></div>
      <div class="minus"><dt>세금 <?= CONTRACT_TAX_RATE ?>%</dt><dd><?= $r['tax'] ? '− ' . won($r['tax']) : '없음' ?></dd></div>
      <div><dt>청소 담당 몫 <?= $r['byeong_rate'] ?>%</dt><dd><?= won($r['byeong_amount']) ?></dd></div>
      <div class="minus"><dt>원천징수 <?= CONTRACT_WITHHOLDING ?>%</dt><dd><?= $r['withholding_amount'] ? '− ' . won($r['withholding_amount']) : '없음' ?></dd><?php if ($r['withholding_amount']): ?><small>소득세 <?= number_format($r['income_tax']) ?> + 지방세 <?= number_format($r['local_tax']) ?></small><?php endif; ?></div>
      <div class="is-byeong"><dt>청소 담당 실지급</dt><dd><?= won($r['byeong_pay']) ?></dd><?php if (contract_partner_name($c, 'byeong') !== ''): ?><small><?= e(contract_partner_name($c, 'byeong')) ?></small><?php endif; ?></div>
      <div><dt>도급 몫 <?= $r['contract_rate'] ?>%</dt><dd><?= won($r['contract_amount']) ?></dd></div>
      <div class="is-gap"><dt>대표파트너 <?= $r['gap_rate'] ?>%</dt><dd><?= won($r['gap_amount']) ?></dd><?php if (contract_partner_name($c, 'gap') !== ''): ?><small><?= e(contract_partner_name($c, 'gap')) ?></small><?php endif; ?></div>
      <div class="is-eul"><dt>운영파트너 <?= 100 - $r['gap_rate'] ?>%</dt><dd><?= won($r['eul_amount']) ?></dd><?php if (contract_partner_name($c, 'eul') !== ''): ?><small><?= e(contract_partner_name($c, 'eul')) ?></small><?php endif; ?></div>
    </dl>
    <div class="settle-steps">
      <span class="settle-steps-label">대표파트너 정산</span>
      <ul class="step-list">
<?php foreach ($steps as $k => $label): $at = $r['step_' . $k]; ?>
        <li>
          <form method="post" action="/admin/contracts/settle"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="step"><input type="hidden" name="step" value="<?= $k ?>"><input type="hidden" name="on" value="<?= $at ? '0' : '1' ?>">
            <button type="submit" class="step-btn<?= $at ? ' is-on' : '' ?>" aria-pressed="<?= $at ? 'true' : 'false' ?>"><span class="step-box" aria-hidden="true"><?= $at ? '✓' : '' ?></span><?= e($label) ?><?php if ($k === 'paid_byeong'): ?> <b><?= won($r['byeong_pay']) ?></b><?php elseif ($k === 'paid_eul'): ?> <b><?= won($r['eul_amount']) ?></b><?php elseif ($k === 'received'): ?> <b><?= won($r['fee']) ?></b><?php endif; ?><?php if ($at): ?> <small><?= e(fmt_date($at, 'm.d')) ?></small><?php endif; ?></button>
          </form>
        </li>
<?php endforeach; ?>
      </ul>
      <div class="settle-actions">
<?php if ($done): ?>
        <form method="post" action="/admin/contracts/settle" data-confirm="정산 전으로 되돌릴까요? 체크한 단계도 모두 풀려요."><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="undo"><button type="submit" class="link-btn">되돌리기</button></form>
<?php else: ?>
        <form method="post" action="/admin/contracts/settle" data-confirm="남은 단계를 모두 체크하고 정산 완료로 할까요?"><?= csrf_field() ?><input type="hidden" name="month" value="<?= e($month) ?>"><input type="hidden" name="contract_id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="done"><button type="submit" class="btn btn-primary btn-sm">한 번에 정산 완료</button></form>
<?php endif; ?>
      </div>
    </div>
    <ul class="pay-to">
<?php foreach (array('byeong' => $r['byeong_pay'], 'eul' => $r['eul_amount']) as $role => $amount): $pp = contract_partner($c, $role); $nm = contract_partner_name($c, $role); ?>
      <li>
        <span class="pay-role"><?= e(CONTRACT_ROLE_SIDES[$role]) ?><?= $nm !== '' ? ' ' . e($nm) : '' ?></span>
        <strong><?= won($amount) ?></strong>
<?php if ($pp && $pp['bank_account'] !== ''): ?>
        <span class="pay-acc"><span class="bank-chip"><?= e($pp['bank_name']) ?></span> <span class="mono"><?= e($pp['bank_account']) ?></span> <span class="sub">예금주 <?= e($pp['bank_holder'] !== '' ? $pp['bank_holder'] : $pp['name']) ?></span></span>
        <button type="button" class="icon-btn" data-copy-text="<?= e($pp['bank_name'] . ' ' . $pp['bank_account'] . ' ' . ($pp['bank_holder'] !== '' ? $pp['bank_holder'] : $pp['name'])) ?>">계좌 복사</button>
<?php else: ?>
        <a class="sub warn-text" href="/admin/contracts/<?= (int) $c['id'] ?>/edit">지급 계좌를 정해 주세요 ›</a>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ul>
<?php if ($r['memo'] !== ''): ?>    <p class="sub memo-line">메모: <?= e($r['memo']) ?></p>
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
  </article>
<?php endforeach; ?>
  <section class="card settle-total" aria-label="이달 합계">
    <strong>합계 <span class="sub"><?= $sum['count'] ?>곳 · 정산 완료 <?= $sum['done'] ?>/<?= $sum['count'] ?></span></strong>
    <dl class="settle-amounts">
      <div><dt>청소비용</dt><dd><?= won($sum['fee']) ?></dd></div>
      <div class="minus"><dt>세금</dt><dd>− <?= won($sum['tax']) ?></dd></div>
      <div><dt>청소 담당 몫</dt><dd><?= won($sum['byeong_amount']) ?></dd></div>
      <div class="minus"><dt>원천징수</dt><dd>− <?= won($sum['withholding_amount']) ?></dd></div>
      <div class="is-byeong"><dt>청소 담당 실지급</dt><dd><?= won($sum['byeong_pay']) ?></dd></div>
      <div><dt>도급 몫</dt><dd><?= won($sum['contract_amount']) ?></dd></div>
      <div class="is-gap"><dt>대표파트너</dt><dd><?= won($sum['gap_amount']) ?></dd></div>
      <div class="is-eul"><dt>운영파트너</dt><dd><?= won($sum['eul_amount']) ?></dd></div>
    </dl>
  </section>
</div>
<?php elseif (!$contractCount): ?>
<section class="card"><div class="empty-card"><p>아직 등록한 청소가 없어요.</p><p class="sub">청소 이름, 월 청소비용, 도급비율, 대표·운영 파트너 비율을 넣으면 달마다 정산표가 자동으로 만들어져요.</p><a class="btn btn-primary" href="/admin/contracts/new">+ 첫 청소 추가하기</a></div></section>
<?php else: ?>
<section class="card"><p class="muted pad"><?= e(month_label($month)) ?>에 진행 중인 청소가 없어요.</p></section>
<?php endif; ?>

<section class="card flush" aria-labelledby="year-title">
  <div class="card-head pad-head"><h2 id="year-title"><?= $year ?>년 한눈에 보기</h2></div>
  <div class="table-wrap">
  <table class="table year-table">
    <thead><tr><th scope="col">월</th><th scope="col" class="num">청소</th><th scope="col" class="num">청소비용</th><th scope="col" class="num">세금</th><th scope="col" class="num">청소 담당 실지급</th><th scope="col" class="num">원천징수</th><th scope="col" class="num">대표파트너</th><th scope="col" class="num">운영파트너</th><th scope="col">정산</th></tr></thead>
    <tbody>
<?php $yt = array('fee' => 0, 'tax' => 0, 'byeong_pay' => 0, 'withholding_amount' => 0, 'gap_amount' => 0, 'eul_amount' => 0);
$hasPlanned = false;
foreach ($yearSummary as $ym => $t): foreach ($yt as $k => $v) { $yt[$k] += $t[$k]; }
    $future = $ym > date('Y-m');
    $hasPlanned = $hasPlanned || ($future && $t['count']); ?>
      <tr class="<?= $ym === $month ? 'is-current' : '' ?><?= !$t['count'] ? ' is-empty' : '' ?>">
        <td><a href="/admin/contracts?month=<?= e($ym) ?>"><?= (int) substr($ym, 5, 2) ?>월</a></td>
        <td class="num"><?= $t['count'] ? $t['count'] . '곳' : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['fee']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['tax']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['byeong_pay']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['withholding_amount']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['gap_amount']) : '-' ?></td>
        <td class="num"><?= $t['count'] ? won($t['eul_amount']) : '-' ?></td>
        <td><?php if (!$t['count']): ?>-<?php elseif ($t['done'] === $t['count']): ?><span class="status status-paid">완료</span><?php elseif ($future): ?><span class="sub">예정</span><?php else: ?><?= $t['done'] ?> / <?= $t['count'] ?><?php endif; ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
    <tfoot><tr><th scope="row"><?= $year ?>년 합계<?= $hasPlanned ? ' <span class="sub">(예정 포함)</span>' : '' ?></th><td></td><td class="num"><?= won($yt['fee']) ?></td><td class="num"><?= won($yt['tax']) ?></td><td class="num"><?= won($yt['byeong_pay']) ?></td><td class="num"><?= won($yt['withholding_amount']) ?></td><td class="num"><strong><?= won($yt['gap_amount']) ?></strong></td><td class="num"><strong><?= won($yt['eul_amount']) ?></strong></td><td></td></tr></tfoot>
  </table>
  </div>
</section>
