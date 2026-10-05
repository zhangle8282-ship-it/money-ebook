<?php /** 관리자 › 도급 정산 탭. 변수: $tab */ ?>
<nav class="tabs big-tabs contract-tabs" aria-label="도급 정산 메뉴">
  <a href="/admin/contracts"<?= $tab === 'month' ? ' aria-current="page"' : '' ?>>월별 정산</a>
  <a href="/admin/contracts/list"<?= $tab === 'list' ? ' aria-current="page"' : '' ?>>청소 목록</a>
  <a href="/admin/contracts/roles"<?= $tab === 'roles' ? ' aria-current="page"' : '' ?>>갑 · 을 역할</a>
  <a href="/admin/contracts/new"<?= $tab === 'new' ? ' aria-current="page"' : '' ?>>+ 새 청소</a>
</nav>
