<?php /** 그린청소 관리자 › 후기 관리. 변수: $reviews, $errors */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>후기 관리</h1>
    <p class="muted">홈페이지 ‘맡겨본 분들의 이야기’에 보일 고객 후기예요(최대 <?= CLEANING_REVIEW_MAX ?>개). 글이 비어 있는 칸은 빠지고, 모두 비우면 후기 구역이 숨겨져요.</p>
  </div>
  <button type="submit" form="review-form" class="btn btn-primary">저장하기</button>
</div>
<div class="notice-box"><p>실제 고객이 남긴 후기만 올려 주세요. 지어낸 후기나 부풀린 후기는 표시·광고법 위반이 될 수 있어요. 작성자 이름은 저장할 때 ‘장**’처럼 자동으로 가려지고, 어디를 청소했는지 자세한 장소는 적지 않는 게 좋아요.</p></div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="/admin/reviews" id="review-form" class="review-edit-list">
  <?= csrf_field() ?>
<?php for ($i = 0; $i < CLEANING_REVIEW_MAX; $i++): $r = $reviews[$i] ?? array('', ''); ?>
  <section class="card stack review-edit" aria-labelledby="rv-<?= $i ?>">
    <h2 id="rv-<?= $i ?>">후기 <?= $i + 1 ?><?= $r[0] === '' ? ' <span class="sub">(비어 있음)</span>' : '' ?></h2>
    <div class="field">
      <label for="rv-text-<?= $i ?>">후기 내용 (200자까지)</label>
      <textarea id="rv-text-<?= $i ?>" name="review_text_<?= $i ?>" rows="3" maxlength="200" placeholder="예: 매주 같은 분이 오셔서 따로 설명할 필요가 없어요."><?= e($r[0]) ?></textarea>
    </div>
    <div class="field">
      <label for="rv-who-<?= $i ?>">작성자 이름 (저장하면 장**처럼 가려져요)</label>
      <input id="rv-who-<?= $i ?>" name="review_who_<?= $i ?>" type="text" maxlength="40" placeholder="예: 장혜진 → 장**" value="<?= e($r[1]) ?>">
    </div>
  </section>
<?php endfor; ?>
</form>
