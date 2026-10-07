<?php
/** 그린청소 검색어 페이지(지역 · 업종별 소개). 변수: $page, $others, $posts, $preview */
$name = gc('name');
$phone = gc('phone');
$base = base_url();
$url = landing_url($page, true);
$desc = blog_desc($page, 160);
$title = $page['seo_title'] !== '' ? $page['seo_title'] : $page['title'] . ' | ' . $name;
$img = $page['cover'] !== '' ? $page['cover'] : blog_image($page);
$imgAbs = $img !== '' ? (preg_match('~^https?://~', $img) ? $img : $base . $img) : $base . '/assets/green-og.jpg';
$kind = array_key_exists($page['kind'], CLEANING_KINDS) ? $page['kind'] : 'office';
$scope = cleaning_scope()[$kind];
$meta = array(
    'title' => $title, 'desc' => $desc, 'canonical' => $url, 'image' => $imgAbs, 'modified' => $page['updated_at'],
    'ld' => array(
        array(
            '@context' => 'https://schema.org', '@type' => 'Service', 'name' => $page['title'], 'serviceType' => $page['title'],
            'description' => $desc, 'url' => $url, 'image' => $imgAbs,
            'areaServed' => array_map(function ($a) {
                return array('@type' => 'AdministrativeArea', 'name' => $a);
            }, array('충청북도 음성군', '충청북도 진천군', '충북혁신도시', '충청북도 청주시 오창읍', '경기도 안성시')),
            'provider' => array('@type' => 'LocalBusiness', '@id' => $base . '/#business', 'name' => $name, 'url' => $base . '/', 'telephone' => $phone),
        ),
        array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array(
            array('@type' => 'ListItem', 'position' => 1, 'name' => '홈', 'item' => $base . '/'),
            array('@type' => 'ListItem', 'position' => 2, 'name' => $page['title'], 'item' => $url),
        )),
    ),
);
$nav = array('services' => '서비스', 'process' => '작업 과정', 'scope' => '청소 범위', 'regions' => '서비스 지역', 'faq' => 'FAQ');
$quote = '/?kind=' . $kind . '#quote';
?>
<?= view('cleaning/_head', array('meta' => $meta)) ?>
<?= view('cleaning/_header', array('nav' => $nav, 'onHome' => false)) ?>
<main id="main" class="g-article-wrap g-landing">
  <div class="g-wrap">
    <article class="g-article">
<?php if ($preview): ?>      <p class="g-preview-note">관리자 미리보기예요. 숨김 상태라 다른 사람에게는 보이지 않아요.</p>
<?php endif; ?>
      <header class="g-article-head">
        <ol class="g-crumbs"><li><a href="/">홈</a></li><li><a href="/#areas">지역 · 업종별 청소</a></li></ol>
        <h1><?= e($page['title']) ?></h1>
<?php if ($page['summary'] !== ''): ?>        <p class="g-landing-lead"><?= e($page['summary']) ?></p>
<?php endif; ?>
        <div class="g-cta-btns g-landing-btns"><a class="g-btn-green" href="<?= e($quote) ?>">무료 견적 문의</a><a class="g-btn-outline" href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></div>
      </header>
<?php if ($page['cover'] !== ''): ?>
      <figure class="g-article-cover"><img src="<?= e($page['cover']) ?>" alt="<?= e($page['title']) ?>"></figure>
<?php endif; ?>
      <div class="g-prose"><?= blog_render($page['body']) ?></div>

      <section class="g-landing-box" aria-labelledby="l-scope">
        <h2 id="l-scope"><?= e(CLEANING_KINDS[$kind]) ?> 정기청소 기본 범위</h2>
        <ul class="g-landing-scope">
<?php foreach ($scope as $item): ?>          <li><?= e($item) ?></li>
<?php endforeach; ?>
        </ul>
        <p class="g-landing-note">현장을 보고 더하거나 뺄 곳을 함께 정해요. 청소 장비와 세제는 저희가 준비합니다.</p>
      </section>

      <section class="g-landing-box" aria-labelledby="l-steps">
        <h2 id="l-steps">진행 순서</h2>
        <ol class="g-landing-steps">
<?php foreach (cleaning_steps() as $i => $s): ?>          <li><span><?= $i + 1 ?></span><div><strong><?= e($s[0]) ?></strong><p><?= e($s[1]) ?></p></div></li>
<?php endforeach; ?>
        </ol>
      </section>

      <aside class="g-article-cta" aria-label="견적 문의">
        <div><strong><?= e($page['title']) ?>, <?= e($name) ?>에 맡겨 보세요</strong><p>현장 방문 견적은 무료예요. 공간 종류와 위치만 알려 주세요.</p></div>
        <div class="g-cta-btns"><a class="g-btn-green" href="<?= e($quote) ?>">무료 견적 문의</a><a class="g-btn-outline" href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></div>
      </aside>

<?php if ($others): ?>
      <nav class="g-landing-others" aria-labelledby="l-others">
        <h2 id="l-others">다른 지역 · 업종 청소</h2>
        <ul>
<?php foreach ($others as $o): ?>          <li><a href="<?= e(landing_url($o)) ?>"><?= e($o['title']) ?></a></li>
<?php endforeach; ?>
          <li><a href="/">충북 음성 청소업체 <?= e($name) ?></a></li>
        </ul>
      </nav>
<?php endif; ?>
<?php if ($posts): ?>
      <section class="g-related" aria-labelledby="l-posts">
        <h2 id="l-posts">청소 이야기</h2>
        <?= view('cleaning/_post_cards', array('posts' => $posts)) ?>
      </section>
<?php endif; ?>
    </article>
  </div>
</main>
<?= view('cleaning/_footer') ?>
