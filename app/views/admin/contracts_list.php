<?php /** 관리자 › 도급 정산 › 청소 목록. 변수: $contracts */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>도급 정산</h1>
    <p class="muted">등록한 청소와 계약 조건이에요. 금액은 한 달 기준이에요. 이름을 누르면 고칠 수 있고, 끝난 청소는 ‘끝난 월’을 정하면 그 다음 달부터 정산표에서 빠져요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/contracts/new">+ 새 청소</a>
</div>
<?= view('admin/_contracts_tabs', array('tab' => 'list')) ?>
<section class="card flush">
<?php if ($contracts): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">청소</th><th scope="col" class="num">월 청소비용</th><th scope="col">조건</th><th scope="col" class="num">청소 담당 실지급</th><th scope="col" class="num">대표파트너</th><th scope="col" class="num">운영파트너</th><th scope="col">기간</th><th scope="col"><span class="sr-only">상태</span></th></tr></thead>
    <tbody>
<?php foreach ($contracts as $c): ?>
      <tr>
        <td><a class="strong" href="/admin/contracts/<?= (int) $c['id'] ?>/edit"><?= e($c['name']) ?></a><?php if ($c['client'] !== ''): ?><div class="sub"><?= e($c['client']) ?></div><?php endif; ?></td>
        <td class="num"><?= won($c['monthly_fee']) ?></td>
        <td class="nowrap">세금 <?= $c['invoice'] ? CONTRACT_TAX_RATE . '%' : '없음' ?> · 청소 담당 <?= 100 - (int) $c['contract_rate'] ?>% · 도급 <?= (int) $c['contract_rate'] ?>%<div class="sub">대표:운영 <?= (int) $c['gap_rate'] ?>:<?= 100 - (int) $c['gap_rate'] ?> · 원천징수 <?= (int) $c['withholding'] ? CONTRACT_WITHHOLDING . '%' : '없음' ?></div></td>
        <td class="num"><?= won($c['calc']['byeong_pay']) ?><?php if ($c['byeong_name'] !== ''): ?><div class="sub"><?= e($c['byeong_name']) ?></div><?php endif; ?></td>
        <td class="num"><?= won($c['calc']['gap_amount']) ?><?php if ($c['gap_name'] !== ''): ?><div class="sub"><?= e($c['gap_name']) ?></div><?php endif; ?></td>
        <td class="num"><?= won($c['calc']['eul_amount']) ?><?php if ($c['eul_name'] !== ''): ?><div class="sub"><?= e($c['eul_name']) ?></div><?php endif; ?></td>
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
