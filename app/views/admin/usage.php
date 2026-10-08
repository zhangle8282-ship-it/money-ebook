<?php /** 관리자 › 용량 · 트래픽. 변수: $u(usage_cached), $plan, $quota(MB), $trafficQuota(MB), $home(traffic_home_weight), $visits(최근 30일 들어온 횟수), $heavy, $today · $month(server_stat_report) */
$MB = 1048576;
$used = (int) $u['total'];
$quotaB = $quota * $MB;
$pct = function ($n, $of, $digits = 1) {
    return $of > 0 ? round($n * 100 / $of, $digits) : 0;
};
$monthly = $visits * $home['total'];
$per1000 = 1000 * $home['total'];
$trafficB = $trafficQuota * $MB;
$share = function ($n) use ($used) {
    return $used ? max(0.4, $n * 100 / $used) : 0;
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>용량 · 트래픽</h1>
    <p class="muted">이 홈페이지가 카페24 서버 공간을 무엇에 얼마나 쓰는지 색깔별로 보여 줘요. 트래픽은 홈페이지가 직접 셀 수 없어서(사진 · 디자인 파일은 웹서버가 바로 보냄) 첫 화면 무게와 방문 수로 <b>추정</b>해요. 정확한 사용량은 카페24 › 나의 서비스 관리 › <b>사용량 모니터링</b>에서 볼 수 있어요.</p>
  </div>
</div>

<?php $dayMax = $month['days'] ? max(array_map('array_sum', $month['days'])) : 0; ?>
<section class="card stack usage-card" aria-labelledby="u-today">
  <div class="usage-head"><h2 id="u-today">오늘 서버 사용 <span class="sub">(<?= e(date('m.d')) ?>)</span></h2></div>
  <?= view('admin/_stat_block', array('rep' => $today, 'id' => 'today', 'trafficQuota' => $trafficQuota)) ?>
</section>

<section class="card stack usage-card" aria-labelledby="u-month">
  <div class="usage-head"><h2 id="u-month">최근 30일 서버 사용</h2></div>
  <?= view('admin/_stat_block', array('rep' => $month, 'id' => 'month', 'trafficQuota' => $trafficQuota)) ?>
<?php if ($dayMax): ?>
  <p class="sub">날짜별 보낸 양 (색은 곳)</p>
  <ol class="stat-days" aria-label="날짜별 보낸 양">
<?php foreach ($month['days'] as $d => $parts): $sum = array_sum($parts); ?>
    <li title="<?= e($d) ?> · <?= e(usage_bytes($sum)) ?>"><span class="sd-stack" style="height:<?= $sum ? max(3, round($sum * 100 / $dayMax)) : 0 ?>%"><?php foreach (array_reverse(array_keys(STAT_SECTIONS)) as $k): if (!$parts[$k]) { continue; } ?><i style="height:<?= round($parts[$k] * 100 / $sum, 2) ?>%;background:<?= STAT_SECTIONS[$k][1] ?>"></i><?php endforeach; ?></span><span class="sd-day"><?= (int) substr($d, 8, 2) ?></span><span class="sr-only"><?= e($d) ?> <?= e(usage_bytes($sum)) ?></span></li>
<?php endforeach; ?>
  </ol>
<?php endif; ?>
  <p class="sub"><b>페이지</b>는 홈페이지가 직접 보낸 양(압축 후)이라 정확하고, <b>사진 · 디자인</b>은 그 페이지에 들어 있는 사진과 처음 온 손님의 디자인 파일을 받았다고 보고 넉넉하게 잡은 추정이에요. 검색 로봇이 사진을 따로 받아 가는 양은 들어 있지 않아요. 오늘부터 세기 시작해요.</p>
</section>

<section class="card stack usage-card" aria-labelledby="u-disk">
  <div class="usage-head">
    <h2 id="u-disk">서버 공간 (웹 용량)</h2>
    <a class="link-btn" href="/admin/usage?fresh=1">지금 다시 재기</a>
  </div>
  <p class="usage-big"><b><?= e(usage_bytes($used)) ?></b><?php if ($quota): ?> <span>/ <?= number_format($quota) ?>MB<?= $plan !== '' ? ' (' . e($plan) . ')' : '' ?></span> <em class="<?= $pct($used, $quotaB) >= 80 ? 'warn-text' : '' ?>"><?= $pct($used, $quotaB, 2) ?>% 사용 · 남은 공간 <?= e(usage_bytes(max(0, $quotaB - $used))) ?></em><?php endif; ?></p>
<?php if ($quota): ?>
  <div class="usage-bar is-quota" role="img" aria-label="웹 용량 <?= $pct($used, $quotaB, 2) ?>% 사용"><span style="width:<?= max(0.6, min(100, $pct($used, $quotaB, 3))) ?>%"></span></div>
<?php endif; ?>
  <p class="sub">쓰고 있는 공간을 종류별로 나누면</p>
  <div class="usage-bar" aria-hidden="true">
<?php foreach (USAGE_PARTS as $k => $p): if (empty($u['parts'][$k][0])) { continue; } ?>    <span style="width:<?= $share($u['parts'][$k][0]) ?>%;background:<?= $p[1] ?>" title="<?= e($p[0]) ?>"></span>
<?php endforeach; ?>
  </div>
  <ul class="usage-legend">
<?php foreach (USAGE_PARTS as $k => $p): $b = (int) ($u['parts'][$k][0] ?? 0); ?>
    <li><i style="background:<?= $p[1] ?>" aria-hidden="true"></i><span class="ul-name"><?= e($p[0]) ?></span><span class="ul-num"><?= $pct($b, $used) ?>%</span><span class="ul-size"><?= e(usage_bytes($b)) ?><small> · <?= number_format((int) ($u['parts'][$k][1] ?? 0)) ?>개</small></span></li>
<?php endforeach; ?>
  </ul>
  <p class="sub">이 홈페이지 폴더 기준이에요(<?= e(fmt_date($u['at'], 'm.d H:i')) ?>에 쟀어요<?= !empty($u['partial']) ? ' · 파일이 많아 일부만 셌어요' : '' ?>). 10분 동안은 잰 값을 그대로 보여 주고, ‘지금 다시 재기’를 누르면 새로 재요.</p>
</section>

<section class="card stack usage-card" aria-labelledby="u-traffic">
  <div class="usage-head"><h2 id="u-traffic">첫 화면 무게 · 한 달 예상</h2></div>
  <p class="usage-big"><b><?= e(usage_bytes($home['total'])) ?></b> <span>손님 한 명이 첫 화면을 끝까지 볼 때</span></p>
  <div class="usage-bar" aria-hidden="true">
<?php foreach (TRAFFIC_PARTS as $k => $p): if (empty($home['parts'][$k])) { continue; } ?>    <span style="width:<?= $home['total'] ? max(0.6, $home['parts'][$k] * 100 / $home['total']) : 0 ?>%;background:<?= $p[1] ?>" title="<?= e($p[0]) ?>"></span>
<?php endforeach; ?>
  </div>
  <ul class="usage-legend">
<?php foreach (TRAFFIC_PARTS as $k => $p): $b = (int) ($home['parts'][$k] ?? 0); ?>
    <li><i style="background:<?= $p[1] ?>" aria-hidden="true"></i><span class="ul-name"><?= e($p[0]) ?></span><span class="ul-num"><?= $pct($b, $home['total']) ?>%</span><span class="ul-size"><?= e(usage_bytes($b)) ?></span></li>
<?php endforeach; ?>
  </ul>
  <dl class="usage-est">
    <div><dt>최근 30일 들어온 횟수</dt><dd><?= number_format($visits) ?>회 <a class="sub" href="/admin/visits?days=30">유입 경로 ›</a></dd></div>
    <div><dt>한 달 예상 트래픽</dt><dd><b><?= e(usage_bytes($monthly)) ?></b><?php if ($trafficQuota): ?> <span class="sub">/ <?= number_format($trafficQuota) ?>MB 중 <?= $pct($monthly, $trafficB, 3) ?>%</span><?php endif; ?></dd></div>
    <div><dt>손님 1,000명이면</dt><dd><?= e(usage_bytes($per1000)) ?><?php if ($trafficQuota): ?> <span class="sub">(<?= $pct($per1000, $trafficB, 3) ?>%)</span><?php endif; ?></dd></div>
  </dl>
  <p class="sub">모두 첫 화면을 끝까지 본다고 넉넉하게 잡은 값이에요. 두 번째 페이지부터는 디자인 · 동작 파일을 다시 받지 않아 페이지당 수 KB예요. 글꼴은 무료 외부 서버에서 받아서 넣지 않았어요. 검색 로봇이 읽어 가는 양은 빠져 있어요.</p>
</section>

<?php if ($heavy): ?>
<section class="card flush" aria-labelledby="u-heavy">
  <div class="card-head pad-head"><h2 id="u-heavy">무거운 파일 TOP <?= count($heavy) ?></h2></div>
  <div class="table-wrap">
  <table class="table usage-heavy">
    <thead><tr><th scope="col">파일</th><th scope="col">종류</th><th scope="col" class="num">용량</th></tr></thead>
    <tbody>
<?php foreach ($heavy as $h): ?>
      <tr<?= $h[1] > 307200 ? ' class="is-heavy"' : '' ?>><td><a href="<?= e($h[0]) ?>" target="_blank" rel="noopener" class="mono"><?= e(basename($h[0])) ?></a></td><td><?= e($h[2]) ?></td><td class="num"><?= e(usage_bytes($h[1])) ?><?= $h[1] > 307200 ? ' <span class="status status-pending">무거움</span>' : '' ?></td></tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p class="sub pad">300KB 넘는 사진은 많이 보일수록 트래픽을 많이 써요. 새로 올리는 사진은 자동으로 줄어들어요.</p>
</section>
<?php endif; ?>

<form method="post" action="/admin/usage" class="card stack-lg" aria-labelledby="u-plan">
  <?= csrf_field() ?>
  <div class="card-intro"><h2 id="u-plan">카페24 상품 정보</h2><p class="muted">카페24 › 나의 서비스 관리의 ‘하드/트래픽 사양’에 나온 값이에요. 상품을 바꾸면 여기도 고쳐 주세요.</p></div>
  <div class="grid-3">
    <div class="field"><label for="u-plan-name">상품 이름</label><input id="u-plan-name" name="plan" type="text" maxlength="40" value="<?= e($plan) ?>"></div>
    <div class="field"><label for="u-quota">웹 용량 (MB)</label><input id="u-quota" name="quota_mb" type="text" inputmode="numeric" value="<?= $quota ? e(number_format($quota)) : '' ?>" data-money></div>
    <div class="field"><label for="u-traffic-q">트래픽 용량 (MB)</label><input id="u-traffic-q" name="traffic_mb" type="text" inputmode="numeric" value="<?= $trafficQuota ? e(number_format($trafficQuota)) : '' ?>" data-money></div>
  </div>
  <div><button type="submit" class="btn btn-outline">저장하기</button></div>
</form>
