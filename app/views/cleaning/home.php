<?php
/** 그린청소 홈페이지 첫 화면(디자인 1b 확장안). 변수: $sent, $form, $errors, $photos */
$name = gc('name');
$phone = gc('phone');
$tel = tel_href($phone);
$kakao = gc('kakao_url');
$channelKey = gc('channeltalk_key');
$services = cleaning_services();
$reviews = cleaning_reviews();
$scope = cleaning_scope();
$sitePhotos = array_values(array_filter($photos['site']));
$pairs = array_values(array_filter($photos['ba'], function ($p) {
    return $p['before'] !== '' && $p['after'] !== '';
}));
$kakaoIcon = cleaning_kakao_icon();
$icon = function ($path, $size = 24) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . e($path) . '"/></svg>';
};
$nav = array('services' => '서비스', 'process' => '작업 과정', 'scope' => '청소 범위');
if ($pairs) {
    $nav['before-after'] = '청소 전후';
}
$nav += array('regions' => '서비스 지역', 'faq' => 'FAQ');
// 검색 노출: 제목·설명은 관리자 › 홈페이지 관리 › 검색 노출에서 바꿀 수 있어요.
$url = base_url() . '/';
$faq = cleaning_faq();
// 지역 · 업종별 청소: 검색어 페이지가 있으면 그 페이지로 연결(없으면 처음 소개 카드)
$areas = array_map(function ($p) {
    return array($p['title'], $p['summary'], $p['kind'], landing_url($p));
}, landing_public());
if (!$areas) {
    $areas = array_map(function ($l) {
        return array($l[0], $l[1], $l[2], '');
    }, cleaning_local_services());
}
$meta = array(
    'title' => gc('seo_title') !== '' ? gc('seo_title') : $name,
    'desc' => gc('seo_desc'),
    'canonical' => $url,
    'keywords' => true,
    'ld' => array(
        array(
            '@context' => 'https://schema.org', '@type' => 'LocalBusiness', '@id' => $url . '#business',
            'name' => $name, 'url' => $url, 'telephone' => $phone, 'image' => base_url() . '/assets/green-og.jpg',
            'description' => gc('seo_desc'), 'priceRange' => '견적 문의',
            'areaServed' => array_map(function ($a) {
                return array('@type' => 'AdministrativeArea', 'name' => $a);
            }, array('충청북도 음성군', '음성군 금왕읍', '음성군 대소면', '충청북도 진천군', '충북혁신도시')),
            'keywords' => gc('seo_keywords'),
            'hasOfferCatalog' => array('@type' => 'OfferCatalog', 'name' => '정기청소 서비스', 'itemListElement' => array_map(function ($l) {
                return array('@type' => 'Offer', 'itemOffered' => array('@type' => 'Service', 'name' => $l[0], 'description' => $l[1]) + ($l[3] !== '' ? array('url' => base_url() . $l[3]) : array()));
            }, $areas)),
        ),
        array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(function ($f) {
            return array('@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => array('@type' => 'Answer', 'text' => $f[1]));
        }, $faq)),
    ),
);
$latestPosts = blog_latest(3);
// 첫 화면 제목(h1)에 대표 검색어를 넣습니다(이미 들어 있으면 그대로).
$h1 = trim(gc('tagline'));
if ($h1 === '') {
    $h1 = '충북음성청소업체 ' . $name;
} elseif (mb_strpos(str_replace(' ', '', $h1), '충북음성청소업체') === false) {
    $h1 = '충북음성청소업체 · ' . $h1;
}
?>
<?= view('cleaning/_head', array('meta' => $meta)) ?>
<?= view('cleaning/_header', array('nav' => $nav, 'onHome' => true)) ?>

<main id="main">
<section class="g-hero" aria-labelledby="hero-title">
  <div class="g-wrap g-hero-grid">
    <div class="g-hero-text">
      <h1 class="g-eyebrow" id="hero-title"><span aria-hidden="true"></span><?= e($h1) ?></h1>
      <p class="g-hero-title">사무실·상가 청소,<br><span class="g-accent">이제 신경 끄세요.</span></p>
      <p class="g-lead">요일과 시간만 정해주시면, <?= e($name) ?> 전담 인력이 매번 같은 기준으로 관리합니다.</p>
      <ul class="g-hero-services">
<?php foreach ($services as $key => $s): ?>
        <li><a href="/?kind=<?= $key ?>#quote" data-pick-kind="<?= $key ?>"><span class="g-circle"><?= $icon($s[2], 38) ?></span><span><?= e($s[1]) ?></span></a></li>
<?php endforeach; ?>
      </ul>
    </div>

    <div class="g-quote" id="quote">
      <?= view('cleaning/_quote_card', array('sent' => $sent, 'form' => $form, 'errors' => $errors)) ?>
    </div>
  </div>
</section>

<?php if ($sitePhotos): ?>
<section class="g-photos" aria-label="작업 현장 사진">
  <div class="g-wrap g-photos-inner">
    <span class="g-photos-label">작업 현장<br>사진</span>
    <div class="g-photo-row">
<?php foreach ($sitePhotos as $i => $src): ?>
      <img src="<?= e($src) ?>" alt="작업 현장 사진 <?= $i + 1 ?>" loading="lazy">
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="g-section" id="services" aria-labelledby="services-title">
  <div class="g-wrap">
    <div class="g-head"><span class="g-kicker">— 서비스 소개 —</span><h2 id="services-title">공간에 맞춘 정기 관리</h2></div>
    <ul class="g-service-list">
<?php foreach ($services as $key => $s): ?>
      <li class="g-service">
        <span class="g-circle g-circle-soft"><?= $icon($s[2], 34) ?></span>
        <h3><?= e($s[0]) ?></h3>
        <p><?= e($s[3]) ?></p>
        <a class="g-more" href="/?kind=<?= $key ?>#quote" data-pick-kind="<?= $key ?>">견적 받기 →</a>
      </li>
<?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="g-section g-soft" id="areas" aria-labelledby="areas-title">
  <div class="g-wrap">
    <div class="g-head"><span class="g-kicker">— 지역 · 업종별 청소 —</span><h2 id="areas-title">음성 · 진천 · 혁신도시, 공간에 맞춰 관리합니다</h2></div>
    <ul class="g-area-grid">
<?php foreach ($areas as $l): ?>
      <li class="g-area">
        <h3><?= $l[3] !== '' ? '<a href="' . e($l[3]) . '">' . e($l[0]) . '</a>' : e($l[0]) ?></h3>
        <p><?= e(str_cut($l[1], 90)) ?></p>
        <div class="g-area-links">
<?php if ($l[3] !== ''): ?>          <a class="g-more" href="<?= e($l[3]) ?>">자세히 보기 →</a>
<?php endif; ?>
          <a class="g-more" href="/?kind=<?= e($l[2]) ?>#quote" data-pick-kind="<?= e($l[2]) ?>">견적 받기 →</a>
        </div>
      </li>
<?php endforeach; ?>
<?php if (count($areas) % 3 === 2): ?>
      <li class="g-area">
        <h3>그 밖의 지역 · 공간</h3>
        <p>음성 · 진천 · 혁신도시 인근이라면 사무실 · 상가 · 공장 · 화장실 어디든 먼저 문의해 주세요.</p>
        <div class="g-area-links"><a class="g-more" href="#quote" data-go-quote>견적 문의 →</a></div>
      </li>
<?php endif; ?>
    </ul>
  </div>
</section>

<section class="g-section g-dark" id="process" aria-labelledby="process-title">
  <div class="g-wrap">
    <div class="g-head"><span class="g-kicker">— 작업 과정 —</span><h2 id="process-title">문의부터 정기관리까지 4단계</h2></div>
    <ol class="g-steps">
<?php foreach (cleaning_steps() as $i => $p): ?>
      <li><span class="g-step-no"><?= $i + 1 ?></span><strong><?= e($p[0]) ?></strong><p><?= e($p[1]) ?></p></li>
<?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="g-section" id="scope" aria-labelledby="scope-title">
  <div class="g-wrap g-scope">
    <div class="g-scope-intro">
      <span class="g-kicker">— 청소 범위 —</span>
      <h2 id="scope-title">어디까지 해주나요?<br>구역별로 확인하세요</h2>
      <p>기본 항목 외 추가 요청도 견적 시 반영해드립니다.</p>
      <div class="g-tabs" role="tablist" aria-label="청소 구역">
<?php $first = true; foreach (CLEANING_KINDS as $key => $label): ?>
        <button type="button" role="tab" id="tab-<?= $key ?>" aria-controls="panel-<?= $key ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"<?= $first ? '' : ' tabindex="-1"' ?> data-tab><span><?= e($label) ?></span><span aria-hidden="true">→</span></button>
<?php $first = false; endforeach; ?>
      </div>
    </div>
    <div class="g-scope-panels">
<?php $first = true; foreach (CLEANING_KINDS as $key => $label): ?>
      <div class="g-scope-panel" role="tabpanel" id="panel-<?= $key ?>" aria-labelledby="tab-<?= $key ?>"<?= $first ? '' : ' data-hidden' ?>>
        <h3 class="g-panel-title"><?= e($label) ?></h3>
        <ul class="g-checks">
<?php foreach ($scope[$key] as $item): ?>
          <li><span class="g-check" aria-hidden="true">✓</span><?= e($item) ?></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php $first = false; endforeach; ?>
    </div>
  </div>
</section>

<?php if ($pairs): ?>
<section class="g-section g-soft" id="before-after" aria-labelledby="ba-title">
  <div class="g-wrap">
    <div class="g-head"><span class="g-kicker">— 청소 전후 —</span><h2 id="ba-title">차이는 사진이 말해줍니다</h2></div>
    <div class="g-ba-grid">
<?php foreach ($pairs as $p): ?>
      <figure class="g-ba">
        <div class="g-ba-imgs">
          <div><img src="<?= e($p['before']) ?>" alt="<?= e($p['title']) ?> 청소 전" loading="lazy"><span class="g-tag">청소 전</span></div>
          <div><img src="<?= e($p['after']) ?>" alt="<?= e($p['title']) ?> 청소 후" loading="lazy"><span class="g-tag g-tag-green">청소 후</span></div>
        </div>
        <figcaption><?= e($p['title']) ?></figcaption>
      </figure>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($reviews): ?>
<section class="g-section" id="reviews" aria-labelledby="reviews-title">
  <div class="g-wrap">
    <div class="g-head"><span class="g-kicker">— 고객 후기 —</span><h2 id="reviews-title">맡겨본 분들의 이야기</h2></div>
    <div class="g-reviews">
<?php foreach ($reviews as $r): ?>
      <blockquote class="g-review"><span class="g-quote-mark" aria-hidden="true">“</span><p><?= e($r[0]) ?></p><?php if ($r[1] !== ''): ?><footer><?= e($r[1]) ?></footer><?php endif; ?></blockquote>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="g-section g-soft" id="regions" aria-labelledby="regions-title">
  <div class="g-wrap g-regions">
    <div class="g-regions-text">
      <span class="g-kicker">— 서비스 지역 —</span>
      <h2 id="regions-title">충북 음성 · 진천 ·<br>혁신도시 어디든</h2>
      <p>지역 기반으로 운영해 정해진 시간에 정확히 방문합니다. 인근 지역은 문의해 주세요.</p>
      <ul class="g-region-list">
<?php foreach (cleaning_regions() as $g): ?>
        <li><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg><strong><?= e($g[0]) ?></strong><span><?= e($g[1]) ?></span></li>
<?php endforeach; ?>
      </ul>
    </div>
    <div class="g-map">
<?php if ($photos['map'] !== ''): ?>
      <img src="<?= e($photos['map']) ?>" alt="서비스 지역 지도 (음성·진천·충북혁신도시)" loading="lazy">
<?php else: ?>
      <svg class="g-map-art" viewBox="0 0 480 400" role="img" aria-label="서비스 지역: 진천군, 충북혁신도시, 음성군">
        <rect width="480" height="400" rx="24" fill="#E3EEE6"/>
        <path d="M40 300 C120 250 160 330 240 280 S380 210 450 250" fill="none" stroke="#C9DCCF" stroke-width="18" stroke-linecap="round"/>
        <path d="M70 120 C150 90 210 160 290 130 S400 90 440 120" fill="none" stroke="#D3E4D8" stroke-width="12" stroke-linecap="round"/>
        <circle cx="240" cy="205" r="92" fill="#2F7D5C" opacity=".1"/>
<?php foreach (array(array(130, 170, '진천군'), array(240, 215, '충북혁신도시'), array(350, 165, '음성군')) as $pin): ?>
        <g transform="translate(<?= $pin[0] ?> <?= $pin[1] ?>)"><path d="M0 0c-14-15-22-27-22-38a22 22 0 0 1 44 0c0 11-8 23-22 38z" fill="#2F7D5C"/><circle cy="-38" r="8" fill="#fff"/><text y="30" text-anchor="middle" font-size="17" font-weight="800" fill="#2A2D33"><?= e($pin[2]) ?></text></g>
<?php endforeach; ?>
      </svg>
<?php endif; ?>
    </div>
  </div>
</section>

<?php if ($latestPosts): ?>
<section class="g-section" id="blog" aria-labelledby="blog-title">
  <div class="g-wrap">
    <div class="g-head"><span class="g-kicker">— 청소 이야기 —</span><h2 id="blog-title">현장에서 전하는 청소 팁</h2></div>
    <?= view('cleaning/_post_cards', array('posts' => $latestPosts)) ?>
    <p class="g-center"><a class="g-btn-outline" href="/blog">블로그 글 모두 보기 →</a></p>
  </div>
</section>
<?php endif; ?>

<section class="g-section" id="faq" aria-labelledby="faq-title">
  <div class="g-wrap g-faq-wrap">
    <h2 id="faq-title" class="g-faq-title">자주 묻는 질문</h2>
    <div class="g-faq">
<?php foreach ($faq as $i => $f): ?>
      <details<?= $i === 0 ? ' open' : '' ?>><summary><span><?= e($f[0]) ?></span><span class="g-faq-sign" aria-hidden="true"></span></summary><p><?= e($f[1]) ?></p></details>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="g-cta" aria-label="청소 문의 · 견적 상담">
  <div class="g-wrap g-cta-inner">
    <div><span class="g-cta-kicker">— 청소 문의 · 견적 상담 —</span><a class="g-cta-phone" href="<?= e($tel) ?>"><?= e($phone) ?></a></div>
    <div class="g-cta-btns">
<?php if ($channelKey !== ''): ?>      <a class="g-btn-chat" href="#" data-open-chat><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg>채팅 상담</a>
<?php endif; ?>
<?php if ($kakao !== ''): ?>      <a class="g-btn-kakao" href="<?= e($kakao) ?>" target="_blank" rel="noopener"><?= $kakaoIcon ?>카카오톡 상담</a>
<?php endif; ?>
      <a class="g-btn-white" href="#quote" data-go-quote>견적 문의하기</a>
    </div>
  </div>
</section>
</main>

<?= view('cleaning/_footer') ?>
