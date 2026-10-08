<?php /** 서버 사용 통계 한 묶음(오늘 · 30일). 변수: $rep(server_stat_report), $id, $trafficQuota(MB) */
$total = (int) $rep['total'];
$p = function ($n) use ($total) {
    return $total ? round($n * 100 / $total, 1) : 0;
};
?>
  <p class="usage-big"><b><?= e(usage_bytes($total)) ?></b> <span>보낸 양 · 요청 <?= number_format($rep['hits']) ?>번</span><?php if ($trafficQuota && $id === 'month'): ?> <em><?= number_format($trafficQuota) ?>MB 중 <?= $trafficQuota ? round($total * 100 / ($trafficQuota * 1048576), 3) : 0 ?>%</em><?php endif; ?></p>
<?php if ($total): ?>
  <div class="usage-bar" aria-hidden="true">
<?php foreach (STAT_SECTIONS as $k => $s): $n = $rep['sections'][$k][2] + $rep['sections'][$k][3]; if (!$n) { continue; } ?>    <span style="width:<?= max(0.6, $n * 100 / $total) ?>%;background:<?= $s[1] ?>" title="<?= e($s[0]) ?> <?= $p($n) ?>%"></span>
<?php endforeach; ?>
  </div>
<?php endif; ?>
  <div class="table-wrap">
  <table class="table stat-table">
    <thead><tr><th scope="col">곳</th><th scope="col" class="num">사람</th><th scope="col" class="num">검색 로봇</th><th scope="col" class="num">페이지</th><th scope="col" class="num">사진 · 디자인</th><th scope="col" class="num">합계</th><th scope="col" class="num">비율</th></tr></thead>
    <tbody>
<?php foreach (STAT_SECTIONS as $k => $s): $r = $rep['sections'][$k]; $n = $r[2] + $r[3]; ?>
      <tr<?= $r[0] + $r[1] ? '' : ' class="is-zero"' ?>><td><i class="stat-dot" style="background:<?= $s[1] ?>" aria-hidden="true"></i><?= e($s[0]) ?></td><td class="num"><?= number_format($r[0]) ?></td><td class="num"><?= number_format($r[1]) ?></td><td class="num"><?= e(usage_bytes($r[2])) ?></td><td class="num"><?= e(usage_bytes($r[3])) ?></td><td class="num strong"><?= e(usage_bytes($n)) ?></td><td class="num"><b><?= $p($n) ?>%</b></td></tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
