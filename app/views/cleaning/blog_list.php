<?php /** 그린청소 블로그 목록. 변수: $posts, $page, $pages, $total */
$name = gc('name');
$url = base_url() . '/blog' . ($page > 1 ? '?page=' . $page : '');
$meta = array(
    'title' => $name . ' 블로그 · 충북 음성 청소 이야기' . ($page > 1 ? ' (' . $page . '쪽)' : ''),
    'desc' => '충북음성청소업체 ' . $name . '가 전하는 사무실 정기청소, 공장·상가·화장실 청소 이야기와 현장 팁. 금왕·대소·진천·충북혁신도시 청소 사례를 확인하세요.',
    'canonical' => $url,
    'ld' => array(
        array('@context' => 'https://schema.org', '@type' => 'Blog', 'name' => $name . ' 블로그', 'url' => base_url() . '/blog',
            'publisher' => array('@type' => 'Organization', 'name' => $name, 'url' => base_url() . '/'),
            'blogPost' => array_map(function ($p) {
                return array('@type' => 'BlogPosting', 'headline' => $p['title'], 'url' => blog_url($p, true), 'datePublished' => date('c', strtotime($p['published_at'])));
            }, $posts)),
        array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array(
            array('@type' => 'ListItem', 'position' => 1, 'name' => '홈', 'item' => base_url() . '/'),
            array('@type' => 'ListItem', 'position' => 2, 'name' => '블로그', 'item' => base_url() . '/blog'),
        )),
    ),
);
$nav = array('services' => '서비스', 'process' => '작업 과정', 'scope' => '청소 범위', 'regions' => '서비스 지역', 'faq' => 'FAQ');
?>
<?= view('cleaning/_head', array('meta' => $meta)) ?>
<?= view('cleaning/_header', array('nav' => $nav, 'onHome' => false, 'current' => 'blog')) ?>
<main id="main">
  <section class="g-blog-head">
    <div class="g-wrap">
      <ol class="g-crumbs"><li><a href="/">홈</a></li><li>블로그</li></ol>
      <h1><?= e($name) ?> 블로그</h1>
      <p>충북 음성 · 진천 · 혁신도시 현장에서 전하는 청소 이야기와 관리 팁</p>
    </div>
  </section>
  <section class="g-blog-list">
    <div class="g-wrap">
<?php if ($posts): ?>
      <?= view('cleaning/_post_cards', array('posts' => $posts)) ?>
<?php if ($pages > 1): ?>
      <nav class="g-pager" aria-label="쪽 이동">
<?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i === $page): ?><span aria-current="page"><?= $i ?></span><?php else: ?><a href="/blog<?= $i > 1 ? '?page=' . $i : '' ?>"><?= $i ?></a><?php endif; ?>
<?php endfor; ?>
      </nav>
<?php endif; ?>
<?php else: ?>
      <p class="g-empty">아직 올라온 글이 없어요.</p>
<?php endif; ?>
    </div>
  </section>
</main>
<?= view('cleaning/_footer') ?>
