<?php
/** 그린청소 홈페이지 첫 화면(디자인 1b 확장안). 변수: $sent, $form, $errors, $photos */
$name = gc('name');
$phone = gc('phone');
$tel = tel_href($phone);
$kakao = gc('kakao_url');
$channelKey = gc('channeltalk_key');
$services = cleaning_services();
$scope = cleaning_scope();
$sitePhotos = array_values(array_filter($photos['site']));
$pairs = array_values(array_filter($photos['ba'], function ($p) {
    return $p['before'] !== '' && $p['after'] !== '';
}));
$pageTitle = $name . ' | 음성·진천·충북혁신도시 사무실·상가·화장실 정기청소';
$pageDesc = '요일과 시간만 정해주시면 전담 인력이 매번 같은 기준으로 관리합니다. 음성·진천·충북혁신도시 사무실, 건물·상가, 화장실 정기청소. 현장 방문 견적 무료.';
$asset = function ($file) {
    return '/assets/' . $file . '?v=' . @filemtime(PUBLIC_DIR . '/assets/' . $file);
};
$logo = function ($light = false) use ($name) {
    $leaf = $light ? '#A8D5BA' : '#2A2D33';
    $stroke = $light ? '#fff' : '#2F7D5C';
    $text = mb_substr($name, 0, 2) === '그린'
        ? '<span class="g-logo-green">그린</span>' . e(mb_substr($name, 2))
        : e($name);
    return '<svg class="g-logo-mark" viewBox="0 0 66 64" aria-hidden="true"><path d="M47.6 16.4A22 22 0 1 0 54 32H36" fill="none" stroke="' . $stroke . '" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/><path d="M50 14C50 7 55 3 62 3C62 10 57 14 50 14Z" fill="' . $leaf . '"/></svg><span class="g-logo-text">' . $text . '</span>';
};
$kakaoIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3C6.5 3 2 6.6 2 11c0 2.8 1.9 5.3 4.7 6.7l-1 3.6c-.1.3.3.6.6.4l4.2-2.8c.5.1 1 .1 1.5.1 5.5 0 10-3.6 10-8S17.5 3 12 3z"/></svg>';
$phoneIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>';
$icon = function ($path, $size = 24) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . e($path) . '"/></svg>';
};
$nav = array('services' => '서비스', 'process' => '작업 과정', 'scope' => '청소 범위');
if ($pairs) {
    $nav['before-after'] = '청소 전후';
}
$nav += array('regions' => '서비스 지역', 'faq' => 'FAQ');
$ld = array(
    '@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => $name, 'telephone' => $phone,
    'url' => base_url() . '/', 'description' => $pageDesc, 'image' => base_url() . '/assets/green-og.jpg',
    'areaServed' => array('충청북도 음성군', '충청북도 진천군', '충북혁신도시'),
);
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<link rel="canonical" href="<?= e(base_url()) ?>/">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($name) ?>">
<meta property="og:title" content="<?= e($name) ?> · 사무실·상가·화장실 정기청소">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:url" content="<?= e(base_url()) ?>/">
<meta property="og:image" content="<?= e(base_url()) ?>/assets/green-og.jpg">
<meta name="theme-color" content="#2F7D5C">
<?= icon_links() ?>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
<link rel="stylesheet" href="<?= e($asset('green.css')) ?>">
<script>document.documentElement.classList.add('js');</script>
<script type="application/ld+json"><?= str_replace('</', '<\/', json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></script>
</head>
<body class="g-body<?= $kakao !== '' ? ' has-kakao' : '' ?><?= $channelKey !== '' ? ' has-channeltalk' : '' ?>"<?= $channelKey !== '' ? ' data-channeltalk="' . e($channelKey) . '"' : '' ?>>
<a class="g-skip" href="#main">본문 바로가기</a>

<header class="g-header" data-header>
  <div class="g-wrap g-header-inner">
    <a class="g-logo" href="/" aria-label="<?= e($name) ?> 처음으로"><?= $logo() ?></a>
    <nav class="g-nav" id="g-nav" aria-label="주요 메뉴">
<?php foreach ($nav as $id => $label): ?>
      <a href="#<?= $id ?>"><?= e($label) ?></a>
<?php endforeach; ?>
      <a class="g-nav-quote" href="#quote">무료 견적 문의</a>
    </nav>
    <div class="g-header-cta">
<?php if ($kakao !== ''): ?>      <a class="g-btn-kakao g-btn-sm" href="<?= e($kakao) ?>" target="_blank" rel="noopener"><?= $kakaoIcon ?>카톡 상담</a>
<?php endif; ?>
      <a class="g-header-phone" href="<?= e($tel) ?>"><?= e($phone) ?></a>
    </div>
    <button type="button" class="g-menu-btn" aria-controls="g-nav" aria-expanded="false" data-menu-btn>
      <span class="g-sr">메뉴 열기</span>
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
  </div>
</header>

<main id="main">
<section class="g-hero" aria-labelledby="hero-title">
  <div class="g-wrap g-hero-grid">
    <div class="g-hero-text">
      <p class="g-eyebrow"><span aria-hidden="true"></span><?= e(gc('tagline')) ?></p>
      <h1 id="hero-title">사무실·상가 청소,<br><span class="g-accent">이제 신경 끄세요.</span></h1>
      <p class="g-lead">요일과 시간만 정해주시면, <?= e($name) ?> 전담 인력이 매번 같은 기준으로 관리합니다.</p>
      <ul class="g-hero-services">
<?php foreach ($services as $key => $s): ?>
        <li><a href="/?kind=<?= $key ?>#quote" data-pick-kind="<?= $key ?>"><span class="g-circle"><?= $icon($s[2], 38) ?></span><span><?= e($s[1]) ?></span></a></li>
<?php endforeach; ?>
      </ul>
    </div>

    <div class="g-quote" id="quote">
      <div class="g-quote-card">
        <div class="g-sent" data-sent role="status"<?= $sent ? '' : ' hidden' ?>>
          <span class="g-sent-mark" aria-hidden="true">✓</span>
          <strong>문의가 접수되었습니다</strong>
          <p>영업일 하루 안에 연락드립니다.<br>급하시면 <a href="<?= e($tel) ?>"><?= e($phone) ?></a>로 전화 주세요.</p>
          <a class="g-link-btn" href="/#quote" data-reset>다시 작성</a>
        </div>
        <form method="post" action="/inquiry" class="g-form" data-inquiry-form novalidate<?= $sent ? ' hidden' : '' ?>>
          <?= csrf_field() ?>
          <div class="g-hp" aria-hidden="true"><label>웹사이트 <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <h2 class="g-form-title">무료 견적 문의</h2>
          <p class="g-form-sub">30초면 끝나요. 현장 방문 견적 무료.</p>
          <fieldset class="g-chips">
            <legend class="g-sr">청소 종류</legend>
<?php foreach (CLEANING_KINDS as $key => $label): ?>
            <input type="radio" name="kind" value="<?= $key ?>" id="kind-<?= $key ?>"<?= ($form['kind'] ?? 'office') === $key ? ' checked' : '' ?>><label for="kind-<?= $key ?>"><?= e($label) ?></label>
<?php endforeach; ?>
          </fieldset>
          <div class="g-field">
            <label class="g-sr" for="q-name">업체명 / 담당자</label>
            <input id="q-name" name="name" type="text" maxlength="60" required placeholder="업체명 / 담당자" autocomplete="organization" value="<?= e($form['name'] ?? '') ?>"<?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?> aria-describedby="err-name">
            <p class="g-err" id="err-name" data-err="name"><?= e($errors['name'] ?? '') ?></p>
          </div>
          <div class="g-field">
            <label class="g-sr" for="q-phone">연락처</label>
            <input id="q-phone" name="phone" type="tel" inputmode="tel" maxlength="30" required placeholder="연락처" autocomplete="tel" value="<?= e($form['phone'] ?? '') ?>"<?= isset($errors['phone']) ? ' aria-invalid="true"' : '' ?> aria-describedby="err-phone">
            <p class="g-err" id="err-phone" data-err="phone"><?= e($errors['phone'] ?? '') ?></p>
          </div>
          <div class="g-field">
            <label class="g-sr" for="q-address">주소 · 면적(평)</label>
            <input id="q-address" name="address" type="text" maxlength="120" placeholder="주소 · 면적(평)" autocomplete="street-address" value="<?= e($form['address'] ?? '') ?>">
          </div>
          <label class="g-agree"><input type="checkbox" name="agree" value="1" required> <span>개인정보 수집·이용에 동의합니다 <a href="/privacy" target="_blank" rel="noopener">자세히</a></span></label>
          <p class="g-err" data-err="agree"><?= e($errors['agree'] ?? '') ?></p>
          <p class="g-err g-err-form" data-err="form" role="alert"><?= e($errors['form'] ?? '') ?></p>
          <button type="submit" class="g-btn-dark">견적 문의 보내기</button>
        </form>
      </div>
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

<section class="g-section" id="reviews" aria-labelledby="reviews-title">
  <div class="g-wrap">
    <div class="g-head"><span class="g-kicker">— 고객 후기 —</span><h2 id="reviews-title">맡겨본 분들의 이야기</h2></div>
    <div class="g-reviews">
<?php foreach (cleaning_reviews() as $r): ?>
      <blockquote class="g-review"><span class="g-quote-mark" aria-hidden="true">“</span><p><?= e($r[0]) ?></p><footer><?= e($r[1]) ?></footer></blockquote>
<?php endforeach; ?>
    </div>
  </div>
</section>

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

<section class="g-section" id="faq" aria-labelledby="faq-title">
  <div class="g-wrap g-faq-wrap">
    <h2 id="faq-title" class="g-faq-title">자주 묻는 질문</h2>
    <div class="g-faq">
<?php foreach (cleaning_faq() as $i => $f): ?>
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

<footer class="g-footer">
  <div class="g-wrap">
    <div class="g-footer-top"><span class="g-logo g-logo-light"><?= $logo(true) ?></span><a class="g-footer-phone" href="<?= e($tel) ?>"><?= e($phone) ?></a></div>
    <div class="g-footer-info">
      <p><span>상호 <?= e($name) ?></span><?php if (gc('owner') !== ''): ?><span>대표 <?= e(gc('owner')) ?></span><?php endif; ?><?php if (gc('biz_number') !== ''): ?><span>사업자등록번호 <?= e(gc('biz_number')) ?></span><?php endif; ?><?php if (gc('biz_type') !== '' || gc('biz_item') !== ''): ?><span>업태 <?= e(gc('biz_type')) ?> · 종목 <?= e(gc('biz_item')) ?></span><?php endif; ?></p>
      <p><?php if (gc('address') !== ''): ?><span>주소 <?= e(gc('address')) ?></span><?php endif; ?><?php if (gc('email') !== ''): ?><span>이메일 <a href="mailto:<?= e(gc('email')) ?>"><?= e(gc('email')) ?></a></span><?php endif; ?><?php if (gc('area') !== ''): ?><span>서비스 지역 <?= e(gc('area')) ?></span><?php endif; ?></p>
    </div>
    <p class="g-copy">© <?= e($name) ?>. All rights reserved. <a href="/privacy">개인정보처리방침</a></p>
  </div>
</footer>

<?php if ($channelKey !== ''): ?>
<a class="g-chat-float" href="#" data-open-chat aria-label="채팅 상담"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg><span>채팅 상담</span></a>
<?php endif; ?>
<?php if ($kakao !== ''): ?>
<a class="g-kakao-float" href="<?= e($kakao) ?>" target="_blank" rel="noopener" aria-label="카카오톡 상담"><?= $kakaoIcon ?></a>
<?php endif; ?>
<nav class="g-mobile-bar<?= $kakao !== '' ? '' : ' no-kakao' ?>" aria-label="빠른 문의">
  <a class="g-mb-call" href="<?= e($tel) ?>"><?= $phoneIcon ?>전화</a>
<?php if ($kakao !== ''): ?>  <a class="g-mb-kakao" href="<?= e($kakao) ?>" target="_blank" rel="noopener"><?= $kakaoIcon ?>카톡</a>
<?php endif; ?>
  <a class="g-mb-quote" href="#quote" data-go-quote>무료 견적</a>
</nav>
<script src="<?= e($asset('green.js')) ?>" defer></script>
</body>
</html>
