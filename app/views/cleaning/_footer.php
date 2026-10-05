<?php
/** 그린청소 공개 화면 바닥글 · 떠 있는 상담 단추 · 스크립트(</body>까지) */
$name = gc('name');
$phone = gc('phone');
$tel = tel_href($phone);
$kakao = gc('kakao_url');
$channelKey = gc('channeltalk_key');
?>
<footer class="g-footer">
  <div class="g-wrap">
    <div class="g-footer-top"><span class="g-logo g-logo-light"><?= cleaning_logo(true) ?></span><a class="g-footer-phone" href="<?= e($tel) ?>"><?= e($phone) ?></a></div>
    <div class="g-footer-info">
      <p><span>상호 <?= e($name) ?></span><?php if (gc('owner') !== ''): ?><span>대표 <?= e(gc('owner')) ?></span><?php endif; ?><?php if (gc('biz_number') !== ''): ?><span>사업자등록번호 <?= e(gc('biz_number')) ?></span><?php endif; ?><?php if (gc('biz_type') !== '' || gc('biz_item') !== ''): ?><span>업태 <?= e(gc('biz_type')) ?> · 종목 <?= e(gc('biz_item')) ?></span><?php endif; ?></p>
      <p><?php if (gc('address') !== ''): ?><span>주소 <?= e(gc('address')) ?></span><?php endif; ?><?php if (gc('email') !== ''): ?><span>이메일 <a href="mailto:<?= e(gc('email')) ?>"><?= e(gc('email')) ?></a></span><?php endif; ?><?php if (gc('area') !== ''): ?><span>서비스 지역 <?= e(gc('area')) ?></span><?php endif; ?></p>
    </div>
    <p class="g-copy">© <?= e($name) ?>. All rights reserved. <a href="/privacy">개인정보처리방침</a><?php if (blog_has_posts()): ?> <a href="/blog">블로그</a><?php endif; ?></p>
  </div>
</footer>

<?php if ($channelKey !== ''): ?>
<a class="g-chat-float" href="#" data-open-chat aria-label="채팅 상담"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg><span>채팅 상담</span></a>
<?php endif; ?>
<?php if ($kakao !== ''): ?>
<a class="g-kakao-float" href="<?= e($kakao) ?>" target="_blank" rel="noopener" aria-label="카카오톡 상담"><?= cleaning_kakao_icon() ?></a>
<?php endif; ?>
<?php if ($channelKey === ''): /* 모바일 아래 빠른 문의 막대는 채널톡을 쓰지 않을 때만 */ ?>
<nav class="g-mobile-bar<?= $kakao !== '' ? '' : ' no-kakao' ?>" aria-label="빠른 문의">
  <a class="g-mb-call" href="<?= e($tel) ?>"><?= cleaning_phone_icon() ?>전화</a>
<?php if ($kakao !== ''): ?>  <a class="g-mb-kakao" href="<?= e($kakao) ?>" target="_blank" rel="noopener"><?= cleaning_kakao_icon() ?>카톡</a>
<?php endif; ?>
  <a class="g-mb-quote" href="#quote" data-go-quote>무료 견적</a>
</nav>
<?php endif; ?>
<script src="<?= e(cleaning_asset('green.js')) ?>" defer></script>
<?= custom_code('body') ?>
</body>
</html>
