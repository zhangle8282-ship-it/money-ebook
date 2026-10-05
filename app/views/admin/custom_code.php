<?php /** 그린청소 관리자 › 헤드 코드. 변수: $values, $errors */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>헤드 코드</h1>
    <p class="muted">구글 애널리틱스, 네이버 애널리틱스, 광고 픽셀, 사이트 확인 태그처럼 홈페이지 <code>&lt;head&gt;</code>에 넣으라는 코드를 붙여 넣어요. 홈페이지 모든 화면(첫 화면 · 블로그 · 개인정보처리방침)에 들어가고, 관리자 화면에는 들어가지 않아요.</p>
  </div>
  <button type="submit" form="code-form" class="btn btn-primary">저장하기</button>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" action="/admin/code" id="code-form" class="stack-lg" data-code-form>
  <?= csrf_field() ?>
  <section class="card stack" aria-labelledby="c-head">
    <div class="code-head">
      <h2 id="c-head">&lt;head&gt; 안에 넣을 코드</h2>
      <span class="sub" data-code-count="head_code">0줄 · 0자</span>
    </div>
    <textarea id="head_code" name="head_code" class="code-area" rows="16" spellcheck="false" autocomplete="off" autocapitalize="off" placeholder="<!-- 예: Google tag (gtag.js) -->
<script async src=&quot;https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX&quot;></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXX');
</script>"><?= e($values['head']) ?></textarea>
    <p class="field-help">받은 코드를 그대로 붙여 넣으세요. 여러 개면 줄을 바꿔 이어서 넣으면 돼요. Tab 키는 들여쓰기로 들어가요.</p>
  </section>

  <section class="card stack" aria-labelledby="c-body">
    <div class="code-head">
      <h2 id="c-body">&lt;/body&gt; 바로 앞에 넣을 코드 <span class="sub">(선택)</span></h2>
      <span class="sub" data-code-count="body_code">0줄 · 0자</span>
    </div>
    <textarea id="body_code" name="body_code" class="code-area code-area-sm" rows="8" spellcheck="false" autocomplete="off" autocapitalize="off" placeholder="본문 맨 끝에 넣으라고 안내된 코드만 여기에 넣어요. (예: 네이버 애널리틱스 wcslog)"><?= e($values['body']) ?></textarea>
  </section>

  <section class="card stack" aria-labelledby="c-on">
    <h2 id="c-on">적용</h2>
    <label class="check-row"><input type="checkbox" name="enabled" value="1"<?= $values['enabled'] ? ' checked' : '' ?>> <span>홈페이지에 코드 넣기<small>코드 때문에 화면이 이상해지면 체크를 끄고 저장하세요. 코드는 지우지 않고 그대로 남아요.</small></span></label>
  </section>
</form>
