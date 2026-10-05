<?php /** 그린청소 관리자 › 계정(비밀번호 바꾸기). 변수: $admin, $errors */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>계정</h1>
    <p class="muted">관리자 아이디 <strong><?= e($admin['username']) ?></strong>의 비밀번호를 바꿔요.</p>
  </div>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="/admin/account" class="card stack-lg account-form" aria-labelledby="pw-title">
  <?= csrf_field() ?>
  <h2 id="pw-title">비밀번호 바꾸기</h2>
  <div class="field"><label for="pw-now">지금 비밀번호</label><input id="pw-now" name="current_password" type="password" required autocomplete="current-password"></div>
  <div class="field"><label for="pw-new">새 비밀번호 (10자 이상)</label><input id="pw-new" name="new_password" type="password" required minlength="10" autocomplete="new-password"></div>
  <div class="field"><label for="pw-new2">새 비밀번호 확인</label><input id="pw-new2" name="new_password2" type="password" required minlength="10" autocomplete="new-password"></div>
  <div><button type="submit" class="btn btn-primary">비밀번호 바꾸기</button></div>
</form>
