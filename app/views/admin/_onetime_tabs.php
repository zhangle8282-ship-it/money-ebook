<?php /** 관리자 › 일회성 정산 탭. 변수: $tab(settle | jobs | roles) */
$jobCount = (int) q_value('SELECT COUNT(*) FROM onetime_jobs');
?>
<nav class="tabs big-tabs contract-tabs" aria-label="일회성 정산 메뉴">
  <a href="/admin/onetime"<?= $tab === 'settle' ? ' aria-current="page"' : '' ?>>정산</a>
  <a href="/admin/onetime/jobs"<?= $tab === 'jobs' ? ' aria-current="page"' : '' ?>>청소 목록 <span class="count"><?= $jobCount ?></span></a>
  <a href="/admin/onetime/roles"<?= $tab === 'roles' ? ' aria-current="page"' : '' ?>>파트너 역할</a>
  <a href="/admin/onetime/new">+ 새 일회성 정산</a>
</nav>
