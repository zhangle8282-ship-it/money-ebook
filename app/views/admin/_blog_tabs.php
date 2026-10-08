<?php /** 관리자 › 블로그 탭. 변수: $tab(list | auto | notes | stock) */
$stockLeft = (int) q_value('SELECT COUNT(*) FROM photo_stock WHERE used_post_id IS NULL');
?>
<nav class="tabs big-tabs contract-tabs" aria-label="블로그 메뉴">
  <a href="/admin/blog"<?= $tab === 'list' ? ' aria-current="page"' : '' ?>>글 목록</a>
  <a href="/admin/blog/auto"<?= $tab === 'auto' ? ' aria-current="page"' : '' ?>>자동 글쓰기 <span class="count"><?= gc('auto_on') === '1' ? '켜짐' : '꺼짐' ?></span></a>
  <a href="/admin/blog/notes"<?= $tab === 'notes' ? ' aria-current="page"' : '' ?>>경험 노트 <span class="count"><?= note_left() ?>개</span></a>
  <a href="/admin/blog/stock"<?= $tab === 'stock' ? ' aria-current="page"' : '' ?>>사진 창고 <span class="count"><?= $stockLeft ?>장</span></a>
  <a href="/admin/blog/new">+ 새 글 쓰기</a>
</nav>
