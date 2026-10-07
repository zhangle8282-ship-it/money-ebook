<?php /** 관리자 › 일회성 정산 › 새 일 / 고치기. 변수: $job, $form, $errors, $locked(정산 완료라 못 고침) */
$action = $job ? '/admin/onetime/' . (int) $job['id'] . '/edit' : '/admin/onetime/new';
$fee = $form['fee'] !== '' ? number_format((int) $form['fee']) : '';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1><?= $job ? '일회성 정산 고치기' : '새 일회성 정산' ?></h1>
    <p class="muted">입주청소 · 대청소처럼 한 번 하는 일의 수익을 나눠요. <b>수수료 방식</b>(청소 담당에게 수수료 10 · 20%를 빼고 줌)과 <b>인수 방식</b>(청소 금액 전체를 대표 · 운영이 5:5) 중에서 골라요. 세금계산서를 발행하면 세금 <?= CONTRACT_TAX_RATE ?>%를 먼저 빼고 나눠요. 달마다가 아니라 이 일 한 번만 정산해요.</p>
  </div>
<?php if (!$locked): ?>  <button type="submit" form="onetime-form" class="btn btn-primary"><?= $job ? '저장하기' : '추가하기' ?></button>
<?php endif; ?>
</div>
<?php if ($locked): ?>
<div class="notice-box"><p>정산 완료한 일이라 고칠 수 없어요. 고치려면 <a href="/admin/onetime?month=<?= e(substr($form['work_date'], 0, 7)) ?>">일회성 정산 목록</a>에서 ‘되돌리기’를 누른 뒤 고쳐 주세요.</p></div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" id="onetime-form" class="form-grid contract-grid" data-contract-form>
  <?= csrf_field() ?>
  <div class="form-main">
    <section class="card stack-lg" aria-labelledby="of-basic">
      <h2 id="of-basic">일 정보</h2>
      <div class="grid-2">
        <div class="field"><label for="of-name">일 이름</label><input id="of-name" name="name" type="text" maxlength="100" required value="<?= e($form['name']) ?>" placeholder="예: 금왕 ○○상가 입주청소"></div>
        <div class="field"><label for="of-client">고객 (선택)</label><input id="of-client" name="client" type="text" maxlength="100" value="<?= e($form['client']) ?>" placeholder="예: 김○○ 님, ○○부동산"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label for="of-date">작업일</label><input id="of-date" name="work_date" type="date" required value="<?= e($form['work_date']) ?>"></div>
        <div class="field">
          <label for="of-fee">청소비용</label>
          <div class="input-suffix"><input id="of-fee" name="fee" type="text" inputmode="numeric" required value="<?= e($fee) ?>" placeholder="0" data-money data-calc-input="fee"><span>원</span></div>
        </div>
      </div>
      <div class="field">
        <span class="field-label-strong">세금계산서 <span class="sub">(일회성은 보통 발행 안 함 · 원하면 발행)</span></span>
        <fieldset class="segmented">
          <legend class="sr-only">세금계산서 발행</legend>
          <input type="radio" id="cf-inv-1" name="invoice" value="1" class="sr-only" data-calc-input="invoice"<?= (int) $form['invoice'] ? ' checked' : '' ?>><label for="cf-inv-1">발행 (세금 <?= CONTRACT_TAX_RATE ?>% 빠짐)</label>
          <input type="radio" id="cf-inv-0" name="invoice" value="0" class="sr-only" data-calc-input="invoice"<?= (int) $form['invoice'] ? '' : ' checked' ?>><label for="cf-inv-0">발행 안 함</label>
        </fieldset>
      </div>
      <div class="field">
        <span class="field-label-strong">일하는 방식</span>
        <fieldset class="segmented">
          <legend class="sr-only">일하는 방식</legend>
<?php foreach (ONETIME_METHODS as $key => $label): ?>
          <input type="radio" id="of-method-<?= $key ?>" name="method" value="<?= $key ?>" class="sr-only" data-calc-input="method"<?= ($form['method'] ?? 'commission') === $key ? ' checked' : '' ?>><label for="of-method-<?= $key ?>"><?= e($label) ?></label>
<?php endforeach; ?>
        </fieldset>
      </div>
      <div class="field method-box" data-method-show="commission">
        <span class="field-label-strong">수수료 (대표 · 운영 파트너 몫)</span>
        <fieldset class="segmented">
          <legend class="sr-only">수수료</legend>
<?php foreach (ONETIME_RATES as $r): ?>
          <input type="radio" id="cf-rate-<?= $r ?>" name="contract_rate" value="<?= $r ?>" class="sr-only" data-calc-input="rate"<?= (int) $form['contract_rate'] === $r || (!in_array((int) $form['contract_rate'], ONETIME_RATES, true) && $r === 20) ? ' checked' : '' ?>><label for="cf-rate-<?= $r ?>"><?= $r ?>%</label>
<?php endforeach; ?>
        </fieldset>
        <p class="field-help">예: 50만원 · 수수료 20% → 청소 담당에게 <b>40만원</b>, 수수료 10만원은 대표 · 운영이 <b>5만원씩</b>. 수수료는 회사가 청소 담당에게 줄 돈에서 떼는 몫이에요. 청소비용(세금계산서를 발행하면 세금을 뺀 금액)에서 수수료는 대표 · 운영 파트너가 <b><?= ONETIME_GAP ?> : <?= 100 - ONETIME_GAP ?></b>으로 나누고, 나머지 <strong data-calc="byeong_rate"><?= 100 - (int) $form['contract_rate'] ?></strong>%는 청소 담당이 받아요.</p>
      </div>
      <div class="field method-box" data-method-show="takeover">
        <p class="method-note">인수 방식은 <b>청소 담당 몫 없이</b> 청소 금액(세금계산서를 발행하면 세금 뺀 금액) <b>전체를 대표 · 운영이 <?= ONETIME_GAP ?> : <?= 100 - ONETIME_GAP ?></b>으로 나눠요. 청소 담당 · 원천징수 칸은 쓰지 않아요.</p>
      </div>
    </section>

    <?= view('admin/_split_fields', array('form' => $form, 'fixedGap' => ONETIME_GAP)) ?>

    <section class="card stack-lg" aria-labelledby="of-memo">
      <h2 id="of-memo">메모</h2>
      <div class="field"><label class="sr-only" for="of-memo-text">메모</label><textarea id="of-memo-text" name="memo" rows="3" maxlength="1000" placeholder="예: 34평 아파트, 베란다 · 새시 포함"><?= e($form['memo']) ?></textarea></div>
    </section>
<?php if ($job && !$locked): ?>
    <p class="danger-zone"><button type="submit" form="onetime-delete" class="btn btn-danger btn-sm">이 일 지우기</button></p>
<?php endif; ?>
  </div>

  <aside class="form-side">
    <section class="card stack calc-card" aria-labelledby="of-calc">
      <h2 id="of-calc">이번 일 정산 미리보기</h2>
      <dl class="calc">
        <div><dt>청소비용</dt><dd data-calc="fee">0원</dd></div>
        <div class="minus"><dt>세금 <?= CONTRACT_TAX_RATE ?>% (세금계산서)</dt><dd data-calc="tax">− 0원</dd></div>
        <div class="total"><dt>세금 뺀 금액</dt><dd data-calc="after_tax">0원</dd></div>
        <div class="split byeong-row" data-byeong-field><dt>청소 담당 <span data-calc="byeong_rate2">80</span>%<small>몫 <span data-calc="byeong">0원</span> − 원천징수 <?= CONTRACT_WITHHOLDING ?>% <span data-calc="withholding">0원</span><br>(소득세 3% <span data-calc="income_tax">0</span> + 지방세 0.3% <span data-calc="local_tax">0</span>)</small></dt><dd data-calc="byeong_pay">0원</dd></div>
        <div class="split"><dt>회사 몫 (대표 · 운영) <span data-calc="rate">20</span>%</dt><dd data-calc="contract">0원</dd></div>
        <div class="split gap-row sub-row"><dt>└ 대표파트너 <span data-calc="gap_rate">60</span>%</dt><dd data-calc="gap">0원</dd></div>
        <div class="split eul-row sub-row"><dt>└ 운영파트너 <span data-calc="eul_rate2">40</span>%</dt><dd data-calc="eul">0원</dd></div>
      </dl>
    </section>
  </aside>
</form>
<?php if ($job && !$locked): ?>
<form method="post" action="/admin/onetime/<?= (int) $job['id'] ?>/delete" id="onetime-delete" data-confirm="‘<?= e($job['name']) ?>’을(를) 지울까요? 되돌릴 수 없어요."><?= csrf_field() ?></form>
<?php endif; ?>
