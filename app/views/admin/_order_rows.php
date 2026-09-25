<?php /** 주문 표(대시보드·주문 내역 공용). 변수: $orders, $back */ ?>
<div class="table-wrap">
<table class="table">
  <thead>
    <tr><th scope="col">주문</th><th scope="col">주문자 · 입금자명</th><th scope="col">전자책</th><th scope="col" class="num">금액</th><th scope="col">상태</th><th scope="col"><span class="sr-only">처리</span></th></tr>
  </thead>
  <tbody>
<?php foreach ($orders as $o): ?>
    <tr>
      <td><a href="/orders/<?= e($o['order_no']) ?>" class="mono"><?= e($o['order_no']) ?></a><div class="sub"><?= e(fmt_date($o['created_at'], 'Y.m.d H:i')) ?></div></td>
      <td><?= e($o['user_name'] ?? '(탈퇴)') ?><div class="sub"><?php if (is_free_order($o)): ?>무료로 받음<?php else: ?>입금자 <strong><?= e($o['depositor']) ?></strong><?php endif; ?> · <?= e($o['user_email'] ?? '') ?></div></td>
      <td><?= e($o['items'] ? $o['items'][0]['title'] : '') ?><?= count($o['items']) > 1 ? ' 외 ' . (count($o['items']) - 1) . '권' : '' ?></td>
      <td class="num"><?= e(price_label($o['total'])) ?></td>
      <td>
<?php if (is_free_order($o)): ?>
        <span class="status status-free">무료</span>
        <div class="sub"><?= e(fmt_date($o['paid_at'], 'm.d H:i')) ?> 받음</div>
      </td>
      <td class="actions"></td>
    </tr>
<?php continue; endif; ?>
        <span class="status status-<?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']]) ?></span>
<?php if ($o['status'] === 'pending' && $o['due_at']): ?>        <div class="sub<?= strtotime($o['due_at']) < time() ? ' overdue' : '' ?>">기한 <?= e(fmt_date($o['due_at'], 'm.d')) ?></div>
<?php elseif ($o['status'] === 'paid'): ?>        <div class="sub"><?= e(fmt_date($o['paid_at'], 'm.d H:i')) ?> 확인</div>
<?php endif; ?>
      </td>
      <td class="actions">
<?php if ($o['status'] === 'pending'): ?>
        <form method="post" action="/admin/orders/<?= (int) $o['id'] ?>/status" data-confirm="<?= e($o['depositor']) ?> 님의 <?= e(won($o['total'])) ?> 입금을 확인했나요?">
          <?= csrf_field() ?><input type="hidden" name="to" value="paid"><input type="hidden" name="back" value="<?= e($back) ?>">
          <button type="submit" class="btn btn-primary btn-sm">입금 확인</button>
        </form>
        <form method="post" action="/admin/orders/<?= (int) $o['id'] ?>/status" data-confirm="이 주문을 취소할까요?">
          <?= csrf_field() ?><input type="hidden" name="to" value="cancelled"><input type="hidden" name="back" value="<?= e($back) ?>">
          <button type="submit" class="btn btn-ghost btn-sm">취소</button>
        </form>
<?php else: ?>
        <form method="post" action="/admin/orders/<?= (int) $o['id'] ?>/status" data-confirm="입금 대기 상태로 되돌릴까요?<?= $o['status'] === 'paid' ? ' 주문자는 다운로드할 수 없게 돼요.' : '' ?>">
          <?= csrf_field() ?><input type="hidden" name="to" value="pending"><input type="hidden" name="back" value="<?= e($back) ?>">
          <button type="submit" class="btn btn-ghost btn-sm">되돌리기</button>
        </form>
<?php endif; ?>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
