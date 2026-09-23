<?php
/** 주문서: 무통장 입금. */
?>
<section class="page wrap-narrow">
  <h1 class="page-title">주문하기</h1>
<?php if ($errors): ?>
  <div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>

  <h2 class="block-title">주문할 전자책</h2>
  <ul class="line-items">
<?php foreach ($books as $b): ?>
    <li class="line-item<?= $b['blocked'] !== '' ? ' is-blocked' : '' ?>">
      <span class="line-cover" aria-hidden="true"><?= cover_html($b, 'thumb') ?></span>
      <div class="line-body">
        <span class="line-title"><?= e($b['title']) ?></span>
        <span class="line-sub"><?= e($b['author']) ?> · <?= e($b['file_format']) ?></span>
<?php if ($b['blocked'] !== ''): ?>        <span class="line-warn"><?= e($b['blocked']) ?> 이 주문에서 빠져요.</span>
<?php endif; ?>
      </div>
      <span class="line-price"><?= won($b['price']) ?></span>
    </li>
<?php endforeach; ?>
  </ul>
  <div class="total-row"><span>입금하실 금액</span><strong><?= won($total) ?></strong></div>

<?php if ($buyable): ?>
  <form method="post" action="/checkout" class="checkout-form">
    <?= csrf_field() ?>
<?php if ($buy): ?>    <input type="hidden" name="buy" value="<?= (int) $buy ?>">
<?php endif; ?>
    <h2 class="block-title">결제 방법</h2>
    <div class="pay-method">
      <strong>무통장 입금</strong>
<?php if (bank_ready()): ?>
      <span><?= e(setting('bank_name')) ?> <?= e(setting('bank_account')) ?> · 예금주 <?= e(setting('bank_holder')) ?></span>
      <span class="muted">주문 후 <?= (int) setting('deposit_days') ?>일 안에 입금해 주세요. 입금이 확인되면 내 서재에서 바로 내려받을 수 있어요.</span>
<?php else: ?>
      <span class="line-warn">입금 계좌가 아직 등록되지 않아 지금은 주문할 수 없어요.</span>
<?php endif; ?>
    </div>

    <div class="field">
      <label for="depositor">입금자명 <span class="req" aria-hidden="true">*</span></label>
      <input id="depositor" name="depositor" type="text" maxlength="30" required value="<?= e($depositor) ?>" autocomplete="name">
      <span class="field-help">통장에 찍히는 이름과 같아야 입금을 빨리 확인할 수 있어요.</span>
    </div>

    <label class="check">
      <input type="checkbox" name="agree" value="1" required>
      <span>주문 내용을 확인했어요. 전자책은 다운로드한 뒤에는 청약철회가 제한된다는 점에 동의해요. (<a href="/terms" target="_blank" rel="noopener">이용약관</a>)</span>
    </label>

    <button type="submit" class="btn btn-primary btn-lg btn-block"<?= bank_ready() ? '' : ' disabled' ?>><?= won($total) ?> 주문하기</button>
  </form>
<?php else: ?>
  <div class="page-actions"><a class="btn btn-outline" href="/cart">장바구니로 돌아가기</a></div>
<?php endif; ?>
</section>
