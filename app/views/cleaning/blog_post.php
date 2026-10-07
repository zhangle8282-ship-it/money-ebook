<?php /** 그린청소 블로그 글. 변수: $post, $related, $preview */
$name = gc('name');
$url = blog_url($post, true);
$img = blog_image($post);
$imgAbs = $img !== '' ? (preg_match('~^https?://~', $img) ? $img : base_url() . $img) : base_url() . '/assets/green-og.jpg';
$desc = blog_desc($post);
$title = $post['seo_title'] !== '' ? $post['seo_title'] : $post['title'] . ' | ' . $name;
$heads = blog_headings($post);
$published = $post['published_at'] ?: $post['created_at'];
$meta = array(
    'title' => $title, 'desc' => $desc, 'canonical' => $url, 'type' => 'article', 'image' => $imgAbs,
    'published' => $published, 'modified' => $post['updated_at'],
    'ld' => array(
        array('@context' => 'https://schema.org', '@type' => 'BlogPosting', 'mainEntityOfPage' => $url, 'headline' => $post['title'],
            'description' => $desc, 'image' => array($imgAbs), 'datePublished' => date('c', strtotime($published)), 'dateModified' => date('c', strtotime($post['updated_at'])),
            'keywords' => $post['keywords'], 'inLanguage' => 'ko',
            'author' => array('@type' => 'Organization', 'name' => $name, 'url' => base_url() . '/'),
            'publisher' => array('@type' => 'Organization', 'name' => $name, 'logo' => array('@type' => 'ImageObject', 'url' => base_url() . '/icons/green-icon-180.png'))),
        array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array(
            array('@type' => 'ListItem', 'position' => 1, 'name' => '홈', 'item' => base_url() . '/'),
            array('@type' => 'ListItem', 'position' => 2, 'name' => '블로그', 'item' => base_url() . '/blog'),
            array('@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $url),
        )),
    ),
);
$nav = array('services' => '서비스', 'process' => '작업 과정', 'scope' => '청소 범위', 'regions' => '서비스 지역', 'faq' => 'FAQ');
// 목차에서 소제목으로 바로 가도록 본문 h2 에 번호를 붙입니다.
$n = 0;
$body = preg_replace_callback('/<h2(?=[\s>])/', function () use (&$n) {
    return '<h2 id="s' . (++$n) . '"';
}, blog_body_html($post));
// 본문 가운데에 청소 범위 상자, 글 끝에는 견적 문의 칸(채팅 상담 안내 포함)
list($bodyTop, $bodyRest) = blog_split_middle($body);
$kind = blog_guess_kind($post);
$channelKey = gc('channeltalk_key');
$kakao = gc('kakao_url');
$phone = gc('phone');
?>
<?= view('cleaning/_head', array('meta' => $meta)) ?>
<?= view('cleaning/_header', array('nav' => $nav, 'onHome' => false, 'current' => 'blog')) ?>
<main id="main" class="g-article-wrap">
  <div class="g-wrap">
    <article class="g-article">
<?php if ($preview): ?>      <p class="g-preview-note">관리자 미리보기예요. 아직 공개되지 않은 글이라 다른 사람에게는 보이지 않아요.</p>
<?php endif; ?>
      <header class="g-article-head">
        <ol class="g-crumbs"><li><a href="/">홈</a></li><li><a href="/blog">블로그</a></li></ol>
        <h1><?= e($post['title']) ?></h1>
        <p class="g-article-meta"><span><?= e($name) ?></span></p>
      </header>
<?php if ($post['cover'] !== ''): ?>
      <figure class="g-article-cover"><img src="<?= e($post['cover']) ?>" alt="<?= e($post['title']) ?>"></figure>
<?php endif; ?>
<?php if (count($heads) >= 3): ?>
      <nav class="g-toc" aria-label="목차"><strong>목차</strong><ol><?php foreach ($heads as $i => $h): ?><li><a href="#s<?= $i + 1 ?>"><?= e($h) ?></a></li><?php endforeach; ?></ol></nav>
<?php endif; ?>
      <div class="g-prose"><?= $bodyTop ?></div>
      <?= view('cleaning/_scope_box', array('kind' => $kind)) ?>
<?php if ($bodyRest !== ''): ?>
      <div class="g-prose"><?= $bodyRest ?></div>
<?php endif; ?>
      <section class="g-post-quote" id="quote" aria-labelledby="pq-title">
        <div class="g-post-quote-text">
          <span class="g-kicker">— 무료 견적 —</span>
          <h2 id="pq-title">우리 공간도 맡겨 보세요</h2>
          <p>음성 · 진천 · 충북혁신도시 사무실 · 상가 · 공장 · 화장실 정기청소. 현장 방문 견적은 무료이고, 남겨 주시면 영업일 하루 안에 연락드려요.</p>
          <ul class="g-post-ways">
<?php if ($channelKey !== ''): ?>
            <li class="g-way-chat"><span class="g-way-icon" aria-hidden="true">💬</span><div><strong>채팅 상담으로도 견적 문의할 수 있어요</strong><p>오른쪽 아래 ‘채팅 상담’을 누르면 바로 물어볼 수 있어요. 사진을 보내 주시면 더 정확해요.</p><a class="g-post-chat-btn" href="#" data-open-chat>채팅 상담 열기</a></div></li>
<?php endif; ?>
            <li><span class="g-way-icon" aria-hidden="true">📞</span><div><strong>전화 상담</strong><p><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></p></div></li>
<?php if ($kakao !== ''): ?>
            <li><span class="g-way-icon" aria-hidden="true">🟡</span><div><strong>카카오톡 상담</strong><p><a href="<?= e($kakao) ?>" target="_blank" rel="noopener">카카오톡 채널로 문의하기</a></p></div></li>
<?php endif; ?>
          </ul>
        </div>
        <?= view('cleaning/_quote_card', array('form' => array('kind' => $kind))) ?>
      </section>
<?php if ($related): ?>
      <section class="g-related" aria-labelledby="related-title">
        <h2 id="related-title">다른 청소 이야기</h2>
        <?= view('cleaning/_post_cards', array('posts' => $related)) ?>
      </section>
<?php endif; ?>
    </article>
  </div>
</main>
<?= view('cleaning/_footer') ?>
