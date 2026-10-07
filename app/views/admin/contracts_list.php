<?php /** 관리자 › 도급 정산 › 청소 목록. 변수: $contracts */
$cleaners = partners_by_role()['byeong'];
$pickWorkers = workers_for_pick();
$back = $_SERVER['REQUEST_URI'] ?? '/admin/contracts/list';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>도급 정산</h1>
    <p class="muted">등록한 청소와 계약 조건이에요. 금액은 한 달 기준이에요. 이름을 누르면 고칠 수 있고, 끝난 청소는 ‘끝난 월’을 정하면 그 다음 달부터 정산표에서 빠져요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/contracts/new">+ 새 청소</a>
</div>
<?= view('admin/_contracts_tabs', array('tab' => 'list')) ?>
<?php if ($contracts): ?>
<p class="muted small">‘청소 담당’ 칸에서 <a href="/admin/workers">인력 배치</a>에 등록한 사람이나 청소 담당 파트너를 골라 <b>배치</b>를 누르면 바로 배치돼요.</p>
<?php endif; ?>
<section class="card flush">
<?php if ($contracts): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">청소</th><th scope="col">청소 담당</th><th scope="col" class="num">월 청소비용</th><th scope="col">조건</th><th scope="col" class="num">청소 담당 실지급</th><th scope="col" class="num">대표파트너</th><th scope="col" class="num">운영파트너</th><th scope="col">기간</th><th scope="col"><span class="sr-only">상태</span></th></tr></thead>
    <tbody>
<?php foreach ($contracts as $c): ?>
      <tr>
        <td class="contract-name-cell"><a class="strong" href="/admin/contracts/<?= (int) $c['id'] ?>/edit"><?= e($c['name']) ?></a><?php if ($c['client'] !== ''): ?><div class="sub"><?= e($c['client']) ?></div><?php endif; ?></td>
        <td class="assign-cell">
          <form method="post" action="/admin/contracts/<?= (int) $c['id'] ?>/assign" class="assign-form">
            <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
            <select name="byeong" aria-label="‘<?= e($c['name']) ?>’ 청소 담당">
              <option value=""><?= $c['byeong_partner_id'] ? '— 청소 담당 비우기 —' : '— 아직 없음 —' ?></option>
<?php if ($cleaners): ?>              <optgroup label="청소 담당 파트너">
<?php foreach ($cleaners as $p): ?>                <option value="<?= (int) $p['id'] ?>"<?= (int) $c['byeong_partner_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?><?= !empty($p['worker_id']) ? ' · 인력 배치' : '' ?></option>
<?php endforeach; ?>              </optgroup>
<?php endif; ?>
<?php if ($pickWorkers): ?>              <optgroup label="인력 배치에서 고르기">
<?php foreach ($pickWorkers as $w): ?>                <option value="w:<?= (int) $w['id'] ?>"><?= e($w['name']) ?> · <?= e(str_cut($w['regions'], 18)) ?></option>
<?php endforeach; ?>              </optgroup>
<?php endif; ?>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">배치</button>
          </form>
<?php if (!$c['byeong_partner_id'] && $c['byeong_name'] !== ''): ?>          <div class="sub">적어 둔 이름: <?= e($c['byeong_name']) ?></div>
<?php endif; ?>
        </td>
        <td class="num"><?= won($c['monthly_fee']) ?></td>
        <td class="nowrap">세금 <?= $c['invoice'] ? CONTRACT_TAX_RATE . '%' : '없음' ?> · 청소 담당 <?= 100 - (int) $c['contract_rate'] ?>% · 도급 <?= (int) $c['contract_rate'] ?>%<div class="sub">대표:운영 <?= (int) $c['gap_rate'] ?>:<?= 100 - (int) $c['gap_rate'] ?> · 원천징수 <?= (int) $c['withholding'] ? CONTRACT_WITHHOLDING . '%' : '없음' ?></div></td>
        <td class="num"><?= won($c['calc']['byeong_pay']) ?><?php if (contract_partner_name($c, 'byeong') !== ''): ?><div class="sub"><?= e(contract_partner_name($c, 'byeong')) ?></div><?php endif; ?></td>
        <td class="num"><?= won($c['calc']['gap_amount']) ?><?php if (contract_partner_name($c, 'gap') !== ''): ?><div class="sub"><?= e(contract_partner_name($c, 'gap')) ?></div><?php endif; ?></td>
        <td class="num"><?= won($c['calc']['eul_amount']) ?><?php if (contract_partner_name($c, 'eul') !== ''): ?><div class="sub"><?= e(contract_partner_name($c, 'eul')) ?></div><?php endif; ?></td>
        <td class="nowrap"><?= e(month_label($c['start_month'])) ?> ~ <?= $c['end_month'] ? e(month_label($c['end_month'])) : '' ?><div class="sub">정산 완료 <?= (int) $c['done_count'] ?>달</div></td>
        <td><span class="status <?= $c['active'] ? 'status-paid' : ($c['upcoming'] ? 'status-pending' : 'status-cancelled') ?>"><?= $c['active'] ? '진행 중' : ($c['upcoming'] ? '시작 전' : '끝남') ?></span></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <div class="empty-card"><p>아직 등록한 청소가 없어요.</p><a class="btn btn-primary" href="/admin/contracts/new">+ 첫 청소 추가하기</a></div>
<?php endif; ?>
</section>
