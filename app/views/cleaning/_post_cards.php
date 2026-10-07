<?php /** 블로그 글 카드 목록. 변수: $posts */ ?>
<ul class="g-post-grid">
<?php foreach ($posts as $p): $img = blog_image($p); ?>
  <li>
    <a class="g-post-card" href="<?= e(blog_url($p)) ?>">
      <span class="g-post-thumb"><?php if ($img !== ''): ?><img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy"><?php else: ?><svg viewBox="0 0 66 64" aria-hidden="true"><path d="M47.6 16.4A22 22 0 1 0 54 32H36" fill="none" stroke="#2F7D5C" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/><path d="M50 14C50 7 55 3 62 3C62 10 57 14 50 14Z" fill="#2A2D33"/></svg><?php endif; ?></span>
      <span class="g-post-body">
        <span class="g-post-views">조회 <?= number_format((int) $p['views']) ?></span>
        <h3><?= e($p['title']) ?></h3>
        <p><?= e(blog_desc($p, 80)) ?></p>
      </span>
    </a>
  </li>
<?php endforeach; ?>
</ul>
