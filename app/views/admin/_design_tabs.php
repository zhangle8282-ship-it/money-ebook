<?php /** 관리자 › 디자인 탭. 변수: $tab */
$fontCount = count(uploaded_fonts());
?>
<nav class="tabs big-tabs design-tabs" aria-label="디자인 메뉴">
  <a href="/admin/design"<?= $tab === 'design' ? ' aria-current="page"' : '' ?>>디자인 설정</a>
  <a href="/admin/design/fonts"<?= $tab === 'fonts' ? ' aria-current="page"' : '' ?>>글씨체<?php if ($fontCount): ?> <span class="count"><?= $fontCount ?></span><?php endif; ?></a>
</nav>
