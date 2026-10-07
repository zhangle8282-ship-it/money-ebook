<?php /** 관리자 › 일회성 정산 › 목록 · 정산. 변수: $month, $pendingOnly, $rows, $sum, $pendingCount, $total, $roles */
$prev = month_shift($month, -1);
$next = month_shift($month, 1);
$back = $_SERVER['REQUEST_URI'] ?? '/admin/onetime';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>일회성 정산</h1>
    <p class="muted">입주청소 · 대청소처럼 <strong>한 번 하는 일</strong>의 수익을 나눠요. 청소비용에서 (세금계산서를 발행했으면 <strong>세금 <?= CONTRACT_TAX_RATE ?>%</strong>를 빼고) <strong>회사 몫(수수료)은 대표파트너 · 운영파트너</strong>가 <strong><?= ONETIME_GAP ?> : <?= 100 - ONETIME_GAP ?></strong>으로 나누고, <strong>나머지는 청소 담당</strong>이 원천징수 <?= CONTRACT_WITHHOLDING ?>%를 떼고 받아요. <strong>수수료 방식</strong>은 청소 담당에게 수수료 10 · 20%를 빼고 주고, <strong>인수 방식</strong>은 청소 담당 몫 없이 청소 금액 전체를 대표 · 운영이 나눠요. 달마다 하는 청소는 <a href="/admin/contracts">정기청소 정산</a>에서 해요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/onetime/new">+ 새 일회성 정산</a>
</div>
<?= view('admin/_onetime_tabs', array('tab' => 'settle')) ?>

<section class="role-strip" aria-label="파트너가 하는 일">
<?php foreach (CONTRACT_ROLE_SIDES as $side => $label): ?>
  <div class="role-strip-col role-<?= $side ?>">
    <span class="role-badge" title="<?= e($label) ?>"><?= e(CONTRACT_ROLE_SHORT[$side]) ?></span>
    <ul><?php foreach ($roles[$side] as $task): ?><li><?= e($task) ?></li><?php endforeach; ?><?php if (!$roles[$side]): ?><li class="muted">정한 일 없음</li><?php endif; ?></ul>
  </div>
<?php endforeach; ?>
  <a class="role-strip-edit" href="/admin/onetime/roles">역할 고치기 ›</a>
</section>

<div class="toolbar month-bar">
  <nav class="tabs" aria-label="보기">
    <a href="/admin/onetime?month=<?= e($month) ?>"<?= $pendingOnly ? '' : ' aria-current="page"' ?>>작업 월별</a>
    <a href="/admin/onetime?status=pending"<?= $pendingOnly ? ' aria-current="page"' : '' ?>>정산 전 모두 <span class="count"><?= (int) $pendingCount ?></span></a>
  </nav>
<?php if (!$pendingOnly): ?>
  <div class="month-nav">
    <a class="btn btn-outline btn-sm" href="/admin/onetime?month=<?= e($prev) ?>" aria-label="이전 달">‹ <?= (int) substr($prev, 5, 2) ?>월</a>
    <strong class="month-now"><?= e(month_label($month)) ?></strong>
    <a class="btn btn-outline btn-sm" href="/admin/onetime?month=<?= e($next) ?>" aria-label="다음 달"><?= (int) substr($next, 5, 2) ?>월 ›</a>
<?php if ($month !== date('Y-m')): ?>    <a class="month-today" href="/admin/onetime">이번 달로</a>
<?php endif; ?>
  </div>
<?php endif; ?>
</div>

<div class="settle-cards">
  <div class="card settle-card"><span>청소비용</span><strong><?= won($sum['fee']) ?></strong><small><?= $sum['count'] ?>건</small></div>
  <div class="card settle-card"><span>세금 <?= CONTRACT_TAX_RATE ?>% (세금계산서)</span><strong class="minus">− <?= won($sum['tax']) ?></strong></div>
  <div class="card settle-card is-byeong"><span>청소 담당 실지급</span><strong><?= won($sum['byeong_pay']) ?></strong><small>몫 <?= won($sum['byeong_amount']) ?> − 원천징수 <?= won($sum['withholding_amount']) ?></small></div>
  <div class="card settle-card"><span>회사 몫 (대표 · 운영 수익)</span><strong><?= won($sum['contract_amount']) ?></strong></div>
  <div class="card settle-card is-gap"><span>대표파트너 받는 돈</span><strong><?= won($sum['gap_amount']) ?></strong></div>
  <div class="card settle-card is-eul"><span>운영파트너 받는 돈</span><strong><?= won($sum['eul_amount']) ?></strong><small>정산 완료 <?= $sum['done'] ?> / <?= $sum['count'] ?></small></div>
</div>

<?php if ($rows): ?>
<div class="settle-list">
<?php foreach ($rows as $r): $done = $r['status'] === 'done'; $takeover = ($r['method'] ?? 'commission') === 'takeover';
    $steps = settlement_steps($r); $doneSteps = 0;
    foreach ($steps as $k => $label) { if (!empty($r['step_' . $k])) { $doneSteps++; } }
    $settleUrl = '/admin/onetime/' . (int) $r['id'] . '/settle'; ?>
  <article class="card settle-item<?= $done ? ' is-done' : '' ?>">
    <header class="settle-item-head">
      <div>
        <a class="strong settle-name" href="/admin/onetime/<?= (int) $r['id'] ?>/edit"><?= e($r['name']) ?></a>
        <span class="sub"><?= e(date('Y.m.d', strtotime($r['work_date']))) ?> 작업<?= $r['client'] !== '' ? ' · ' . e($r['client']) : '' ?> · <b><?= e(onetime_method_label($r)) ?></b> · 청소 담당 <?= $r['byeong_rate'] ?>% (대표:운영 <?= (int) $r['gap_rate'] ?>:<?= 100 - (int) $r['gap_rate'] ?>)<?= $r['invoice'] ? '' : ' · 세금계산서 없음' ?><?= $r['withholding'] ? '' : ' · 원천징수 없음' ?></span>
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
<?php if (!$takeover): ?>
      <div><dt>청소 담당 몫 <?= $r['byeong_rate'] ?>%</dt><dd><?= won($r['byeong_amount']) ?></dd></div>
      <div class="minus"><dt>원천징수 <?= CONTRACT_WITHHOLDING ?>%</dt><dd><?= $r['withholding_amount'] ? '− ' . won($r['withholding_amount']) : '없음' ?></dd><?php if ($r['withholding_amount']): ?><small>소득세 <?= number_format($r['income_tax']) ?> + 지방세 <?= number_format($r['local_tax']) ?></small><?php endif; ?></div>
      <div class="is-byeong"><dt>청소 담당 실지급</dt><dd><?= won($r['byeong_pay']) ?></dd><?php if (contract_partner_name($r, 'byeong') !== ''): ?><small><?= e(contract_partner_name($r, 'byeong')) ?></small><?php endif; ?></div>
<?php endif; ?>
      <div><dt><?= $takeover ? '대표 · 운영이 나눌 금액(인수)' : '수수료 ' . (int) $r['contract_rate'] . '% (대표 · 운영)' ?></dt><dd><?= won($r['contract_amount']) ?></dd></div>
      <div class="is-gap"><dt>대표파트너 <?= (int) $r['gap_rate'] ?>%</dt><dd><?= won($r['gap_amount']) ?></dd><?php if (contract_partner_name($r, 'gap') !== ''): ?><small><?= e(contract_partner_name($r, 'gap')) ?></small><?php endif; ?></div>
      <div class="is-eul"><dt>운영파트너 <?= 100 - (int) $r['gap_rate'] ?>%</dt><dd><?= won($r['eul_amount']) ?></dd><?php if (contract_partner_name($r, 'eul') !== ''): ?><small><?= e(contract_partner_name($r, 'eul')) ?></small><?php endif; ?></div>
    </dl>
    <div class="settle-steps">
      <span class="settle-steps-label">대표파트너 정산</span>
      <ul class="step-list">
<?php foreach ($steps as $k => $label): $at = $r['step_' . $k]; ?>
        <li>
          <form method="post" action="<?= e($settleUrl) ?>"><?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="action" value="step"><input type="hidden" name="step" value="<?= $k ?>"><input type="hidden" name="on" value="<?= $at ? '0' : '1' ?>">
            <button type="submit" class="step-btn<?= $at ? ' is-on' : '' ?>" aria-pressed="<?= $at ? 'true' : 'false' ?>"><span class="step-box" aria-hidden="true"><?= $at ? '✓' : '' ?></span><?= e($label) ?><?php if ($k === 'paid_byeong'): ?> <b><?= won($r['byeong_pay']) ?></b><?php elseif ($k === 'paid_eul'): ?> <b><?= won($r['eul_amount']) ?></b><?php elseif ($k === 'received'): ?> <b><?= won($r['fee']) ?></b><?php endif; ?><?php if ($at): ?> <small><?= e(fmt_date($at, 'm.d')) ?></small><?php endif; ?></button>
          </form>
        </li>
<?php endforeach; ?>
      </ul>
      <div class="settle-actions">
<?php if ($done): ?>
        <form method="post" action="<?= e($settleUrl) ?>" data-confirm="정산 전으로 되돌릴까요? 체크한 단계도 모두 풀려요."><?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="action" value="undo"><button type="submit" class="link-btn">되돌리기</button></form>
<?php else: ?>
        <form method="post" action="<?= e($settleUrl) ?>" data-confirm="남은 단계를 모두 체크하고 정산 완료로 할까요?"><?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="action" value="done"><button type="submit" class="btn btn-primary btn-sm">한 번에 정산 완료</button></form>
<?php endif; ?>
      </div>
    </div>
    <ul class="pay-to">
<?php foreach ($takeover ? array('eul' => $r['eul_amount']) : array('byeong' => $r['byeong_pay'], 'eul' => $r['eul_amount']) as $role => $amount): $pp = contract_partner($r, $role); $nm = contract_partner_name($r, $role); ?>
      <li>
        <span class="pay-role"><?= e(CONTRACT_ROLE_SIDES[$role]) ?><?= $nm !== '' ? ' ' . e($nm) : '' ?></span>
        <strong><?= won($amount) ?></strong>
<?php if ($pp && $pp['bank_account'] !== ''): ?>
        <span class="pay-acc"><span class="bank-chip"><?= e($pp['bank_name']) ?></span> <span class="mono"><?= e($pp['bank_account']) ?></span> <span class="sub">예금주 <?= e($pp['bank_holder'] !== '' ? $pp['bank_holder'] : $pp['name']) ?></span></span>
        <button type="button" class="icon-btn" data-copy-text="<?= e($pp['bank_name'] . ' ' . $pp['bank_account'] . ' ' . ($pp['bank_holder'] !== '' ? $pp['bank_holder'] : $pp['name'])) ?>">계좌 복사</button>
<?php elseif ($pp): ?>
        <a class="sub warn-text" href="/admin/contracts/partners/<?= (int) $pp['id'] ?>/edit">지급 계좌를 넣어 주세요 ›</a>
<?php else: ?>
        <a class="sub warn-text" href="/admin/onetime/<?= (int) $r['id'] ?>/edit">받을 사람을 정해 주세요 ›</a>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ul>
<?php if ((string) $r['memo'] !== ''): ?>    <p class="sub memo-line">메모: <?= e($r['memo']) ?></p>
<?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
<?php elseif (!$total): ?>
<section class="card"><div class="empty-card"><p>아직 등록한 일회성 정산이 없어요.</p><p class="sub">일 이름, 작업일, 청소비용, 도급 비율(10 · 20%), 대표 · 운영 · 청소 담당을 넣으면 나눌 금액이 바로 계산돼요.</p><a class="btn btn-primary" href="/admin/onetime/new">+ 첫 일회성 정산 추가</a></div></section>
<?php else: ?>
<section class="card"><p class="muted pad"><?= $pendingOnly ? '정산 전인 일이 없어요. 모두 정산했어요.' : e(month_label($month)) . '에 작업한 일이 없어요.' ?></p></section>
<?php endif; ?>
