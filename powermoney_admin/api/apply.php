<?php
declare(strict_types=1);

/* =====================================================
 *  공개 접수 API — 프론트 상담신청 폼이 POST 하는 곳
 *  보안: prepared statement / IP 속도제한(5회·5분→30분 차단)
 *        / 허니팟 / 동일 출처 검사 / 화이트리스트 검증
 * ===================================================== */

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';

send_security_headers();
header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['ok' => false, 'msg' => '허용되지 않은 요청입니다.']);
}

$ip = client_ip();

/* 1. 차단된 IP */
if (ip_is_blocked($ip)) {
    respond(429, ['ok' => false, 'msg' => '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.']);
}

/* 2. 속도 제한 — 5분 내 5회 초과 시 30분 차단 */
if (!rate_hit($ip, 'apply')) {
    respond(429, ['ok' => false, 'msg' => '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.']);
}

/* 3. 동일 출처 검사 (Origin 헤더가 있으면 반드시 우리 도메인) */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
    $selfHost   = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    if ($originHost !== $selfHost) {
        respond(403, ['ok' => false, 'msg' => '허용되지 않은 출처입니다.']);
    }
}

/* 4. 허니팟 — 사람은 채우지 않는 숨은 필드 */
if (trim($_POST['website'] ?? '') !== '') {
    respond(200, ['ok' => true]); // 봇에게는 성공한 척
}

/* 5. 입력 검증 (화이트리스트) */
$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$product = trim($_POST['product'] ?? '');
$amount  = trim($_POST['amount'] ?? '');

// 성함: 2~6자, 한글/영문만 (스크립트·특수문자 원천 차단)
if (!preg_match('/^[가-힣a-zA-Z]{2,6}$/u', $name)) {
    respond(422, ['ok' => false, 'msg' => '성함을 정상적으로 입력해 주세요. (한글/영문 2~6자)']);
}
// 연락처: 하이픈 유무 모두 허용, 단 입력 원문이 15자를 넘으면 거부
if (mb_strlen($phone) > 15) {
    respond(422, ['ok' => false, 'msg' => '연락처를 정상적으로 입력해 주세요.']);
}
$phoneDigits = preg_replace('/[-\s]/', '', $phone);
if (!preg_match('/^01[016789]\d{7,8}$/', $phoneDigits)) {
    respond(422, ['ok' => false, 'msg' => '연락처를 정상적으로 입력해 주세요.']);
}
$phone = preg_replace('/^(\d{3})(\d{3,4})(\d{4})$/', '$1-$2-$3', $phoneDigits);

if (!in_array($product, PRODUCT_LIST, true)) {
    respond(422, ['ok' => false, 'msg' => '상품을 선택해 주세요.']);
}
if (!in_array($amount, AMOUNT_LIST, true)) {
    respond(422, ['ok' => false, 'msg' => '금액을 선택해 주세요.']);
}

/* 6. UTM / 유입경로 수집 — 전부 위생처리 (스크립트 삽입 차단) */
$utmSource   = clean_param($_POST['utm_source'] ?? '', 100);
$utmMedium   = clean_param($_POST['utm_medium'] ?? '', 100);
$utmCampaign = clean_param($_POST['utm_campaign'] ?? '', 100);
$utmTerm     = clean_param($_POST['utm_term'] ?? '', 100);
$utmContent  = clean_param($_POST['utm_content'] ?? '', 100);
$referrer    = clean_url($_POST['referrer'] ?? '');
$landingUrl  = clean_url($_POST['landing_url'] ?? '');
$userAgent   = clean_param($_SERVER['HTTP_USER_AGENT'] ?? '', 300);

$channel = detect_channel($utmSource, $referrer);
$keyword = extract_keyword($utmTerm, $referrer);

/* 7. 중복 접수 방지 — 같은 연락처가 10분 내 재접수하면 기존 건 유지 */
$pdo = db();
$st  = $pdo->prepare('SELECT COUNT(*) FROM pm_apply WHERE phone = ? AND created_at > ?');
$st->execute([$phone, date('Y-m-d H:i:s', time() - 600)]);
if ((int)$st->fetchColumn() > 0) {
    respond(200, ['ok' => true, 'msg' => '이미 접수되었습니다. 담당자가 곧 연락드립니다.']);
}

/* 8. 저장 (고유키는 5자리 난수, 중복 시 재시도) */
$now = date('Y-m-d H:i:s');
for ($try = 0; $try < 5; $try++) {
    $uid = random_int(10000, 99999);
    try {
        $st = $pdo->prepare('
            INSERT INTO pm_apply
                (uid, created_at, updated_at, name, phone, product, amount, ip, status,
                 channel, keyword, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                 referrer, landing_url, user_agent, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute([
            $uid, $now, $now, $name, $phone, $product, $amount, $ip, STATUS_DEFAULT,
            $channel, $keyword, $utmSource, $utmMedium, $utmCampaign, $utmTerm, $utmContent,
            $referrer, $landingUrl, $userAgent, '',
        ]);
        respond(200, ['ok' => true, 'msg' => '정상적으로 접수되었습니다. 잠시 후 연락드리겠습니다.']);
    } catch (PDOException $e) {
        // uid UNIQUE 충돌만 재시도, 그 외는 실패 처리
        if (!str_contains($e->getMessage(), 'UNIQUE') && !str_contains($e->getMessage(), 'Duplicate')) {
            respond(500, ['ok' => false, 'msg' => '일시적인 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.']);
        }
    }
}
respond(500, ['ok' => false, 'msg' => '일시적인 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.']);
