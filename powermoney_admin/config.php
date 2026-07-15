<?php
declare(strict_types=1);

/* =====================================================
 *  파워머니플랜 어드민 설정
 *  - 로컬 테스트  : DB_DRIVER = 'sqlite'  (별도 DB 설치 불필요)
 *  - 닷홈 배포    : DB_DRIVER = 'mysql' 로 바꾸고 아래 MySQL 정보 입력
 * ===================================================== */

date_default_timezone_set('Asia/Seoul');

/* ---------- DB ---------- */
const DB_DRIVER = 'mysql';                     // 'sqlite' | 'mysql'
const DB_SQLITE_PATH = __DIR__ . '/data/powermoney.sqlite';

// MySQL 접속 정보 — 로컬은 XAMPP 기본값, 닷홈 배포 시 발급 정보로 변경
const DB_HOST = 'localhost';
const DB_NAME = 'powermoney';   // 닷홈: 닷홈아이디
const DB_USER = 'root';         // 닷홈: 닷홈아이디
const DB_PASS = '';             // 닷홈: DB비밀번호

/* ---------- 보안 ---------- */
// 접수 API 속도 제한: 같은 IP가 RATE_WINDOW초 안에 RATE_LIMIT회 초과 요청 시 BLOCK_SECONDS 동안 차단
const RATE_LIMIT    = 5;        // 허용 횟수
const RATE_WINDOW   = 300;      // 5분 (초)
const BLOCK_SECONDS = 1800;     // 차단 시간: 30분

// 로그인 무차별 대입 방지: 5분 내 5회 실패 시 30분 차단 (위 값 공유)
const LOGIN_FAIL_LIMIT = 5;

// 프록시(CDN) 뒤에 있을 때만 true (닷홈 일반 호스팅은 false 유지)
const TRUST_PROXY = false;

// 최초 관리자 계정 생성용 키 — admin/setup.php 접속 시 필요.
// 배포 전 반드시 임의의 긴 문자열로 변경하세요.
const SETUP_KEY = 'change-this-setup-key-2026';

// 관리자 세션 유휴 만료 (초)
const SESSION_IDLE_TIMEOUT = 3600;

/* ---------- 서비스 ---------- */
const SITE_NAME = '파워머니플랜';

// 어드민 상단 "홈페이지" 버튼이 여는 주소 — 닷홈 배포 시 '/' 로 변경
const SITE_URL = '/powermoney_onepage_v2.html';

// 접속자 로그 속도 제한 (페이지 새로고침은 정상 행동이므로 접수보다 느슨하게)
const TRACK_RATE_LIMIT = 60;

// 접수상태 목록 (그누보드 어드민과 동일 구성)
const STATUS_LIST = ['접수중', '접수완료', '부재', '상담', '진행', '오류', '폐기'];
const STATUS_DEFAULT = '접수중';

// 접수 폼 허용 값 (화이트리스트 검증)
const PRODUCT_LIST = ['개인사업자대출', '법인사업자대출', '기업단기자금대출'];
const AMOUNT_LIST  = ['1,000만원 미만', '1,000만원 이상 ~', '5,000만원 이상 ~', '1억원 이상 ~'];
