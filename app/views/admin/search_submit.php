<?php
/** 그린청소 관리자 › 검색 등록. 변수: $values, $errors, $base, $key, $entries, $posts, $log, $local */
$v = function ($k) use ($values) {
    return e(isset($values[$k]) && is_string($values[$k]) ? $values[$k] : '');
};
$home = $base . '/';
$sitemap = $base . '/sitemap.xml';
$rss = $base . '/rss.xml';
$copy = function ($text, $label = '복사') {
    return '<button type="button" class="btn btn-outline btn-sm" data-copy-text="' . e($text) . '">' . e($label) . '</button>';
};
$state = function ($k) use ($values) {
    return !empty($values[$k]) ? '<span class="status status-paid">확인 코드 넣음</span>' : '<span class="status status-cancelled">아직</span>';
};
$on = ($values['gc_indexnow_on'] ?? '1') === '1';
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>검색 등록</h1>
    <p class="muted">네이버 · 구글 · 다음 · 빙이 홈페이지와 블로그 글을 빨리 찾아가도록 사이트맵과 RSS를 알려요. 검색 사이트 등록은 한 번만 하면 되고, 그다음부터 새 글이나 바뀐 내용은 자동으로 알려요.</p>
  </div>
  <button type="submit" form="search-form" class="btn btn-primary">저장하기</button>
</div>
<?php if ($errors): ?>
<div class="alert" role="alert"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<section class="card stack" aria-labelledby="s-urls">
  <div class="card-intro"><h2 id="s-urls">제출할 주소</h2><p class="muted">검색 사이트의 ‘사이트맵 제출’ · ‘RSS 제출’ 칸에 넣는 주소예요. robots.txt에도 적어 두어서 검색 로봇이 스스로도 찾아가요.</p></div>
  <ul class="submit-urls">
    <li>
      <div class="submit-main"><span class="submit-kind">사이트맵</span><a class="submit-url" href="/sitemap.xml" target="_blank" rel="noopener"><?= e(display_url($sitemap)) ?></a><span class="sub">주소 <?= (int) $entries ?>개 · 첫 화면, 개인정보처리방침, 블로그 글이 모두 들어 있어요</span></div>
      <?= $copy($sitemap) ?>
    </li>
    <li>
      <div class="submit-main"><span class="submit-kind">RSS</span><a class="submit-url" href="/rss.xml" target="_blank" rel="noopener"><?= e(display_url($rss)) ?></a>
<?php if ($posts): ?>
        <span class="sub">블로그 글 <?= (int) $posts ?>개 · 최근 30개 글의 본문까지 들어 있어요</span>
<?php else: ?>
        <span class="sub warn-text">아직 공개한 블로그 글이 없어요. 글을 1개 이상 공개한 뒤 RSS를 제출하세요.</span>
<?php endif; ?>
      </div>
      <?= $copy($rss) ?>
    </li>
    <li>
      <div class="submit-main"><span class="submit-kind">robots.txt</span><a class="submit-url" href="/robots.txt" target="_blank" rel="noopener"><?= e(display_url($base . '/robots.txt')) ?></a><span class="sub">관리자 화면은 막고, 사이트맵<?= $posts ? ' · RSS' : '' ?> 주소를 알려 줘요</span></div>
    </li>
  </ul>
</section>


<form method="post" action="/admin/search" id="search-form" class="stack-lg">
  <?= csrf_field() ?>
  <section class="card stack-lg" aria-labelledby="s-engines">
    <div class="card-intro"><h2 id="s-engines">검색 사이트에 등록하기 <span class="sub">(사이트마다 한 번)</span></h2><p class="muted">검색 사이트마다 내 계정으로 로그인해서 ‘이 홈페이지 주인’임을 확인하고 사이트맵 · RSS를 제출해요. 확인 코드를 아래 칸에 붙여 넣고 저장하면 홈페이지에 바로 들어가요.</p></div>

    <div class="engine">
      <div class="engine-head"><h3>네이버 서치어드바이저</h3><?= $state('gc_naver_verify') ?></div>
      <ol class="engine-steps">
        <li><a href="https://searchadvisor.naver.com/console/board" target="_blank" rel="noopener">네이버 서치어드바이저</a>에 네이버 아이디로 로그인 › 웹마스터 도구 › <b>사이트 등록</b>에 홈페이지 주소를 넣어요. <?= $copy($home, '주소 복사') ?></li>
        <li>소유 확인에서 <b>HTML 태그</b>를 골라, 나온 태그를 아래 칸에 통째로 붙여 넣고 <b>저장하기</b> › 네이버 화면에서 <b>소유확인</b>을 눌러요.</li>
        <li>요청 › <b>사이트맵 제출</b>에 <code>sitemap.xml</code>을 넣고 확인. <?= $copy($sitemap, '사이트맵 주소 복사') ?></li>
        <li>요청 › <b>RSS 제출</b>에 RSS 주소를 넣고 확인. <?= $copy($rss, 'RSS 주소 복사') ?></li>
      </ol>
      <div class="field"><label for="f-naver">네이버 사이트 확인 코드</label><input id="f-naver" name="gc_naver_verify" value="<?= $v('gc_naver_verify') ?>" placeholder="&lt;meta name=&quot;naver-site-verification&quot; content=&quot;…&quot; /&gt; 를 통째로 붙여 넣어도 돼요" autocomplete="off" spellcheck="false" data-verify></div>
    </div>

    <div class="engine">
      <div class="engine-head"><h3>구글 서치 콘솔</h3><?= $state('gc_google_verify') ?></div>
      <ol class="engine-steps">
        <li><a href="https://search.google.com/search-console" target="_blank" rel="noopener">구글 서치 콘솔</a>에 구글 계정으로 로그인 › 속성 추가 › 오른쪽 <b>URL 접두어</b>에 홈페이지 주소를 넣어요. <?= $copy($home, '주소 복사') ?></li>
        <li>다른 확인 방법 › <b>HTML 태그</b>의 태그를 아래 칸에 붙여 넣고 <b>저장하기</b> › 구글 화면에서 <b>확인</b>을 눌러요.</li>
        <li>왼쪽 메뉴 <b>Sitemaps</b>에 <code>sitemap.xml</code>을 제출하고, 이어서 <code>rss.xml</code>도 제출해요. 구글은 RSS도 사이트맵으로 받아요.</li>
      </ol>
      <div class="field"><label for="f-google">구글 사이트 확인 코드</label><input id="f-google" name="gc_google_verify" value="<?= $v('gc_google_verify') ?>" placeholder="&lt;meta name=&quot;google-site-verification&quot; content=&quot;…&quot; /&gt;" autocomplete="off" spellcheck="false" data-verify></div>
    </div>

    <div class="engine">
      <div class="engine-head"><h3>다음 웹마스터도구</h3><?= $state('gc_daum_verify') ?></div>
      <ol class="engine-steps">
        <li><a href="https://webmaster.daum.net" target="_blank" rel="noopener">다음 웹마스터도구</a>에서 홈페이지 주소를 넣고 PIN 코드(비밀번호)를 정해요. <?= $copy($home, '주소 복사') ?></li>
        <li>나온 <b>robots.txt 인증 줄</b>(<code>#DaumWebMasterTool:</code>로 시작)을 아래 칸에 붙여 넣고 <b>저장하기</b> › 다음 화면에서 인증해요. 파일을 올릴 필요 없이 <a href="/robots.txt" target="_blank" rel="noopener">robots.txt</a> 끝에 자동으로 들어가요.</li>
        <li>수집 요청에 사이트맵과 RSS 주소를 넣어요. <?= $copy($sitemap, '사이트맵 주소 복사') ?> <?= $copy($rss, 'RSS 주소 복사') ?></li>
      </ol>
      <div class="field"><label for="f-daum">다음 robots.txt 인증 줄</label><input id="f-daum" name="gc_daum_verify" value="<?= $v('gc_daum_verify') ?>" placeholder="#DaumWebMasterTool:…" autocomplete="off" spellcheck="false"></div>
    </div>

    <div class="engine">
      <div class="engine-head"><h3>빙(Bing) 웹마스터 도구 <span class="sub">(선택)</span></h3><?= $state('gc_bing_verify') ?></div>
      <ol class="engine-steps">
        <li><a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">빙 웹마스터 도구</a>에 로그인해요. 구글 서치 콘솔에 먼저 등록했다면 <b>Google Search Console에서 가져오기</b>로 한 번에 끝나요.</li>
        <li>직접 추가할 때는 <b>HTML 메타 태그</b> 방식의 태그를 아래 칸에 붙여 넣고 저장 › 확인한 뒤 사이트맵을 제출해요. 빙은 아래의 ‘바로 알리기’로도 새 글을 받아요.</li>
      </ol>
      <div class="field"><label for="f-bing">빙 사이트 확인 코드</label><input id="f-bing" name="gc_bing_verify" value="<?= $v('gc_bing_verify') ?>" placeholder="&lt;meta name=&quot;msvalidate.01&quot; content=&quot;…&quot; /&gt;" autocomplete="off" spellcheck="false" data-verify></div>
    </div>
    <div><button type="submit" class="btn btn-primary">저장하기</button></div>
  </section>
</form>

<section class="card stack" id="indexnow" aria-labelledby="s-now">
  <div class="card-head submit-head">
    <div class="card-intro"><h2 id="s-now">바뀐 주소 바로 알리기 <span class="sub">(IndexNow)</span></h2>
      <p class="muted">블로그 글을 공개 · 고치기 · 지우기 하거나 홈페이지 정보 · 사진 · 후기를 바꾸면, 바뀐 주소를 <b>네이버</b>와 <b>빙(Bing)</b>에 바로 알려요. 예약한 글은 공개 시각이 지나면 알려요. 구글은 이 방식을 받지 않아서, 위에서 구글 서치 콘솔에 사이트맵을 한 번 제출해 두면 알아서 다시 읽어 가요.</p>
    </div>
    <?= $on ? '<span class="status status-paid">켜짐</span>' : '<span class="status status-cancelled">꺼짐</span>' ?>
  </div>
<?php if ($local): ?>
  <div class="notice-box"><p>지금은 내 컴퓨터 주소(<?= e(parse_url($base, PHP_URL_HOST)) ?>)로 열려 있어서 실제로 보내지는 않아요. 실제 홈페이지 관리자 화면에서는 자동으로 보내요.</p></div>
<?php endif; ?>
  <label class="check-row"><input type="checkbox" name="indexnow_on" value="1" form="search-form"<?= $on ? ' checked' : '' ?>> <span>바뀐 주소를 검색 사이트에 자동으로 알리기<small>끄면 사이트맵 · RSS만 두고, 검색 사이트가 스스로 다시 읽어 갈 때까지 기다려요.</small></span></label>
  <div><button type="submit" form="search-form" class="btn btn-outline btn-sm">켜기 · 끄기 저장</button></div>
  <div class="submit-ping">
    <form method="post" action="/admin/search"><?= csrf_field() ?><input type="hidden" name="action" value="ping_all"><button type="submit" class="btn btn-outline"<?= $on ? '' : ' disabled' ?>>지금 모두 알리기</button></form>
    <p class="field-help">사이트맵에 있는 주소 <?= (int) $entries ?>개를 한꺼번에 보내요. 검색 사이트에 처음 등록했을 때나 알림이 실패했을 때 눌러 주세요.</p>
  </div>
<?php if ($log): ?>
  <div class="table-wrap">
    <table class="table submit-log">
      <thead><tr><th>보낸 시각</th><th>무엇</th><th>주소</th><?php foreach (INDEXNOW_ENDPOINTS as $ep): ?><th><?= e($ep[0]) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
<?php foreach ($log as $row): ?>
        <tr>
          <td class="nowrap mono"><?= e(date('m.d H:i', strtotime($row['at']))) ?></td>
          <td class="nowrap"><?= e($row['why'] ?? '') ?></td>
          <td><?php $first = $row['urls'][0] ?? ''; ?><span class="submit-path"><?= e($first !== '' ? rawurldecode((string) parse_url($first, PHP_URL_PATH)) : '') ?></span><?= ($row['count'] ?? 0) > 1 ? ' <span class="sub">외 ' . ((int) $row['count'] - 1) . '개</span>' : '' ?></td>
<?php if (isset($row['skip'])): ?>
          <td colspan="<?= count(INDEXNOW_ENDPOINTS) ?>"><span class="sub">내 컴퓨터 주소라 보내지 않음</span></td>
<?php else: ?>
<?php foreach (INDEXNOW_ENDPOINTS as $id => $ep): $code = (int) ($row['codes'][$id] ?? 0); ?>
          <td class="nowrap"><span class="status <?= indexnow_ok($code) ? 'status-paid' : 'status-pending' ?>"><?= e(indexnow_code_label($code)) ?></span></td>
<?php endforeach; ?>
<?php endif; ?>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p class="sub">아직 보낸 기록이 없어요. 블로그 글을 공개하거나 ‘지금 모두 알리기’를 누르면 여기에 결과가 남아요.</p>
<?php endif; ?>
  <p class="sub">확인 파일: <a href="/<?= e($key) ?>.txt" target="_blank" rel="noopener">/<?= e($key) ?>.txt</a> — 검색 사이트가 이 파일로 우리 홈페이지가 보낸 알림인지 확인해요. 지우거나 바꿀 필요 없어요.</p>
</section>
