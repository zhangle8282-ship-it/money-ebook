<?php /** 관리자 › 유입 경로. 변수: $days, $r(visit_report) */
$pct = function ($n, $total) {
    return $total ? max(1, (int) round($n * 100 / $total)) : 0;
};
$label = function ($key) {
    return VISIT_SOURCES[$key][0] ?? $key;
};
$max = $r['sources'] ? max($r['sources']) : 0;
$dayMax = $r['days'] ? max(array_map('intval', array_column($r['days'], 'n'))) : 0;
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>유입 경로</h1>
    <p class="muted">손님이 <b>어느 검색 사이트에서, 어떤 검색어로</b> 홈페이지에 들어왔는지 보여 줘요. 사이트 밖에서 처음 들어온 횟수만 세고(같은 사람이 30분 안에 다시 오면 한 번), 검색 로봇과 관리자는 세지 않아요. 개인을 알아볼 수 있는 정보는 남기지 않아요.</p>
  </div>
</div>
<nav class="tabs visit-tabs" aria-label="기간">
<?php foreach (VISIT_PERIODS as $d => $name): ?>  <a href="/admin/visits?days=<?= $d ?>"<?= $d === $days ? ' aria-current="page"' : '' ?>><?= e($name) ?></a>
<?php endforeach; ?>
</nav>

<div class="settle-cards visit-cards">
  <div class="card settle-card"><span>들어온 횟수</span><strong><?= number_format($r['total']) ?></strong><small><?= e(str_replace('-', '.', $r['from'])) ?>부터</small></div>
  <div class="card settle-card is-byeong"><span>검색으로 들어옴</span><strong><?= number_format($r['search']) ?></strong><small><?= $r['total'] ? $pct($r['search'], $r['total']) . '%' : '-' ?></small></div>
  <div class="card settle-card is-gap"><span>네이버 전체</span><strong><?= number_format($r['naver']) ?></strong><small>검색 · 블로그 · 플레이스 · 카페</small></div>
  <div class="card settle-card"><span>직접 방문 · 앱</span><strong><?= number_format($r['direct']) ?></strong><small>주소 입력 · 즐겨찾기 · 카카오톡 등</small></div>
</div>

<?php if (!$r['total']): ?>
<section class="card"><p class="muted pad">이 기간에 들어온 기록이 아직 없어요. 오늘부터 세기 시작했다면 손님이 들어오는 대로 여기에 쌓여요.</p></section>
<?php else: ?>
<div class="visit-grid">
  <section class="card flush" aria-labelledby="v-src">
    <div class="card-head pad-head"><h2 id="v-src">어디서 들어왔나</h2></div>
    <ul class="visit-bars">
<?php foreach ($r['sources'] as $key => $n): ?>
      <li class="<?= !empty(VISIT_SOURCES[$key][1]) ? 'is-search' : '' ?>">
        <span class="vb-name"><?= e($label($key)) ?></span>
        <span class="vb-bar" aria-hidden="true"><i style="width:<?= $max ? max(2, (int) round($n * 100 / $max)) : 0 ?>%"></i></span>
        <span class="vb-num"><?= number_format($n) ?> <small><?= $pct($n, $r['total']) ?>%</small></span>
      </li>
<?php endforeach; ?>
    </ul>
  </section>

  <section class="card flush" aria-labelledby="v-kw">
    <div class="card-head pad-head"><h2 id="v-kw">검색어 순위</h2></div>
<?php if ($r['keywords'] || $r['hidden']): ?>
    <div class="table-wrap">
    <table class="table visit-table">
      <thead><tr><th scope="col">검색어</th><th scope="col">검색 사이트</th><th scope="col" class="num">들어온 횟수</th></tr></thead>
      <tbody>
<?php foreach ($r['keywords'] as $k): ?>
        <tr><td class="strong"><?= e($k['keyword']) ?></td><td><?= e($label($k['source'])) ?></td><td class="num"><?= number_format((int) $k['n']) ?></td></tr>
<?php endforeach; ?>
<?php foreach ($r['hidden'] as $src => $n): ?>
        <tr class="is-hidden-kw"><td><span class="sub">(검색어 비공개)</span></td><td><?= e($label($src)) ?></td><td class="num"><?= number_format((int) $n) ?></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
    </div>
<?php else: ?>
    <p class="muted pad">아직 검색으로 들어온 기록이 없어요.</p>
<?php endif; ?>
    <p class="sub pad">구글은 검색어를 홈페이지에 알려 주지 않아서 ‘검색어 비공개’로 나와요. 구글 검색어는 <a href="https://search.google.com/search-console/performance/search-analytics" target="_blank" rel="noopener">구글 서치 콘솔 › 실적</a>에서, 네이버는 <a href="https://searchadvisor.naver.com/console/board" target="_blank" rel="noopener">서치어드바이저</a>의 리포트에서도 볼 수 있어요.</p>
  </section>
</div>

<?php if ($r['sites']): ?>
<section class="card flush" aria-labelledby="v-sites">
  <div class="card-head pad-head"><h2 id="v-sites">다른 사이트 · AI · 표시한 링크</h2></div>
  <ul class="visit-sites">
<?php foreach ($r['sites'] as $s): ?>    <li><span><?= e($s['keyword'] !== '' ? $s['keyword'] : '-') ?></span><span class="sub"><?= e($label($s['source'])) ?></span><b><?= number_format((int) $s['n']) ?></b></li>
<?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php if (count($r['days']) > 1): ?>
<section class="card flush" aria-labelledby="v-days">
  <div class="card-head pad-head"><h2 id="v-days">날짜별 들어온 횟수</h2></div>
  <ol class="visit-days">
<?php foreach ($r['days'] as $d): ?>    <li><span class="vd-bar" style="height:<?= $dayMax ? max(4, (int) round($d['n'] * 100 / $dayMax)) : 0 ?>%" title="<?= e($d['day']) ?> · <?= (int) $d['n'] ?>회"></span><span class="vd-day"><?= (int) substr($d['day'], 8, 2) ?></span><span class="sr-only"><?= e($d['day']) ?> <?= (int) $d['n'] ?>회</span></li>
<?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>
<?php endif; ?>
<p class="sub">카카오톡 · 문자 · 인스타그램 앱 안에서 링크를 누르면 들어온 곳이 안 알려져 ‘직접 방문 · 앱’으로 잡힐 때가 많아요. 네이버 블로그 글 링크에 <code>?utm_source=naver_blog</code>처럼 붙여 두면 ‘표시한 링크’로 따로 셀 수 있어요.</p>
