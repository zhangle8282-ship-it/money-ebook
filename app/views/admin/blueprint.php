<?php /** 관리자 › 틀 내보내기. 변수: $sum(blueprint_summary) */ ?>
<div class="page-head">
  <div class="page-head-text">
    <h1>틀 내보내기</h1>
    <p class="muted">다른 사이트를 만들 때 참고할 수 있게, 이 홈페이지와 관리자 화면의 <b>틀</b>(데이터 구조 · 화면 · 디자인 · 코드)을 zip 하나로 묶어 내려받아요. 실제 데이터는 넣지 않아요.</p>
  </div>
  <div class="head-actions">
    <a class="btn btn-primary" href="/admin/blueprint/download">틀 묶음 내려받기 (zip)</a>
  </div>
</div>

<section class="card stack" aria-labelledby="bp-in">
  <h2 id="bp-in">들어 있는 것</h2>
  <ul class="auto-checks">
    <li><b>설명서(README):</b> 다른 Claude 작업이 읽고 바로 이해하도록 쓴 전체 구조와 쓰는 법</li>
    <li><b>업무 규칙(areas.md):</b> 견적 문의 · 정기청소 정산 · 일회성 정산 · 인력 배치 · 홈페이지 내용 · 블로그(자동 글쓰기) · 통계 · 관리자 권한</li>
    <li><b>데이터 구조:</b> 표 <?= (int) $sum['tables'] ?>개 · 칸 <?= (int) $sum['columns'] ?>개, 칸마다 무엇을 담는지 설명과 표를 만드는 SQL(지금 DB에서 바로 뽑음)</li>
    <li><b>화면 구조:</b> 관리자 메뉴 <?= (int) $sum['menu'] ?>개, 주소 <?= (int) $sum['routes'] ?>개와 하는 일</li>
    <li><b>설정 키:</b> <?= (int) $sum['settings'] ?>개, 하는 일과 가짜 예시(실제 값 없음)</li>
    <li><b>예시 데이터:</b> 표 <?= (int) $sum['samples'] ?>개의 가짜 예시(정산 계산 예 포함)</li>
    <li><b>디자인:</b> 화면 부품 규칙, 색 · 글꼴 값, 관리자 · 홈페이지 CSS · JS 원본</li>
    <li><b>코드:</b> 이 홈페이지에 쓰는 PHP 파일 <?= (int) $sum['code'] ?>개</li>
  </ul>
</section>

<section class="card stack" aria-labelledby="bp-out">
  <h2 id="bp-out">넣지 않는 것</h2>
  <ul class="auto-checks">
    <li>실제 데이터: 견적 문의 · 고객 연락처 · 인력 · 계좌 · 정산 금액 · 블로그 글 · 올린 사진</li>
    <li>비밀 값: 관리자 비밀번호, 자동 글쓰기 비밀 열쇠, 텔레그램 토큰, 검색 사이트 확인 코드, 헤드 코드, 서버 설정 파일(config.local.php)</li>
    <li>이 홈페이지와 관계없는 전자책 스토어 코드</li>
  </ul>
</section>

<section class="card stack" aria-labelledby="bp-use">
  <h2 id="bp-use">다른 Claude 작업에서 쓰는 법</h2>
  <ol class="auto-checks">
    <li>내려받은 zip을 풀어 새 프로젝트 폴더에 <span class="mono">reference</span> 같은 이름으로 넣어요.</li>
    <li>Claude에게 이렇게 말해요.</li>
  </ol>
  <blockquote class="notice-box">reference 폴더는 다른 사이트의 틀 설계도야. README.md부터 읽고, 화면 구조 · 데이터 구조 · 디자인 · 업무 규칙을 참고해서 우리 사이트를 만들어 줘. 내용(회사 이름 · 문구 · 지역 · 서비스 종류 · 금액 비율)은 우리 것으로 바꾸고, 코드는 참고만 해.</blockquote>
  <p class="sub">구조나 화면을 바꾼 뒤 다시 내려받으면 그때의 틀이 담겨요.</p>
</section>
