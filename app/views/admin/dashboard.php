<?php /** 관리자 대시보드. */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>대시보드</h1>
  </div>
  <a class="btn btn-primary" href="/admin/books/new">새 전자책 등록</a>
</div>

<?php if ($installerLeft): ?>
<div class="alert" role="alert"><p>설치 도구(www/install.php)가 서버에 남아 있어요. 다른 사람이 쓰지 못하게 FTP로 지워 주세요.</p></div>
<?php endif; ?>

<?php if (!$bankReady || !$bizReady): ?>
<section class="card setup-card">
  <h2>판매 전에 확인해 주세요</h2>
  <ul class="checklist">
    <li class="<?= $bankReady ? 'done' : '' ?>"><a href="/admin/settings#bank">입금 받을 계좌 등록</a><?= $bankReady ? ' · 완료' : ' · 계좌가 없으면 주문을 받을 수 없어요' ?></li>
    <li class="<?= $bizReady ? 'done' : '' ?>"><a href="/admin/settings#biz">사업자 정보 입력</a><?= $bizReady ? ' · 완료' : ' · 온라인 판매 시 화면 하단에 표시해야 해요' ?></li>
  </ul>
</section>
<?php endif; ?>

<div class="stats">
  <a class="stat card" href="/admin/orders?status=pending"><span class="stat-label">입금 대기</span><strong class="stat-value"><?= $pendingCount ?>건</strong></a>
  <div class="stat card"><span class="stat-label">이번 달 매출</span><strong class="stat-value"><?= won($monthSales) ?></strong></div>
  <a class="stat card" href="/admin/books?status=on_sale"><span class="stat-label">판매 중인 전자책</span><strong class="stat-value"><?= $bookCount ?>권</strong></a>
  <a class="stat card" href="/admin/reviews"><span class="stat-label">최근 7일 리뷰</span><strong class="stat-value"><?= $reviewCount ?>개</strong></a>
</div>

<section class="card">
  <div class="card-head">
    <h2>입금 확인이 필요한 주문</h2>
    <a href="/admin/orders?status=pending">전체 보기</a>
  </div>
<?php if ($pending): ?>
  <?= view('admin/_order_rows', array('orders' => $pending, 'back' => '/admin')) ?>
<?php else: ?>
  <p class="muted">입금을 기다리는 주문이 없어요.</p>
<?php endif; ?>
</section>
