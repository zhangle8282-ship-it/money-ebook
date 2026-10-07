<?php /** 그린청소 관리자 › 검색어 페이지 목록. 변수: $pages */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>검색어 페이지</h1>
    <p class="muted">검색어(지역 · 업종)마다 따로 있는 소개 페이지예요. 한 페이지가 한 검색어만 깊게 다뤄야 그 검색어에서 잘 나와요. 첫 화면의 ‘지역 · 업종별 청소’와 바닥글에 자동으로 연결되고, 사이트맵에도 들어가요.</p>
  </div>
  <a class="btn btn-primary" href="/admin/pages/new">+ 새 페이지</a>
</div>
<div class="notice-box"><p>처음 넣어 둔 페이지는 홈페이지에 있던 내용으로 쓴 기본 글이에요. 실제와 다른 내용은 고치고, <b>현장 사진</b>을 넣으면 검색에 더 잘 나와요. 첫 화면이 ‘충북음성청소업체’를 맡고 있으니 같은 검색어로 페이지를 또 만들지는 마세요.</p></div>
<section class="card flush">
<?php if ($pages): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">페이지</th><th scope="col">주소</th><th scope="col">상태</th><th scope="col" class="num">순서</th><th scope="col">고친 날</th><th scope="col"><span class="sr-only">보기</span></th></tr></thead>
    <tbody>
<?php foreach ($pages as $p): $public = $p['status'] === 'published'; ?>
      <tr>
        <td><a class="strong" href="/admin/pages/<?= (int) $p['id'] ?>/edit"><?= e($p['title']) ?></a><div class="sub"><?= e(str_cut($p['summary'], 60)) ?></div></td>
        <td class="nowrap">/<?= e($p['slug']) ?></td>
        <td><span class="status <?= $public ? 'status-paid' : 'status-cancelled' ?>"><?= e(LANDING_STATUS[$p['status']] ?? $p['status']) ?></span></td>
        <td class="num"><?= (int) $p['sort'] ?></td>
        <td class="nowrap"><?= e(fmt_date($p['updated_at'], 'Y.m.d')) ?></td>
        <td class="actions"><a class="btn btn-outline btn-sm" href="<?= e(landing_url($p)) ?>" target="_blank" rel="noopener"><?= $public ? '보기 ↗' : '미리보기 ↗' ?></a></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <div class="empty-card"><p>아직 검색어 페이지가 없어요.</p><p class="sub">예: 음성 공장 청소, 진천 상가 청소처럼 지역과 청소 종류를 제목으로 만들어 보세요.</p><a class="btn btn-primary" href="/admin/pages/new">+ 첫 페이지 만들기</a></div>
<?php endif; ?>
</section>
