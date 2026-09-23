<?php /** 관리자 › 마켓 운영 신청. */
$filters = array('' => '전체') + MARKET_STATUS;
$back = $_SERVER['REQUEST_URI'] ?? '/admin/market';
?>
<?= view('admin/_market_tabs', array('tab' => $tab)) ?>
<div class="toolbar">
  <nav class="tabs" aria-label="신청 상태">
<?php foreach ($filters as $key => $label): ?>
    <a href="/admin/market<?= $key !== '' ? '?status=' . $key : '' ?>"<?= $status === $key || ($key === '' && !array_key_exists($status, MARKET_STATUS)) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
  </nav>
</div>
<section class="card flush">
<?php if ($apps): ?>
  <ul class="review-admin-list">
<?php foreach ($apps as $a): ?>
    <li class="review-admin">
      <div class="review-admin-head">
        <span class="status market-<?= e($a['status']) ?>"><?= e(MARKET_STATUS[$a['status']]) ?></span>
        <strong>마켓 운영 <?= (int) $a['months'] ?>개월 · <?= won($a['total']) ?></strong>
        <span class="sub mono"><?= e($a['app_no']) ?> · <?= e(fmt_date($a['created_at'], 'Y.m.d H:i')) ?></span>
      </div>
      <dl class="admin-dl">
        <div><dt>신청자</dt><dd><?= e($a['user_name'] ?? '(탈퇴)') ?> · <?= e($a['user_email'] ?? '') ?><?= $a['phone'] !== '' ? ' · ' . e($a['phone']) : '' ?></dd></div>
        <div><dt>입금자명</dt><dd><strong><?= e($a['depositor']) ?></strong></dd></div>
        <div><dt>희망 도메인</dt><dd><?= $a['domain'] !== '' ? e($a['domain']) : '<span class="muted">없음(상담 후 결정)</span>' ?></dd></div>
<?php if ($a['market_name'] !== ''): ?>        <div><dt>마켓 이름</dt><dd><?= e($a['market_name']) ?></dd></div>
<?php endif; ?>
        <div><dt>요금</dt><dd>월 <?= won($a['monthly_price']) ?><?= $a['discount'] ? ' (' . (int) $a['discount'] . '% 할인)' : '' ?></dd></div>
        <div><dt>추천인</dt><dd><?= $a['referral_code'] !== '' ? e($a['referral_code']) . ' · ' . e($a['referrer_name'] ?? '') . ' · 수익 ' . won($a['commission']) : '<span class="muted">없음</span>' ?></dd></div>
<?php if ($a['status'] === 'paid'): ?>        <div><dt>운영 기간</dt><dd><strong><?= e(fmt_date($a['starts_at'])) ?> ~ <?= e(fmt_date($a['ends_at'])) ?></strong></dd></div>
<?php endif; ?>
      </dl>
      <form method="post" action="/admin/market/<?= (int) $a['id'] ?>" class="note-form">
        <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="action" value="note">
        <label for="note-<?= (int) $a['id'] ?>" class="sub">신청자에게 보일 안내 메모 (사이트 주소, 관리자 계정 안내 등)</label>
        <textarea id="note-<?= (int) $a['id'] ?>" name="admin_note" rows="2"><?= e($a['admin_note']) ?></textarea>
        <button type="submit" class="btn btn-outline btn-sm">메모 저장</button>
      </form>
      <div class="review-admin-actions">
<?php if ($a['status'] === 'pending'): ?>
        <form method="post" action="/admin/market/<?= (int) $a['id'] ?>" data-confirm="<?= e($a['depositor']) ?> 님의 <?= e(won($a['total'])) ?> 입금을 확인했나요? 오늘부터 <?= (int) $a['months'] ?>개월 운영 기간이 시작돼요.">
          <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="action" value="paid">
          <button type="submit" class="btn btn-primary btn-sm">입금 확인</button>
        </form>
        <form method="post" action="/admin/market/<?= (int) $a['id'] ?>" data-confirm="이 신청을 취소할까요?">
          <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="action" value="cancelled">
          <button type="submit" class="btn btn-ghost btn-sm">취소</button>
        </form>
<?php else: ?>
        <form method="post" action="/admin/market/<?= (int) $a['id'] ?>" data-confirm="입금 대기로 되돌릴까요?<?= $a['status'] === 'paid' ? ' 운영 기간과 추천 수익이 빠져요.' : '' ?>">
          <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="action" value="pending">
          <button type="submit" class="btn btn-ghost btn-sm">되돌리기</button>
        </form>
<?php endif; ?>
      </div>
    </li>
<?php endforeach; ?>
  </ul>
<?php else: ?>
  <p class="muted pad">마켓 운영 신청이 없어요.</p>
<?php endif; ?>
</section>
