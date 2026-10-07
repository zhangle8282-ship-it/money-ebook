<?php /** 관리자 › 일회성 정산 › 청소 목록. 변수: $rows, $q, $status, $total */
$back = $_SERVER['REQUEST_URI'] ?? '/admin/onetime/jobs';
$feeSum = array_sum(array_map(function ($r) { return (int) $r['fee']; }, $rows));
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>일회성 정산</h1>
    <p class="muted">등록한 일회성 청소 전부예요(최근 작업일부터). 이름을 누르면 고칠 수 있고, 잘못 넣었거나 필요 없는 일은 오른쪽 <b>지우기</b>로 지워요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/onetime/new">+ 새 일회성 정산</a>
</div>
<?= view('admin/_onetime_tabs', array('tab' => 'jobs')) ?>
<form method="get" action="/admin/onetime/jobs" class="card worker-search" role="search">
  <div class="field worker-q">
    <label for="oj-q">찾기</label>
    <input id="oj-q" name="q" type="search" value="<?= e($q) ?>" placeholder="일 이름 · 고객 · 청소 담당 (예: 입주청소, 금왕)" autocomplete="off">
  </div>
  <div class="field worker-region">
    <label for="oj-status">상태</label>
    <select id="oj-status" name="status">
      <option value="">모든 상태</option>
      <option value="pending"<?= $status === 'pending' ? ' selected' : '' ?>>정산 전</option>
      <option value="done"<?= $status === 'done' ? ' selected' : '' ?>>정산 완료</option>
    </select>
  </div>
  <div class="worker-search-btns">
    <button type="submit" class="btn btn-primary">찾기</button>
<?php if ($q !== '' || $status !== ''): ?>    <a class="btn btn-ghost" href="/admin/onetime/jobs">모두 보기</a>
<?php endif; ?>
  </div>
</form>
<section class="card flush">
<?php if ($rows): ?>
  <div class="table-wrap">
  <table class="table table-stack">
    <thead><tr><th scope="col">일</th><th scope="col">작업일</th><th scope="col">방식</th><th scope="col" class="num">청소비용</th><th scope="col">청소 담당</th><th scope="col">상태</th><th scope="col"><span class="sr-only">지우기</span></th></tr></thead>
    <tbody>
<?php foreach ($rows as $r): $done = $r['status'] === 'done'; $takeover = ($r['method'] ?? 'commission') === 'takeover'; $cleaner = contract_partner_name($r, 'byeong'); ?>
      <tr>
        <td data-label="일"><a class="strong" href="/admin/onetime/<?= (int) $r['id'] ?>/edit"><?= e($r['name']) ?></a><?php if ($r['client'] !== ''): ?><div class="sub"><?= e($r['client']) ?></div><?php endif; ?></td>
        <td data-label="작업일" class="nowrap"><a href="/admin/onetime?month=<?= e(substr($r['work_date'], 0, 7)) ?>"><?= e(str_replace('-', '.', $r['work_date'])) ?></a></td>
        <td data-label="방식"><?= e(onetime_method_label($r)) ?><?php if ($r['invoice']): ?><div class="sub">세금계산서 발행</div><?php endif; ?></td>
        <td data-label="청소비용" class="num"><?= won($r['fee']) ?></td>
        <td data-label="청소 담당"><?php if ($takeover): ?><span class="sub">인수 방식(청소 담당 몫 없음)</span><?php elseif ($cleaner !== ''): ?><span class="who-chip"><?= e($cleaner) ?></span><div class="sub">실지급 <?= won($r['byeong_pay']) ?></div><?php else: ?><span class="who-empty">아직 없음</span><?php endif; ?></td>
        <td data-label="상태"><span class="status <?= $done ? 'status-paid' : 'status-pending' ?>"><?= $done ? '정산 완료' : '정산 전' ?></span></td>
        <td class="nowrap">
          <form method="post" action="/admin/onetime/<?= (int) $r['id'] ?>/delete" data-confirm="‘<?= e($r['name']) ?>’을(를) 지울까요?<?= $done ? ' 정산 완료한 일이라 정산 기록도 함께 지워져요.' : '' ?> 되돌릴 수 없어요.">
            <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><?php if ($done): ?><input type="hidden" name="with_done" value="1"><?php endif; ?>
            <button type="submit" class="link-btn danger-link">지우기</button>
          </form>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
    <tfoot><tr><th scope="row" colspan="3"><?= count($rows) ?>건<?= count($rows) !== $total ? ' <span class="sub">(전체 ' . $total . '건 중)</span>' : '' ?></th><td class="num"><strong><?= won($feeSum) ?></strong></td><td colspan="3"></td></tr></tfoot>
  </table>
  </div>
<?php elseif ($total): ?>
  <p class="muted pad">찾는 일이 없어요. <a href="/admin/onetime/jobs">모두 보기</a></p>
<?php else: ?>
  <div class="empty-card"><p>아직 등록한 일회성 청소가 없어요.</p><a class="btn btn-primary" href="/admin/onetime/new">+ 첫 일회성 정산 추가하기</a></div>
<?php endif; ?>
</section>
