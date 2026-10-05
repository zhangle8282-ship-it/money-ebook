<?php /** 그린청소 관리자 › 견적 문의. 변수: $rows, $status, $counts */
$filters = array('' => '전체') + INQUIRY_STATUS;
$back = $_SERVER['REQUEST_URI'] ?? '/admin/inquiries';
$total = array_sum($counts);
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>견적 문의</h1>
    <p class="muted">홈페이지 ‘무료 견적 문의’로 들어온 문의예요. 연락한 뒤 상태와 메모를 남겨 두세요.</p>
  </div>
  <a class="btn btn-outline" href="/" target="_blank" rel="noopener">홈페이지 보기 ↗</a>
</div>
<?php if (gc('notify_email') === ''): ?>
<div class="notice-box"><p>새 문의가 들어올 때 메일로 받으려면 <a href="/admin/site#notify">홈페이지 관리 › 알림 이메일</a>을 넣어 주세요.</p></div>
<?php else: ?>
<p class="muted small">새 문의는 <strong><?= e(gc('notify_email')) ?></strong>으로도 메일을 보내요. 바꾸거나 시험 메일을 보내려면 <a href="/admin/site#notify">홈페이지 관리</a>에서 하세요.</p>
<?php endif; ?>
<div class="toolbar">
  <nav class="tabs" aria-label="문의 상태">
<?php foreach ($filters as $key => $label): ?>
    <a href="/admin/inquiries<?= $key !== '' ? '?status=' . $key : '' ?>"<?= $status === $key || ($key === '' && !array_key_exists($status, INQUIRY_STATUS)) ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="count"><?= $key === '' ? $total : (int) $counts[$key] ?></span></a>
<?php endforeach; ?>
  </nav>
</div>
<?php if ($rows): ?>
<div class="inquiry-list">
<?php foreach ($rows as $q): ?>
  <article class="card inquiry inquiry-<?= e($q['status']) ?>">
    <div class="inquiry-head">
      <span class="kind-pill"><?= e(CLEANING_KINDS[$q['kind']] ?? $q['kind']) ?></span>
      <strong class="inquiry-name"><?= e($q['name']) ?></strong>
      <span class="status inquiry-status-<?= e($q['status']) ?>"><?= e(INQUIRY_STATUS[$q['status']] ?? $q['status']) ?></span>
      <span class="sub"><?= e(fmt_date($q['created_at'], 'Y.m.d H:i')) ?> 접수</span>
<?php if (!empty($q['mailed']) && isset(INQUIRY_MAILED[$q['mailed']])): ?>      <span class="mail-pill mail-<?= e($q['mailed']) ?>"><?= e(INQUIRY_MAILED[$q['mailed']]) ?></span>
<?php endif; ?>
    </div>
    <dl class="inquiry-info">
      <div><dt>연락처</dt><dd><a class="inquiry-phone" href="<?= e(tel_href($q['phone'])) ?>"><?= e($q['phone']) ?></a></dd></div>
      <div><dt>주소 · 면적</dt><dd><?= $q['address'] !== '' ? e($q['address']) : '<span class="sub">적지 않음</span>' ?></dd></div>
    </dl>
    <form method="post" action="/admin/inquiries/<?= (int) $q['id'] ?>" class="inquiry-form">
      <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
      <label class="sr-only" for="st-<?= (int) $q['id'] ?>">상태</label>
      <select id="st-<?= (int) $q['id'] ?>" name="status">
<?php foreach (INQUIRY_STATUS as $key => $label): ?>
        <option value="<?= $key ?>"<?= $q['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
      <label class="sr-only" for="memo-<?= (int) $q['id'] ?>">메모</label>
      <input id="memo-<?= (int) $q['id'] ?>" type="text" name="memo" maxlength="1000" placeholder="메모 (예: 10/7 방문 견적, 월 20만원 안내)" value="<?= e($q['memo']) ?>">
      <button type="submit" class="btn btn-primary btn-sm">저장</button>
      <button type="submit" name="action" value="delete" class="btn btn-ghost btn-sm" data-confirm-click="<?= e($q['name']) ?> 문의를 완전히 지울까요?">삭제</button>
    </form>
  </article>
<?php endforeach; ?>
</div>
<?php else: ?>
<section class="card empty-card">
  <p><?= $status !== '' ? '이 상태의 문의가 없어요.' : '아직 들어온 견적 문의가 없어요.' ?></p>
  <p class="sub">홈페이지에서 문의를 보내면 여기에 바로 나타나요.</p>
</section>
<?php endif; ?>
