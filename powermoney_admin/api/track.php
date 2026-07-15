<?php
declare(strict_types=1);

/* =====================================================
 *  접속자 로그 수집 API — form-connect.js 가 페이지 진입 시 호출
 *  - 같은 IP는 하루 1회만 기록 (그누보드 접속자집계 방식)
 *  - 응답은 항상 204 (본문 없음, 실패해도 방문자 화면에 영향 없음)
 * ===================================================== */

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';

send_security_headers();

function bye(): never
{
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    bye();
}

$ip = client_ip();

if (ip_is_blocked($ip) || !rate_hit($ip, 'track', TRACK_RATE_LIMIT)) {
    bye();
}

/* 동일 출처 검사 */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
    $selfHost   = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    if ($originHost !== $selfHost) {
        bye();
    }
}

/* 같은 IP 하루 1회 */
$pdo = db();
$st = $pdo->prepare('SELECT COUNT(*) FROM pm_visit WHERE ip = ? AND created_at >= ?');
$st->execute([$ip, date('Y-m-d') . ' 00:00:00']);
if ((int)$st->fetchColumn() > 0) {
    bye();
}

$utmSource  = clean_param($_POST['utm_source'] ?? '', 100);
$utmTerm    = clean_param($_POST['utm_term'] ?? '', 100);
$referrer   = clean_url($_POST['referrer'] ?? '');
$landingUrl = clean_url($_POST['landing_url'] ?? '');
$userAgent  = clean_param($_SERVER['HTTP_USER_AGENT'] ?? '', 300);

[$browser, $os, $device] = parse_ua($userAgent);

$st = $pdo->prepare('
    INSERT INTO pm_visit
        (created_at, ip, channel, keyword, utm_source, referrer, landing_url, browser, os, device, user_agent)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$st->execute([
    date('Y-m-d H:i:s'), $ip,
    detect_channel($utmSource, $referrer),
    extract_keyword($utmTerm, $referrer),
    $utmSource, $referrer, $landingUrl, $browser, $os, $device, $userAgent,
]);
bye();
