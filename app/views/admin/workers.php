<?php /** 그린청소 관리자 › 인력 배치 목록 · 찾기. 변수: $rows, $q, $method, $region, $counts, $total, $assigned(맡은 청소) */
$link = function ($m) use ($q, $region) {
    $params = array_filter(array('q' => $q, 'method' => $m, 'region' => $region), 'strlen');
    return '/admin/workers' . ($params ? '?' . http_build_query($params) : '');
};
$searching = $q !== '' || $region !== '';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>인력 배치</h1>
    <p class="muted">일할 분의 이름 · 연락처 · 커버 가능한 지역과 원하는 방식(수수료 방식 / 인수해서 직접)을 모아 두고, 지역이나 이름으로 찾아봐요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/workers/new">+ 새 사람 추가</a>
</div>

<form method="get" action="/admin/workers" class="card worker-search" role="search">
<?php if ($method !== ''): ?>  <input type="hidden" name="method" value="<?= e($method) ?>">
<?php endif; ?>
  <div class="field worker-q">
    <label for="w-q">찾기</label>
    <input id="w-q" name="q" type="search" value="<?= e($q) ?>" placeholder="이름 · 지역 · 메모 · 전화번호 (예: 금왕, 김)" autocomplete="off">
  </div>
  <div class="field worker-region">
    <label for="w-region">지역</label>
    <select id="w-region" name="region">
      <option value="">모든 지역</option>
<?php foreach (WORKER_REGIONS as $group => $list): ?>
      <optgroup label="<?= e($group) ?>">
<?php foreach ($list as $r): ?>        <option value="<?= e($r) ?>"<?= $region === $r ? ' selected' : '' ?>><?= e($r) ?></option>
<?php endforeach; ?>
      </optgroup>
<?php endforeach; ?>
    </select>
  </div>
  <div class="worker-search-btns">
    <button type="submit" class="btn btn-primary">찾기</button>
<?php if ($searching || $method !== ''): ?>    <a class="btn btn-ghost" href="/admin/workers">처음부터</a>
<?php endif; ?>
  </div>
</form>

<div class="toolbar">
  <nav class="tabs" aria-label="원하는 방식">
    <a href="<?= e($link('')) ?>"<?= $method === '' ? ' aria-current="page"' : '' ?>>전체 <span class="count"><?= (int) $total ?></span></a>
<?php foreach (WORKER_METHODS as $key => $m): ?>
    <a href="<?= e($link($key)) ?>"<?= $method === $key ? ' aria-current="page"' : '' ?>><?= e($m[0]) ?> <span class="count"><?= (int) $counts[$key] ?></span></a>
<?php endforeach; ?>
  </nav>
<?php if ($searching): ?>  <p class="sub">찾은 사람 <?= count($rows) ?>명</p>
<?php endif; ?>
</div>

<?php if ($rows): ?>
<section class="card flush">
  <div class="table-wrap">
  <table class="table worker-table">
    <thead><tr><th scope="col">이름</th><th scope="col">커버 가능 지역</th><th scope="col">원하는 방식</th><th scope="col">맡은 청소</th><th scope="col">메모</th><th scope="col"><span class="sr-only">고치기</span></th></tr></thead>
    <tbody>
<?php foreach ($rows as $w): ?>
      <tr>
        <td class="nowrap"><a class="strong" href="/admin/workers/<?= (int) $w['id'] ?>/edit"><?= e($w['name']) ?></a><?php if ($w['phone'] !== ''): ?><div class="sub"><a href="<?= e(tel_href($w['phone'])) ?>"><?= e($w['phone']) ?></a></div><?php endif; ?></td>
        <td><div class="region-chips"><?php foreach (worker_regions($w['regions']) as $r): ?><span class="region-chip<?= $r === $region ? ' is-hit' : '' ?>"><?= e($r) ?></span><?php endforeach; ?></div></td>
        <td><div class="method-pills"><?php foreach (worker_methods($w['method']) as $key): ?><span class="status worker-<?= e($key) ?>"><?= e(WORKER_METHODS[$key][0]) ?></span><?php endforeach; ?></div></td>
        <td class="worker-jobs"><?php if (!empty($assigned[(int) $w['id']])): foreach ($assigned[(int) $w['id']] as $job): ?><a class="job-chip" href="/admin/contracts/<?= (int) $job[0] ?>/edit"><?= e($job[1]) ?></a><?php endforeach; else: ?><span class="sub">-</span><?php endif; ?></td>
        <td class="worker-memo"><?= $w['memo'] !== '' ? nl2br(e($w['memo']), false) : '<span class="sub">-</span>' ?></td>
        <td class="actions"><a class="btn btn-outline btn-sm" href="/admin/workers/<?= (int) $w['id'] ?>/edit">고치기</a></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
</section>
<?php elseif ($total > 0): ?>
<section class="card empty-card"><p>조건에 맞는 사람이 없어요.</p><p class="sub">다른 낱말이나 지역으로 찾아보세요.</p><a class="btn btn-outline" href="/admin/workers">전체 보기</a></section>
<?php else: ?>
<section class="card empty-card"><p>아직 등록한 사람이 없어요.</p><p class="sub">이름과 커버 가능한 지역, 원하는 방식을 넣어 두면 지역별로 바로 찾을 수 있어요.</p><a class="btn btn-primary" href="/admin/workers/new">+ 첫 사람 추가</a></section>
<?php endif; ?>
