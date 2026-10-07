<?php
/** 무료 견적 문의 칸(첫 화면 · 블로그 글 끝에서 함께 씀). 변수: $sent(접수 완료 화면부터), $form(입력값), $errors */
$phone = gc('phone');
$tel = tel_href($phone);
$sent = !empty($sent);
$form = $form ?? array('kind' => 'office');
$errors = $errors ?? array();
?>
<div class="g-quote-card">
  <div class="g-sent" data-sent role="status"<?= $sent ? '' : ' hidden' ?>>
    <span class="g-sent-mark" aria-hidden="true">✓</span>
    <strong>문의가 접수되었습니다</strong>
    <p>영업일 하루 안에 연락드립니다.<br>급하시면 <a href="<?= e($tel) ?>"><?= e($phone) ?></a>로 전화 주세요.</p>
    <a class="g-link-btn" href="/#quote" data-reset>다시 작성</a>
  </div>
  <form method="post" action="/inquiry" class="g-form" data-inquiry-form novalidate<?= $sent ? ' hidden' : '' ?>>
    <?= csrf_field() ?>
    <div class="g-hp" aria-hidden="true"><label>웹사이트 <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <h2 class="g-form-title">무료 견적 문의</h2>
    <p class="g-form-sub">30초면 끝나요. 현장 방문 견적 무료.</p>
    <fieldset class="g-chips">
      <legend class="g-sr">청소 종류</legend>
<?php foreach (CLEANING_KINDS as $key => $label): ?>
      <input type="radio" name="kind" value="<?= $key ?>" id="kind-<?= $key ?>"<?= ($form['kind'] ?? 'office') === $key ? ' checked' : '' ?>><label for="kind-<?= $key ?>"><?= e($label) ?></label>
<?php endforeach; ?>
    </fieldset>
    <div class="g-field">
      <label class="g-sr" for="q-name">업체명 / 담당자</label>
      <input id="q-name" name="name" type="text" maxlength="60" required placeholder="업체명 / 담당자" autocomplete="organization" value="<?= e($form['name'] ?? '') ?>"<?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?> aria-describedby="err-name">
      <p class="g-err" id="err-name" data-err="name"><?= e($errors['name'] ?? '') ?></p>
    </div>
    <div class="g-field">
      <label class="g-sr" for="q-phone">연락처</label>
      <input id="q-phone" name="phone" type="tel" inputmode="tel" maxlength="30" required placeholder="연락처" autocomplete="tel" value="<?= e($form['phone'] ?? '') ?>"<?= isset($errors['phone']) ? ' aria-invalid="true"' : '' ?> aria-describedby="err-phone">
      <p class="g-err" id="err-phone" data-err="phone"><?= e($errors['phone'] ?? '') ?></p>
    </div>
    <div class="g-field">
      <label class="g-sr" for="q-address">주소 · 면적(평)</label>
      <input id="q-address" name="address" type="text" maxlength="120" placeholder="주소 · 면적(평)" autocomplete="street-address" value="<?= e($form['address'] ?? '') ?>">
    </div>
    <label class="g-agree"><input type="checkbox" name="agree" value="1" required> <span>개인정보 수집·이용에 동의합니다 <a href="/privacy" target="_blank" rel="noopener">자세히</a></span></label>
    <p class="g-err" data-err="agree"><?= e($errors['agree'] ?? '') ?></p>
    <p class="g-err g-err-form" data-err="form" role="alert"><?= e($errors['form'] ?? '') ?></p>
    <button type="submit" class="g-btn-dark">견적 문의 보내기</button>
  </form>
</div>
