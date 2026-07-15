# 파워머니플랜 어드민 (PHP 8.2)

그누보드 스타일 신청문의 관리자 — CMS 없이 **접수 DB만 쌓이는** 경량 어드민.
프론트 HTML 디자인은 전혀 건드리지 않습니다.

## 폴더 구조

```
powermoney_admin/
├─ config.php          ← 유일하게 수정할 설정 파일 (DB, 보안 상수)
├─ schema.mysql.sql    ← 닷홈 phpMyAdmin에서 실행할 스키마
├─ form-connect.js     ← 프론트 폼 연동 스크립트 (HTML엔 script 한 줄만 추가)
├─ api/apply.php       ← 공개 접수 API (속도제한·허니팟·검증)
├─ api/track.php       ← 접속자 로그 수집 API (IP당 하루 1회)
├─ admin/
│  ├─ setup.php        ← 최초 관리자 계정 생성 (1회 후 삭제)
│  ├─ login.php / logout.php
│  ├─ _layout.php      ← 공용 레이아웃 (상단바 + 접이식 사이드바)
│  ├─ index.php        ← 신청문의 보드 (필터·일괄처리·엑셀)
│  ├─ view.php         ← 상세 (UTM 전체 + 메모)
│  ├─ visit.php        ← 접속자 로그 (기간검색·경로/브라우저/OS/기기·기간삭제)
│  ├─ action.php       ← 상태변경/삭제 처리
│  └─ export.php       ← XLS/CSV 다운로드
├─ lib/                ← DB·보안·헬퍼 (웹 접근 차단됨)
├─ data/               ← 로컬 SQLite DB (웹 접근 차단됨)
└─ tools/              ← 더미데이터 생성 (seed.php, seed_visits.php / CLI 전용)
```

## 로컬 실행 (XAMPP PHP 8.2)

```powershell
cd C:\Users\server\Desktop\test
C:\xampp\php\php.exe powermoney_admin\tools\seed.php 30   # 더미 데이터 (선택)
C:\xampp\php\php.exe -S localhost:8080
```

1. `http://localhost:8080/powermoney_admin/admin/setup.php` — 설치키(config.php의 `SETUP_KEY`) 입력 후 관리자 계정 생성
2. `http://localhost:8080/powermoney_admin/admin/` — 로그인 후 보드 확인
3. 프론트 테스트: HTML `</body>` 직전에 `<script src="powermoney_admin/form-connect.js"></script>` 한 줄 추가
   → `http://localhost:8080/powermoney_onepage_v2.html?utm_source=naver&utm_medium=cpc&utm_term=사업자대출` 접속 후 폼 제출

## 닷홈 배포 절차

1. **DB 생성**: 닷홈 컨트롤패널 > MySQL 관리에서 DB 생성 → phpMyAdmin 접속 → `schema.mysql.sql` 내용 실행
2. **config.php 수정**:
   - `DB_DRIVER` → `'mysql'`
   - `DB_NAME / DB_USER / DB_PASS` → 닷홈 발급 정보
   - `SETUP_KEY` → 임의의 긴 문자열로 **반드시 변경**
   - `SITE_URL` → `'/'` (어드민 상단 "홈페이지" 버튼 주소)
3. **업로드**: `powermoney_admin/` 폴더 전체를 FTP로 `html/` 아래에 업로드 (HTML 파일과 같은 층위)
4. **관리자 생성**: `https://도메인/powermoney_admin/admin/setup.php` 접속 → 계정 생성 → **setup.php 파일 삭제**
5. **프론트 연동**: HTML `</body>` 직전에 script 한 줄 추가 (디자인 변경 없음)
6. (선택) `tools/` 폴더는 서버에 업로드하지 않아도 됩니다

## 보안 설계

| 항목 | 구현 |
|---|---|
| SQL 인젝션 | 전 쿼리 PDO prepared statement (`EMULATE_PREPARES=false`), 컬럼명은 화이트리스트 |
| IP 도배 차단 | **5분 내 5회 초과 → 30분 자동 차단** (접수 API·로그인 공통, `config.php`에서 조정) |
| 무차별 로그인 | 실패 5회/5분 → 30분 차단, `password_hash` 저장, 세션 고정 방지(`session_regenerate_id`) |
| 봇 스팸 | 허니팟 필드 + 동일 출처(Origin) 검사 + 연락처 10분 중복 접수 방지 |
| XSS | 모든 출력 `htmlspecialchars`, `X-Content-Type-Options`, `X-Frame-Options` |
| CSRF | 어드민 모든 POST에 토큰 검증 |
| 파일 보호 | `lib/`·`data/`·`tools/` `.htaccess` 접근 차단, 어드민 페이지 `noindex` |
| 세션 | httponly + SameSite=Lax 쿠키, 60분 유휴 자동 로그아웃 |

## UTM 경로 추적

- `form-connect.js`가 첫 방문 시 `utm_source/medium/campaign/term/content` + 리퍼러 + 랜딩 URL을 30일간 보관(최초 유입 기준) → 접수 시 함께 전송
- 서버가 utm_source·리퍼러로 **접속경로**(네이버/다음/카카오/구글/유튜브/인스타그램/페이스북 등) 자동 판별
- 검색어는 `utm_term` 우선, 없으면 네이버(`query`)·구글/다음(`q`) 리퍼러에서 추출 → 보드에 `n_keyword : 사업자대출` 형식으로 표시

## 접속자 로그 (그누보드 접속자집계 대응)

- `form-connect.js`가 페이지 진입 시 `api/track.php`로 핑 → **같은 IP는 하루 1회만** 기록 (그누보드 방식)
- 기록 항목: IP · 접속 경로(`n_keyword : 검색어` 또는 리퍼러 URL) · 채널 · 브라우저 · OS · 접속기기(PC/모바일/태블릿) · 일시
- 어드민 > 접속자 로그: 기간별 검색(기본 오늘) + 빠른선택, 조회 기간 로그 일괄 삭제
- 좌측 사이드바(신청문의/접속자 로그)는 상단 ☰ 버튼으로 접었다 펼 수 있고 상태가 브라우저에 저장됨

## 접수상태 (7종)

`접수중`(기본) · `접수완료` · `부재` · `상담` · `진행` · `오류` · `폐기`
— 보드에서 인라인 변경, 체크박스로 일괄 `접수완료`/`접수중`/`삭제` 처리 가능.
