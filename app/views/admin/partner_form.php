<?php /** 관리자 › 도급 정산 › 파트너 고치기. 변수: $partner, $form, $errors */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>파트너 고치기</h1>
    <p class="muted">이름 · 역할 · 지급 계좌를 바꿔요. 바꾸면 이 파트너를 고른 모든 청소에 바로 반영돼요.</p>
  </div>
</div>
<?= view('admin/_contracts_tabs', array('tab' => 'partners')) ?>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="/admin/contracts/partners/<?= (int) $partner['id'] ?>/edit" class="card stack-lg partner-form">
  <?= csrf_field() ?>
  <?= view('admin/_partner_fields', array('form' => $form)) ?>
  <div class="form-actions-row">
    <button type="submit" class="btn btn-primary">저장하기</button>
    <a class="btn btn-ghost" href="/admin/contracts/partners">목록으로</a>
    <button type="submit" form="partner-delete" class="btn btn-danger btn-sm push-right">이 파트너 지우기</button>
  </div>
</form>
<form method="post" action="/admin/contracts/partners/<?= (int) $partner['id'] ?>/delete" id="partner-delete" data-confirm="‘<?= e($partner['name']) ?>’을(를) 지울까요? 맡았던 청소에는 이름만 남아요."><?= csrf_field() ?></form>
