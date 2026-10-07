<?php /** 그린청소 관리자 › 블로그 글 목록. 변수: $posts, $drafts(준비된 초안), $usedTitles(이미 쓴 제목) */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>블로그</h1>
    <p class="muted">여기서 쓴 글은 그린청소.com/blog 에 올라가고, 사이트맵·RSS에 자동으로 들어가 네이버·구글 검색에 나와요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/blog/new">+ 새 글 쓰기</a>
</div>
<section class="card flush">
<?php if ($posts): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">제목</th><th scope="col">상태</th><th scope="col">작성일</th><th scope="col" class="num">조회</th><th scope="col"><span class="sr-only">보기</span></th></tr></thead>
    <tbody>
<?php foreach ($posts as $p): $public = blog_is_public($p); ?>
      <tr>
        <td><a class="strong" href="/admin/blog/<?= (int) $p['id'] ?>/edit"><?= e($p['title']) ?></a><div class="sub"><?= e(str_cut(blog_desc($p, 60), 60)) ?></div></td>
        <td><span class="status <?= $public ? 'status-paid' : 'status-pending' ?>"><?= $public ? '공개' : ($p['status'] === 'published' ? '예약' : '임시저장') ?></span></td>
        <td class="nowrap"><?= e(fmt_date($p['published_at'] ?: $p['created_at'], 'Y.m.d')) ?></td>
        <td class="num"><?= number_format((int) $p['views']) ?></td>
        <td class="actions"><a class="btn btn-outline btn-sm" href="<?= e(blog_url($p)) ?>" target="_blank" rel="noopener"><?= $public ? '보기 ↗' : '미리보기 ↗' ?></a></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <div class="empty-card"><p>아직 쓴 글이 없어요.</p><p class="sub">지역 이름(음성 · 금왕 · 대소 · 진천 · 혁신도시)과 청소 종류를 제목에 넣으면 검색에 잘 나와요.</p><a class="btn btn-primary" href="/admin/blog/new">+ 첫 글 쓰기</a></div>
<?php endif; ?>
</section>
<section class="card stack" aria-labelledby="drafts-title">
  <div class="card-intro"><h2 id="drafts-title">준비된 초안 <span class="sub">(<?= count($drafts) ?>개)</span></h2><p class="muted">검색어마다 하나씩 써 둔 초안이에요. ‘이 초안으로 쓰기’를 누르면 글쓰기 화면에 채워져요. 현장 사진을 넣고 우리 업체와 다른 내용을 고친 뒤 올리세요. 일주일에 1~2개씩 올리면 좋아요.</p></div>
  <ul class="draft-list">
<?php foreach ($drafts as $i => $d): $used = isset($usedTitles[$d[0]]); ?>
    <li>
      <div><span class="strong"><?= e($d[0]) ?></span><div class="sub">키워드: <?= e($d[1]) ?></div></div>
<?php if ($used): ?>      <span class="status status-paid">씀</span>
<?php else: ?>      <a class="btn btn-outline btn-sm" href="/admin/blog/new?draft=<?= $i + 1 ?>">이 초안으로 쓰기</a>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ul>
</section>
