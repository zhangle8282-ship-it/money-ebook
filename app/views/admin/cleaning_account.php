<?php /** 그린청소 관리자 › 계정: 관리자 목록 · 추가 · 지우기 · 권한, 내 비밀번호 바꾸기. 변수: $admin, $admins, $errors, $form, $created, $full(전체 권한인지) */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>계정</h1>
<?php if ($full): ?>    <p class="muted">관리자 아이디를 더 만들거나 지우고, 아이디마다 쓸 수 있는 메뉴(전체 / 업무만)를 정해요. 지금 로그인한 아이디는 <strong><?= e($admin['username']) ?></strong>이에요.</p>
<?php else: ?>    <p class="muted">지금 로그인한 아이디 <strong><?= e($admin['username']) ?></strong>은(는) <b>업무 메뉴</b>(견적 문의 · 정기청소 정산 · 일회성 정산 · 인력 배치)만 쓸 수 있어요. 여기서는 내 비밀번호만 바꿀 수 있어요.</p>
<?php endif; ?>
  </div>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<div class="account-grid">
<?php if ($full): ?>
  <section class="card flush" aria-labelledby="ad-list">
    <div class="card-head pad-head"><h2 id="ad-list">관리자 계정 <span class="sub">(<?= count($admins) ?>명)</span></h2></div>
    <ul class="admin-list">
<?php foreach ($admins as $a): $mine = (int) $a['id'] === (int) $admin['id']; $acc = $a['access'] === 'work' ? 'work' : 'all'; ?>
      <li>
        <span class="admin-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($a['username'], 0, 1))) ?></span>
        <span class="admin-who"><strong><?= e($a['username']) ?></strong><?php if ($mine): ?> <span class="status status-paid">나</span><?php endif; ?> <span class="access-badge access-<?= $acc ?>"><?= e(ADMIN_ACCESS[$acc]) ?></span><span class="sub"><?= e(fmt_date($a['created_at'], 'Y.m.d')) ?> 만듦</span></span>
<?php if (!$mine): ?>
        <span class="admin-actions">
          <form method="post" action="/admin/account" class="access-form">
            <?= csrf_field() ?><input type="hidden" name="form" value="access"><input type="hidden" name="admin_id" value="<?= (int) $a['id'] ?>">
            <label class="sr-only" for="acc-<?= (int) $a['id'] ?>">‘<?= e($a['username']) ?>’ 권한</label>
            <select id="acc-<?= (int) $a['id'] ?>" name="access">
<?php foreach (ADMIN_ACCESS as $k => $label): ?>              <option value="<?= $k ?>"<?= $acc === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">권한 저장</button>
          </form>
          <form method="post" action="/admin/account" data-confirm="관리자 ‘<?= e($a['username']) ?>’을(를) 지울까요? 이 아이디로는 더 이상 로그인할 수 없어요.">
            <?= csrf_field() ?><input type="hidden" name="form" value="delete"><input type="hidden" name="admin_id" value="<?= (int) $a['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm">지우기</button>
          </form>
        </span>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ul>
    <p class="sub pad">‘업무만’ 아이디는 견적 문의 · 정기청소 정산 · 일회성 정산 · 인력 배치만 보고, 홈페이지 관리 메뉴는 보이지도 열리지도 않아요.</p>
  </section>

  <form method="post" action="/admin/account" class="card stack-lg" aria-labelledby="ad-new" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="form" value="create">
    <div class="card-intro"><h2 id="ad-new">관리자 추가</h2><p class="muted">새 아이디가 쓸 수 있는 메뉴를 골라요. 나중에 위 목록에서 바꿀 수 있어요.</p></div>
    <div class="field"><label for="ad-user">새 아이디 (영문·숫자 3~30자)</label><input id="ad-user" name="new_username" type="text" maxlength="30" required pattern="[A-Za-z0-9_.\-]{3,30}" autocomplete="off" value="<?= e($form === 'create' ? $created['username'] : '') ?>"></div>
    <div class="field"><label for="ad-access">권한</label>
      <select id="ad-access" name="access">
<?php foreach (ADMIN_ACCESS as $k => $label): ?>        <option value="<?= $k ?>"<?= $created['access'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="grid-2">
      <div class="field"><label for="ad-pw">새 비밀번호 (10자 이상)</label><input id="ad-pw" name="new_admin_password" type="password" required minlength="10" autocomplete="new-password"></div>
      <div class="field"><label for="ad-pw2">새 비밀번호 확인</label><input id="ad-pw2" name="new_admin_password2" type="password" required minlength="10" autocomplete="new-password"></div>
    </div>
    <div class="field"><label for="ad-me">지금 내 비밀번호 (확인용)</label><input id="ad-me" name="my_password" type="password" required autocomplete="current-password"></div>
    <div><button type="submit" class="btn btn-primary">관리자 만들기</button></div>
  </form>
<?php endif; ?>

  <form method="post" action="/admin/account" class="card stack-lg account-form" aria-labelledby="pw-title">
    <?= csrf_field() ?><input type="hidden" name="form" value="password">
    <h2 id="pw-title">내 비밀번호 바꾸기</h2>
    <div class="field"><label for="pw-now">지금 비밀번호</label><input id="pw-now" name="current_password" type="password" required autocomplete="current-password"></div>
    <div class="field"><label for="pw-new">새 비밀번호 (10자 이상)</label><input id="pw-new" name="new_password" type="password" required minlength="10" autocomplete="new-password"></div>
    <div class="field"><label for="pw-new2">새 비밀번호 확인</label><input id="pw-new2" name="new_password2" type="password" required minlength="10" autocomplete="new-password"></div>
    <div><button type="submit" class="btn btn-outline">비밀번호 바꾸기</button></div>
  </form>
</div>
