<?php /** 관리자 › 정기청소 정산 › 파트너 · 계좌. 변수: $groups, $usage, $form, $errors */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>정기청소 정산</h1>
    <p class="muted">대표 · 운영 · 청소 담당 파트너의 지급 계좌를 등록해요. 청소마다 담당 파트너를 고르면, 월별 정산의 지급 단계에 보낼 계좌가 바로 나와요.</p>
  </div>
  <a class="btn btn-primary" href="#partner-add">+ 파트너 추가</a>
</div>
<?= view('admin/_contracts_tabs', array('tab' => 'partners')) ?>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<div class="partner-board">
<?php foreach (CONTRACT_ROLE_SIDES as $role => $label): $list = $groups[$role]; ?>
  <section class="card role-card role-<?= $role ?>" aria-labelledby="pg-<?= $role ?>">
    <div class="role-head">
      <span class="role-badge" title="<?= e($label) ?>"><?= e(CONTRACT_ROLE_SHORT[$role]) ?></span>
      <div><h2 id="pg-<?= $role ?>"><?= e($label) ?></h2><p class="sub"><?= count($list) ?>명</p></div>
    </div>
<?php if ($list): ?>
    <ul class="partner-list">
<?php foreach ($list as $p): ?>
      <li>
        <div class="partner-top">
          <strong><?= e($p['name']) ?></strong>
<?php if (!empty($usage[(int) $p['id']])): ?>          <span class="sub">청소 <?= (int) $usage[(int) $p['id']] ?>곳 담당</span>
<?php endif; ?>
          <a class="partner-edit" href="/admin/contracts/partners/<?= (int) $p['id'] ?>/edit">고치기</a>
        </div>
<?php if ($p['bank_account'] !== ''): ?>
        <div class="partner-account">
          <span class="bank-chip"><?= e($p['bank_name']) ?></span>
          <span class="mono" id="acc-<?= (int) $p['id'] ?>"><?= e($p['bank_account']) ?></span>
          <span class="sub">예금주 <?= e($p['bank_holder'] !== '' ? $p['bank_holder'] : $p['name']) ?></span>
          <button type="button" class="icon-btn" data-copy-text="<?= e($p['bank_name'] . ' ' . $p['bank_account'] . ' ' . ($p['bank_holder'] !== '' ? $p['bank_holder'] : $p['name'])) ?>">복사</button>
        </div>
<?php else: ?>
        <p class="sub warn-text">계좌를 아직 넣지 않았어요</p>
<?php endif; ?>
<?php if ($p['phone'] !== '' || $p['memo'] !== ''): ?>        <p class="sub"><?= $p['phone'] !== '' ? '<a href="' . e(tel_href($p['phone'])) . '">' . e($p['phone']) . '</a>' : '' ?><?= $p['phone'] !== '' && $p['memo'] !== '' ? ' · ' : '' ?><?= e($p['memo']) ?></p>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="muted role-empty">아직 등록한 <?= e($label) ?>가 없어요.</p>
<?php endif; ?>
    <a class="btn btn-outline btn-sm" href="/admin/contracts/partners?role=<?= $role ?>#partner-add">+ <?= e(CONTRACT_ROLE_SHORT[$role]) ?> 파트너 추가</a>
  </section>
<?php endforeach; ?>
</div>
<form method="post" action="/admin/contracts/partners" class="card stack-lg" id="partner-add" aria-labelledby="pa-title">
  <?= csrf_field() ?>
  <h2 id="pa-title">파트너 추가</h2>
  <?= view('admin/_partner_fields', array('form' => $form)) ?>
  <div><button type="submit" class="btn btn-primary">파트너 추가하기</button></div>
</form>
