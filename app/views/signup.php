<?php /** 회원가입. */ ?>
<section class="auth wrap-narrow">
  <h1 class="page-title">회원가입</h1>
  <p class="muted">가입하면 구매한 전자책을 내 서재에서 언제든 내려받을 수 있어요.</p>
<?php if ($errors): ?>
  <div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
  <form method="post" action="/signup" class="auth-form">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="field">
      <label for="name">이름</label>
      <input id="name" name="name" type="text" maxlength="30" required autocomplete="name" value="<?= e($form['name']) ?>">
    </div>
    <div class="field">
      <label for="email">이메일</label>
      <input id="email" name="email" type="email" maxlength="190" required autocomplete="email" value="<?= e($form['email']) ?>">
    </div>
    <div class="field">
      <label for="password">비밀번호</label>
      <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password" aria-describedby="pw-help">
      <span class="field-help" id="pw-help">8자 이상</span>
    </div>
    <div class="field">
      <label for="password2">비밀번호 확인</label>
      <input id="password2" name="password2" type="password" minlength="8" required autocomplete="new-password">
    </div>
    <label class="check">
      <input type="checkbox" name="agree" value="1" required>
      <span><a href="/terms" target="_blank" rel="noopener">이용약관</a>과 <a href="/privacy" target="_blank" rel="noopener">개인정보처리방침</a>에 동의해요.</span>
    </label>
    <button type="submit" class="btn btn-primary btn-lg btn-block">가입하기</button>
  </form>
  <p class="auth-switch">이미 계정이 있나요? <a href="/login?next=<?= e(rawurlencode($next)) ?>">로그인</a></p>
</section>
