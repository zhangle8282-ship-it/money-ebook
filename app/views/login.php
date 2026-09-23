<?php /** 회원 로그인. */ ?>
<section class="auth wrap-narrow">
  <h1 class="page-title">로그인</h1>
<?php if ($error): ?>
  <div class="alert" role="alert"><p><?= e($error) ?></p></div>
<?php endif; ?>
  <form method="post" action="/login" class="auth-form">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="field">
      <label for="email">이메일</label>
      <input id="email" name="email" type="email" required autocomplete="email" value="<?= e($email) ?>">
    </div>
    <div class="field">
      <label for="password">비밀번호</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block">로그인</button>
  </form>
  <p class="auth-switch">처음이신가요? <a href="/signup?next=<?= e(rawurlencode($next)) ?>">회원가입</a></p>
</section>
