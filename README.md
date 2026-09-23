# 전자책 스토어

전자책 판매 사이트예요. 디자인 시안(스토어 홈, 전자책 상세·미리보기·리뷰, 관리자 등록)을 바탕으로 만들었어요.
PHP와 파일 하나짜리 DB(SQLite)로 동작해요. 결제는 무통장 입금이에요.

## 로컬에서 실행

```bash
cd ~/Documents/my-blog
php -d upload_max_filesize=200M -d post_max_size=210M -S localhost:8000 -t public public/index.php
```

- 스토어: http://localhost:8000
- 관리자: http://localhost:8000/admin → 처음 한 번은 관리자 계정을 만드는 화면이 나와요.
- 예시 전자책 8권 넣기(선택): `php app/cli.php demo`
- 관리자 비밀번호를 잊었을 때: `php app/cli.php reset-admin` → /admin 에서 새로 만들기

## 판매 흐름 (무통장 입금)

1. 관리자 › 설정에서 **입금 계좌**와 **사업자 정보**를 등록해요. 계좌가 없으면 주문을 받지 않아요.
2. 관리자 › 전자책 관리 › 새 전자책 등록: 표지, EPUB/PDF 파일, 미리보기 범위를 정하고 등록해요.
3. 구매자가 회원가입 후 주문하면 주문 번호와 입금 계좌가 안내돼요(입금 기한은 설정에서 정해요).
4. 통장에서 입금자명과 금액을 확인한 뒤 관리자 › 주문 내역에서 **입금 확인**을 눌러요.
5. 구매자는 바로 **내 서재**에서 파일을 내려받고, 그 책에 리뷰를 쓸 수 있어요.

## 미리보기

- **앞부분 자동 공개**
  - EPUB: 서버가 본문을 읽어 앞부분 N쪽 분량(1쪽 ≈ 600자)을 보여줘요.
  - PDF: 등록할 때 관리자 브라우저가 앞 N쪽을 이미지로 만들어 함께 올려요(pdf.js, 인터넷 연결 필요).
- **직접 입력**: 붙여넣은 본문을 그대로 보여줘요.
- 원본 파일은 `storage/books/`에만 있고 웹에서 직접 열 수 없어요. 입금이 확인된 구매자와 관리자만 내려받아요.

## 폴더

```
app/        PHP 코드(화면: app/views, 처리: app/pages)
public/     웹에 공개되는 폴더(index.php, assets, uploads)
storage/    DB, 전자책 원본, 세션 — 웹에 공개하면 안 돼요
```

## 카페24 배포

`main`에 올리면 GitHub Actions(`.github/workflows/deploy.yml`)가 FTP로 자동 배포해요.

- `public/` → 서버의 `www/` (웹에 공개)
- `app/` → 서버의 `app/` (www 바깥, 웹에서 열 수 없음)
- `storage/`(DB·전자책 원본·세션)는 서버에서 처음 실행될 때 `www` 바깥에 자동으로 만들어져요. 배포가 건드리지 않아요.
- 서버 상태 점검: `https://tip82.com/health` (모두 true 면 정상)
- 첫 배포 직후 곧바로 `/admin`에 들어가 관리자 계정을 만드세요(계정이 없는 동안은 누구나 만들 수 있어요).
- 큰 전자책을 올리려면 호스팅의 `upload_max_filesize` / `post_max_size`를 늘려야 해요.
- MySQL을 쓰려면 서버의 `app/`에 `config.local.php`를 FTP로 올려 DB 정보를 넣어요(예시는 `app/config.php` 위쪽 주석).
