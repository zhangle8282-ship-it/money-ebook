<?php /** 관리자 › 블로그 › 자동 글쓰기. 변수: $plan(auto_plan), $newToken(방금 만든 열쇠, 한 번만), $posts(최근 자동 글), $last, $rejects(최근 거절) */
$on = $plan['enabled'];
$hasToken = gc('auto_token_hash') !== '';
$k = AUTO_KINDS[$plan['kind']];
$cnt = $plan['photos'];
?>
<div class="page-head">
  <div class="page-head-text">
    <h1>블로그</h1>
    <p class="muted">매일 Claude가 ‘지역 + 정기청소’ 키워드로 네이버 블로그 스타일 글을 하나 쓰고, AI 흔적을 지운 뒤 보내 줘요. 받은 글은 <b>하루 1개</b>, 날마다 다른 시각에 공개되도록 바로 예약되고, 대표 사진은 <a href="/admin/blog/stock">사진 창고</a>에서 아직 안 쓴 현장 사진을 골라 붙여요.</p>
  </div>
</div>
<?= view('admin/_blog_tabs', array('tab' => 'auto')) ?>

<?php if ($newToken !== ''): ?>
<section class="card stack token-card" aria-labelledby="t-new">
  <h2 id="t-new">새 비밀 열쇠 <span class="sub">(지금 한 번만 보여요)</span></h2>
  <p class="token-value mono"><?= e($newToken) ?></p>
  <div><button type="button" class="btn btn-primary btn-sm" data-copy-text="<?= e($newToken) ?>">열쇠 복사</button></div>
  <p class="sub">이 열쇠는 <b>Claude 예약 작업의 비밀 설정(환경 변수)</b>에만 넣어 주세요. 채팅이나 메모에 붙여 넣지 마세요. 잃어버리면 새로 만들면 되고, 새로 만들면 예전 열쇠는 바로 못 써요.</p>
</section>
<?php endif; ?>

<form method="post" action="/admin/blog/auto" class="card stack-lg" aria-labelledby="a-set">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <div class="card-head submit-head">
    <div class="card-intro"><h2 id="a-set">자동 글쓰기 설정</h2></div>
    <span class="status <?= $on ? 'status-paid' : 'status-cancelled' ?>"><?= $on ? '켜짐' : '꺼짐' ?></span>
  </div>
  <label class="check-row"><input type="checkbox" name="auto_on" value="1"<?= $on ? ' checked' : '' ?>> <span>자동 글 받기 · 예약하기<small>끄면 새 글을 받지 않아요. 이미 예약된 글은 그대로 공개돼요.</small></span></label>
  <div class="field">
    <span class="field-label-strong">공개 시간대</span>
    <div class="auto-window"><label class="sr-only" for="a-from">시작</label><input id="a-from" name="auto_from" type="time" value="<?= e(gc('auto_from') ?: '08:30') ?>"><span>~</span><label class="sr-only" for="a-to">끝</label><input id="a-to" name="auto_to" type="time" value="<?= e(gc('auto_to') ?: '20:30') ?>"></div>
    <p class="field-help">이 시간대 안에서 <b>날마다 다른 시각</b>(분 단위까지)에 공개돼요. 매일 같은 시각에 올라오면 네이버가 자동 글로 보고 검색에서 빠뜨릴 수 있어서예요. 전날과 1시간 30분 넘게 떨어지게 골라요.</p>
  </div>
  <label class="check-row"><input type="checkbox" name="need_note" value="1"<?= gc('auto_need_note') === '1' ? ' checked' : '' ?>> <span>경험 노트가 없으면 그날은 쉬기<small>끄면 노트가 없을 때 일반 주제(주기 · 체크리스트 · 자주 묻는 질문 등)로 써요. 켜면 <a href="/admin/blog/notes">경험 노트</a>가 있을 때만 올라가요.</small></span></label>
  <div><button type="submit" class="btn btn-primary">저장하기</button></div>
</form>

<section class="card stack" aria-labelledby="a-next">
  <h2 id="a-next">다음에 쓸 글</h2>
  <dl class="auto-plan">
    <div><dt>공개 예정</dt><dd><?= $plan['publish_date'] ? e(fmt_date($plan['publish_date'], 'Y.m.d')) . ' · ' . e($plan['publish_window']) . ' 사이 어느 때' : '<span class="warn-text">' . AUTO_QUEUE_DAYS . '일 뒤까지 예약이 꽉 찼어요</span>' ?></dd></div>
    <div><dt>바탕</dt><dd><?= $plan['experience'] ? '경험 노트 <b>‘' . e($plan['experience']['title']) . '’</b>' . ($plan['experience']['photos'] ? ' · 현장 사진 ' . (int) $plan['experience']['photos'] . '장' : '') : ($plan['skip_today'] ? '<span class="warn-text">경험 노트가 없어 쉬어요</span> → <a href="/admin/blog/notes">경험 노트 적기</a>' : '일반 주제 (경험 노트가 없어서) → <a href="/admin/blog/notes">경험 노트를 적으면 더 좋은 글이 돼요</a>') ?></dd></div>
    <div><dt>키워드</dt><dd><b><?= e($plan['keyword']) ?></b></dd></div>
    <div><dt>제목에 들어갈 말</dt><dd><?= e($plan['title_must_include']) ?></dd></div>
<?php if (!$plan['experience']): ?>    <div><dt>주제</dt><dd><?= e($plan['angle']) ?></dd></div>
<?php endif; ?>
    <div><dt>대표 사진</dt><dd><?= $plan['photo_left'] ? '사진 창고의 ‘' . e($k[0]) . '’ 사진 중 안 쓴 사진 (' . (int) $plan['photo_left'] . '장 남음)' : '<span class="warn-text">‘' . e($k[0]) . '’ 사진이 없어 대표 사진 없이 올라가요 → <a href="/admin/blog/stock">사진 창고</a>에 올려 주세요</span>' ?></dd></div>
  </dl>
  <p class="sub">글 종류는 20개마다 사무실 8 · 공장 7 · 화장실 3 · 건물상가 2 비율로, 지역(<?= e(implode(' · ', AUTO_REGIONS)) ?>)과 주제는 돌아가며 정해져요. 제목에는 ‘사무실정기청소 · 공장청소 · 화장실청소 · 건물상가청소’ 중 하나가 꼭 들어가고, 키워드는 ‘지역 + ○○정기청소’ 형태여야 받아요.</p>
</section>

<section class="card stack" aria-labelledby="a-check">
  <h2 id="a-check">누락 막는 자동 검사</h2>
  <ul class="auto-checks">
    <li><b>유사문서:</b> 최근 글 30개와 <?= round(AUTO_SIMILAR_MAX * 100) ?>% 넘게 겹치면 안 받아요.</li>
    <li><b>키워드 남용:</b> 대표 키워드가 제목 + 본문에 <?= AUTO_KEYWORD_MAX ?>번 넘게 들어가면 안 받아요.</li>
    <li><b>과장 · 광고:</b> <?= e(implode(' · ', AUTO_BANNED)) ?> 같은 말이 있으면 안 받아요.</li>
    <li><b>길이:</b> 본문 <?= number_format(AUTO_MIN_TEXT) ?>~<?= number_format(AUTO_MAX_TEXT) ?>자만 받아요.</li>
  </ul>
  <p class="sub">걸리면 이유를 글 쓰는 Claude에게 돌려줘서 고쳐 다시 보내요. 그래도 안 되면 그날은 건너뛰어요.</p>
<?php if ($rejects): ?>
  <details class="g-index"><summary>최근 거절 <?= count($rejects) ?>건</summary>
    <ul class="auto-rejects">
<?php foreach ($rejects as $r): ?>      <li><span class="sub"><?= e(fmt_date($r['at'] ?? '', 'm.d H:i')) ?></span> <b><?= e(str_cut((string) ($r['title'] ?? ''), 40)) ?></b><br><span class="sub"><?= e(implode(' / ', (array) ($r['errors'] ?? array()))) ?></span></li>
<?php endforeach; ?>
    </ul>
  </details>
<?php endif; ?>
</section>

<section class="card stack" aria-labelledby="a-photo">
  <h2 id="a-photo">사진 창고 남은 사진</h2>
  <ul class="stock-left">
<?php foreach (AUTO_KINDS as $key => $kk): $n = (int) $cnt[$key]; ?>
    <li class="<?= $n < 3 ? 'is-low' : '' ?>"><span><?= e($kk[0]) ?></span><b><?= $n ?>장</b></li>
<?php endforeach; ?>
    <li><span>기타 (어느 글에나)</span><b><?= (int) $cnt['etc'] ?>장</b></li>
  </ul>
  <p class="sub">하루 1개씩 쓰니 한 달이면 사진 약 30장이 필요해요(사무실 · 공장 사진이 더 많이 쓰여요). 3장보다 적으면 주황색으로 알려 드려요. <a href="/admin/blog/stock">사진 창고에 올리기 ›</a></p>
</section>

<section class="card stack" aria-labelledby="a-key">
  <h2 id="a-key">글 받는 통로</h2>
  <p class="muted">Claude 예약 작업이 이 주소로 글을 보내요. 비밀 열쇠가 있어야만 열려요.</p>
  <ul class="submit-urls">
    <li><div class="submit-main"><span class="submit-kind">계획 받기</span><span class="submit-url mono">GET <?= e(display_url(base_url())) ?>/api/auto/plan</span></div></li>
    <li><div class="submit-main"><span class="submit-kind">글 보내기</span><span class="submit-url mono">POST <?= e(display_url(base_url())) ?>/api/auto/post</span></div></li>
  </ul>
  <p class="sub">비밀 열쇠: <?= $hasToken ? '<b>있음</b> (' . e(fmt_date(gc('auto_token_at'), 'Y.m.d H:i')) . ' 만듦' . (gc('auto_token_hint') !== '' ? ' · 앞자리 <b class="mono">' . e(gc('auto_token_hint')) . '</b>, 길이 51자' : '') . ')' : '<span class="warn-text">아직 없어요</span>' ?></p>
<?php $fail = json_decode((string) gc('auto_fail'), true); if (is_array($fail) && !empty($fail['at'])): ?>
  <p class="notice-box">마지막으로 열쇠가 틀린 요청: <?= e(fmt_date($fail['at'], 'm.d H:i')) ?> · <?= $fail['reason'] === 'none'
      ? '<b>열쇠가 아예 오지 않았어요.</b> Claude 환경의 네트워크 비밀값에서 Allowed websites(<span class="mono">xn--2i0b75tqkgu2l.com</span>)와 헤더 이름(<span class="mono">X-Auto-Token</span>)을 확인해 주세요.'
      : '<b>열쇠는 왔는데 값이 달라요.</b> 받은 열쇠 앞자리 <b class="mono">' . e($fail['hint']) . '</b>, 길이 ' . (int) $fail['len'] . '자(' . e($fail['header']) . ' 헤더). 위의 지금 열쇠 앞자리와 길이가 같은지 비교해 주세요. 앞뒤 빈칸이나 줄바꿈이 들어갔을 수도 있어요.' ?></p>
<?php endif; ?>
  <form method="post" action="/admin/blog/auto" data-confirm="<?= $hasToken ? '새 열쇠를 만들면 지금 열쇠는 바로 못 써요. Claude 예약 작업의 열쇠도 새것으로 바꿔야 해요. 만들까요?' : '비밀 열쇠를 만들까요?' ?>">
    <?= csrf_field() ?><input type="hidden" name="action" value="token">
    <button type="submit" class="btn btn-outline btn-sm"><?= $hasToken ? '열쇠 새로 만들기' : '비밀 열쇠 만들기' ?></button>
  </form>
</section>

<section class="card flush" aria-labelledby="a-list">
  <div class="card-head pad-head"><h2 id="a-list">최근 자동 글</h2></div>
<?php if ($posts): ?>
  <div class="table-wrap">
  <table class="table auto-posts">
    <thead><tr><th scope="col">대표 사진</th><th scope="col">제목</th><th scope="col">공개</th><th scope="col">상태</th></tr></thead>
    <tbody>
<?php foreach ($posts as $p): $public = blog_is_public($p); ?>
      <tr>
        <td><?= $p['cover'] !== '' ? '<img class="auto-thumb" src="' . e($p['cover']) . '" alt="">' : '<span class="sub">없음</span>' ?></td>
        <td><a class="strong" href="/admin/blog/<?= (int) $p['id'] ?>/edit"><?= e($p['title']) ?></a><div class="sub"><?= e(str_cut($p['keywords'], 60)) ?></div></td>
        <td class="nowrap"><?= e(fmt_date($p['published_at'], 'm.d H:i')) ?></td>
        <td><span class="status <?= $public ? 'status-paid' : 'status-pending' ?>"><?= $public ? '공개됨' : '예약' ?></span></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <p class="muted pad">아직 자동으로 올라온 글이 없어요.</p>
<?php endif; ?>
<?php if (is_array($last)): ?>  <p class="sub pad">마지막으로 받은 글: <?= e(fmt_date($last['at'], 'm.d H:i')) ?> · <?= e($last['title']) ?><?= empty($last['photo']) ? ' · 대표 사진 없음' : '' ?></p>
<?php endif; ?>
</section>
