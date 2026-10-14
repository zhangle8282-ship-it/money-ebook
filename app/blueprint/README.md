# 홈페이지 · 관리자 틀 설계도

이 묶음은 청소업체 홈페이지와 관리자 화면의 **틀(구조 · 화면 · 디자인 · 코드)**만 담았어요.
실제 고객 · 사람 · 금액 · 글은 하나도 들어 있지 않아요. 예시 데이터는 모두 가짜예요.
다른 업종이나 다른 내용의 사이트를 만들 때 **뼈대를 참고**하려고 만든 자료예요.

- 만든 날: {{DATE}}
- 원래 사이트 버전: {{VERSION}}
- 데이터베이스 구조 버전: {{SCHEMA}}

## 다른 Claude 작업에서 쓰는 법

이 zip을 풀어 새 프로젝트 폴더에 `reference/` 같은 이름으로 넣고, Claude에게 이렇게 말하면 돼요.

> reference 폴더는 다른 사이트의 틀 설계도야. README.md부터 읽고, 화면 구조(screens.md) · 데이터 구조(schema.md) · 디자인(design/design.md) · 업무 규칙(areas.md)을 참고해서
> 우리 사이트를 만들어 줘. 내용(회사 이름 · 문구 · 지역 · 서비스 종류 · 금액 비율)은 우리 것으로 바꾸고, 코드는 code/ 폴더를 참고만 해.

## 들어 있는 것

| 파일 | 내용 |
| --- | --- |
| README.md | 이 설명서(전체 그림과 쓰는 법) |
| areas.md | 영역별로 하는 일과 규칙(블로그 · 홈페이지 내용 · 견적 문의 · 정산 · 인력 · 통계 · 관리자 권한) |
| screens.md · screens.json | 관리자 메뉴, 화면 주소와 하는 일, 공개 화면 주소 |
| schema.md · schema.json · schema.sql | 표(테이블)와 칸마다 무엇을 담는지, 표를 만드는 SQL |
| settings.md · settings.json | 설정 키(gc_…)마다 하는 일과 가짜 예시(실제 값 없음, 비밀 키 표시) |
| samples/ | 표마다 가짜 예시 데이터(JSON) |
| design/ | 디자인 설명(design.md), 색 · 글꼴 값(tokens.json), 화면 CSS · JS 원본 |
| code/ | 이 틀을 만드는 PHP 코드(설정 파일 · 비밀 값 제외) |

## 기술 구조 한눈에

- **PHP 7.4 이상, 프레임워크 없음.** 파일 몇 개로 돌아가는 단순한 구조예요.
- **데이터베이스:** PDO. 기본은 파일 하나짜리 SQLite이고, `config.local.php`로 MySQL로 바꿀 수 있어요.
  표는 `code/app/db.php`의 `migrate()`가 `settings.schema_version` 숫자를 보고 차례로 만들고 고쳐요.
- **요청 흐름:** `public/index.php` → `app/bootstrap.php`(설정 · DB · 공통 함수) → `app/routes.php`의 `dispatch()`가
  주소를 `app/pages/cleaning.php`의 `cleaning_routes()` 표와 맞춰 보고 → 처리 함수(`app/pages/*.php`) → 화면 틀(`app/views/*.php`).
- **화면:** PHP 템플릿. 글자는 항상 `e()`로 감싸서 내보내요(XSS 막기). 관리자 화면은 `render_admin('이름', [...])`이 `views/admin/layout.php` 안에 넣어 그려요.
- **설정:** `settings` 표(키 · 값). 이 사이트 키는 모두 `gc_`로 시작하고, 기본값은 `cleaning_defaults()`에 있어요.
- **보안 습관:** 관리자 화면은 `require_admin()`, 글을 바꾸는 요청은 `require_csrf()`, 업무만 하는 아이디는 `admin_can_open()`으로 주소를 막아요.
  비밀번호는 `password_hash`로만 저장해요.
- **사진:** 올릴 때 자동으로 가볍게 줄여요(`files.php`의 `image_shrink`: 긴 변 1280~1600px, JPEG 품질 80, 위치 정보 등 메타데이터 제거).
- **배포:** `app/`은 웹에서 열리지 않는 서버 바깥 폴더로, `public/`은 웹 폴더(www)로 올려요. 올린 사진(`uploads`)과 `config.local.php`는 배포가 건드리지 않아요.

## 영역 지도

| 영역 | 관리자 화면 | 공개 화면 | 표 |
| --- | --- | --- | --- |
| 견적 문의 · 고객 | /admin/inquiries | 첫 화면 견적 문의 칸 → POST /inquiry | inquiries |
| 정기청소 정산 | /admin/contracts … | (없음) | contracts, contract_settlements, partners |
| 일회성 정산 | /admin/onetime … | (없음) | onetime_jobs, partners |
| 인력 배치 | /admin/workers … | (없음) | workers |
| 홈페이지 내용 | /admin/site · /admin/photos · /admin/reviews · /admin/code | / · /privacy | settings |
| 검색어 페이지 | /admin/pages … | /{검색어} | landing_pages |
| 블로그 | /admin/blog … (자동 글쓰기 · 사진 창고 · 경험 노트) | /blog · /blog/{번호}-{주소} · /rss.xml | blog_posts, photo_stock, experience_notes |
| 검색 등록 · 통계 | /admin/search · /admin/visits · /admin/usage | /sitemap.xml · /robots.txt | visit_sources, server_stats, settings |
| 관리자 | /admin/login · /admin/account | (없음) | admins |

관리자 메뉴는 두 묶음이에요. **업무**(녹색: 견적 문의 · 정기청소 정산 · 일회성 정산 · 인력 배치)와
**홈페이지 관리**(주황색: 나머지). 권한이 `work`인 아이디는 업무 묶음만 보여요.

## 다른 사이트에 맞게 바꿀 곳

- 회사 정보 · 문구: `settings`의 `gc_` 키들(settings.md), 첫 화면 문구는 `code/app/cleaning.php`의 `cleaning_defaults()` · `cleaning_services()`
- 서비스 종류: `CLEANING_KINDS`(문의 종류), `AUTO_KINDS`(블로그 자동 글 종류)
- 지역 목록: `WORKER_REGIONS`(인력), `AUTO_REGIONS`(블로그 자동 글)
- 정산 규칙: `CONTRACT_TAX_RATE` 10, `CONTRACT_WITHHOLDING` 3.3, `CONTRACT_RATES` 10·20·30, `CONTRACT_GAP_DEFAULT` 60, `ONETIME_GAP` 50
- 설정 키 앞글자 `gc_`: 새 사이트라면 다른 앞글자로 바꿔도 돼요
- 비밀 값(열쇠 · 토큰 · 사이트 확인 코드)은 새 사이트에서 **새로 만들어야** 해요

## 넣지 않은 것

- 실제 데이터(문의 · 고객 · 인력 · 계좌 · 정산 금액 · 블로그 글 · 사진)
- 관리자 비밀번호 해시, 비밀 열쇠 · 토큰 · 사이트 확인 코드, 헤드 코드
- 서버 설정 파일(`config.local.php`), 올린 사진 폴더(`uploads`)
- 이 홈페이지와 관계없는 전자책 스토어 코드
