<?php /** 관리자 › 도급 정산 › 새 청소 / 고치기. 변수: $contract, $form, $errors */
$months = array();
for ($i = -24; $i <= 24; $i++) {
    $months[] = month_shift(date('Y-m'), $i);
}
foreach (array($form['start_month'], $form['end_month']) as $m) {
    if (valid_month($m) && !in_array($m, $months, true)) {
        $months[] = $m;
    }
}
sort($months);
$action = $contract ? '/admin/contracts/' . (int) $contract['id'] . '/edit' : '/admin/contracts/new';
$fee = $form['monthly_fee'] !== '' ? number_format((int) $form['monthly_fee']) : '';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1><?= $contract ? '청소 고치기' : '새 청소' ?></h1>
    <p class="muted">조건을 넣으면 오른쪽에서 한 달 정산이 바로 계산돼요. 계산 순서: 청소비용 → 세금 <?= CONTRACT_TAX_RATE ?>% → 도급비용 → 갑 · 을.</p>
  </div>
  <button type="submit" form="contract-form" class="btn btn-primary"><?= $contract ? '저장하기' : '추가하기' ?></button>
</div>
<?= view('admin/_contracts_tabs', array('tab' => $contract ? 'list' : 'new')) ?>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" id="contract-form" class="form-grid contract-grid" data-contract-form>
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card stack-lg" aria-labelledby="cf-basic">
      <h2 id="cf-basic">청소 정보</h2>
      <div class="grid-2">
        <div class="field"><label for="cf-name">청소 이름</label><input id="cf-name" name="name" type="text" maxlength="100" required value="<?= e($form['name']) ?>" placeholder="예: 혁신도시 ○○빌딩 화장실"></div>
        <div class="field"><label for="cf-client">거래처 (선택)</label><input id="cf-client" name="client" type="text" maxlength="100" value="<?= e($form['client']) ?>" placeholder="예: ○○관리사무소"></div>
      </div>
      <div class="field">
        <label for="cf-fee">월 청소비용</label>
        <div class="input-suffix"><input id="cf-fee" name="monthly_fee" type="text" inputmode="numeric" required value="<?= e($fee) ?>" placeholder="0" data-money data-calc-input="fee"><span>원</span></div>
      </div>
      <div class="field">
        <span class="field-label-strong">세금계산서</span>
        <fieldset class="segmented">
          <legend class="sr-only">세금계산서 발행</legend>
          <input type="radio" id="cf-inv-1" name="invoice" value="1" class="sr-only" data-calc-input="invoice"<?= (int) $form['invoice'] ? ' checked' : '' ?>><label for="cf-inv-1">발행 (세금 <?= CONTRACT_TAX_RATE ?>% 빠짐)</label>
          <input type="radio" id="cf-inv-0" name="invoice" value="0" class="sr-only" data-calc-input="invoice"<?= (int) $form['invoice'] ? '' : ' checked' ?>><label for="cf-inv-0">발행 안 함</label>
        </fieldset>
      </div>
      <div class="field">
        <span class="field-label-strong">도급비용</span>
        <fieldset class="segmented">
          <legend class="sr-only">도급비용 비율</legend>
<?php foreach (CONTRACT_RATES as $r): ?>
          <input type="radio" id="cf-rate-<?= $r ?>" name="contract_rate" value="<?= $r ?>" class="sr-only" data-calc-input="rate"<?= (int) $form['contract_rate'] === $r ? ' checked' : '' ?>><label for="cf-rate-<?= $r ?>"><?= $r ?>%</label>
<?php endforeach; ?>
        </fieldset>
        <p class="field-help">세금을 뺀 금액에서 이 비율만큼 도급비용으로 빠져요.</p>
      </div>
    </section>

    <section class="card stack-lg" aria-labelledby="cf-split">
      <h2 id="cf-split">갑 · 을 나누기</h2>
      <div class="grid-2">
        <div class="field">
          <label for="cf-gap">갑 비율</label>
          <div class="input-suffix"><input id="cf-gap" name="gap_rate" type="number" min="0" max="100" step="1" value="<?= e((string) ($form['gap_rate'] >= 0 ? $form['gap_rate'] : CONTRACT_GAP_DEFAULT)) ?>" data-calc-input="gap"><span>%</span></div>
          <p class="field-help">을은 나머지 <strong data-calc="eul_rate"><?= 100 - max(0, (int) $form['gap_rate']) ?></strong>%를 받아요. (기본 갑 60 : 을 40)</p>
        </div>
        <div class="field"><span class="field-label-strong">빠르게 고르기</span>
          <div class="quick-rates"><button type="button" class="btn btn-outline btn-sm" data-gap="60">60 : 40</button><button type="button" class="btn btn-outline btn-sm" data-gap="50">50 : 50</button><button type="button" class="btn btn-outline btn-sm" data-gap="70">70 : 30</button></div>
        </div>
        <div class="field"><label for="cf-gapname">갑 이름 (선택)</label><input id="cf-gapname" name="gap_name" type="text" maxlength="60" value="<?= e($form['gap_name']) ?>" placeholder="예: 그린청소"></div>
        <div class="field"><label for="cf-eulname">을 이름 (선택)</label><input id="cf-eulname" name="eul_name" type="text" maxlength="60" value="<?= e($form['eul_name']) ?>" placeholder="예: 김○○ 팀장"></div>
      </div>
    </section>

    <section class="card stack-lg" aria-labelledby="cf-period">
      <h2 id="cf-period">기간 · 메모</h2>
      <div class="grid-2">
        <div class="field"><label for="cf-start">시작 월</label>
          <select id="cf-start" name="start_month"><?php foreach ($months as $m): ?><option value="<?= e($m) ?>"<?= $form['start_month'] === $m ? ' selected' : '' ?>><?= e(month_label($m)) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="cf-end">끝난 월 (선택)</label>
          <select id="cf-end" name="end_month"><option value="">계속 진행 중</option><?php foreach ($months as $m): ?><option value="<?= e($m) ?>"<?= (string) $form['end_month'] === $m ? ' selected' : '' ?>><?= e(month_label($m)) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label for="cf-memo">메모 (선택)</label><textarea id="cf-memo" name="memo" rows="3" maxlength="1000" placeholder="예: 주 3회(월·수·금) 오전 7시"><?= e($form['memo']) ?></textarea></div>
    </section>
<?php if ($contract): ?>
    <p class="danger-zone"><button type="submit" form="contract-delete" class="btn btn-danger btn-sm">이 청소 지우기</button> <span class="sub">정산 완료한 달이 있으면 지울 수 없어요. 그때는 ‘끝난 월’을 정해 주세요.</span></p>
<?php endif; ?>
  </div>

  <aside class="form-side">
    <section class="card stack calc-card" aria-labelledby="cf-calc">
      <h2 id="cf-calc">한 달 정산 미리보기</h2>
      <dl class="calc">
        <div><dt>청소비용</dt><dd data-calc="fee">0원</dd></div>
        <div class="minus"><dt>세금 <?= CONTRACT_TAX_RATE ?>% (세금계산서)</dt><dd data-calc="tax">− 0원</dd></div>
        <div class="minus"><dt>도급비용 <span data-calc="rate">10</span>%<small>세금 뺀 금액 <span data-calc="after_tax">0원</span> 기준</small></dt><dd data-calc="contract">− 0원</dd></div>
        <div class="total"><dt>나눌 금액</dt><dd data-calc="base">0원</dd></div>
        <div class="split gap-row"><dt>갑 <span data-calc="gap_rate">60</span>%</dt><dd data-calc="gap">0원</dd></div>
        <div class="split eul-row"><dt>을 <span data-calc="eul_rate2">40</span>%</dt><dd data-calc="eul">0원</dd></div>
      </dl>
    </section>
  </aside>
</form>
<?php if ($contract): ?>
<form method="post" action="/admin/contracts/<?= (int) $contract['id'] ?>/delete" id="contract-delete" data-confirm="‘<?= e($contract['name']) ?>’ 청소를 지울까요? 정산 전 기록도 함께 지워져요."><?= csrf_field() ?></form>
<?php endif; ?>
