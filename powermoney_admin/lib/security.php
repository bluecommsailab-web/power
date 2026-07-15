<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/* =====================================================
 *  보안 유틸: IP 차단 / 속도 제한 / CSRF / 세션
 * ===================================================== */

/** 클라이언트 IP (기본은 REMOTE_ADDR만 신뢰 — 스푸핑 방지) */
function client_ip(): string
{
    if (TRUST_PROXY && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** 현재 차단된 IP 인지 확인 */
function ip_is_blocked(string $ip): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM pm_block WHERE ip = ? AND blocked_until > ?');
    $st->execute([$ip, time()]);
    return (int)$st->fetchColumn() > 0;
}

/**
 * 속도 제한 기록 + 판정.
 * RATE_WINDOW초 안에 RATE_LIMIT회를 "초과"하면 BLOCK_SECONDS 동안 IP 차단.
 * @return bool true = 허용, false = 방금 한도를 넘어 차단됨
 */
function rate_hit(string $ip, string $action, int $limit = RATE_LIMIT): bool
{
    $pdo = db();
    $now = time();

    // 오래된 기록 정리 (테이블 비대 방지)
    $st = $pdo->prepare('DELETE FROM pm_ratelimit WHERE created_at < ?');
    $st->execute([$now - RATE_WINDOW * 2]);

    $st = $pdo->prepare('INSERT INTO pm_ratelimit (ip, action, created_at) VALUES (?, ?, ?)');
    $st->execute([$ip, $action, $now]);

    $st = $pdo->prepare('SELECT COUNT(*) FROM pm_ratelimit WHERE ip = ? AND action = ? AND created_at > ?');
    $st->execute([$ip, $action, $now - RATE_WINDOW]);
    $count = (int)$st->fetchColumn();

    if ($count > $limit) {
        $st = $pdo->prepare('INSERT INTO pm_block (ip, reason, blocked_until, created_at) VALUES (?, ?, ?, ?)');
        $st->execute([$ip, $action . ' 과다 요청 (' . $count . '회/' . RATE_WINDOW . '초)', $now + BLOCK_SECONDS, date('Y-m-d H:i:s')]);
        return false;
    }
    return true;
}

/** 공통 보안 응답 헤더 */
function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/* ---------- 관리자 세션 ---------- */

function admin_session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('PMADMSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();

    // 유휴 시간 초과 시 로그아웃
    if (isset($_SESSION['admin_id'], $_SESSION['last_seen'])
        && time() - (int)$_SESSION['last_seen'] > SESSION_IDLE_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
    }
    if (isset($_SESSION['admin_id'])) {
        $_SESSION['last_seen'] = time();
    }
}

function admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

/** 미로그인 시 로그인 페이지로 (admin/ 내부 페이지 상단에서 호출) */
function admin_require_login(): void
{
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function csrf_verify_or_die(): void
{
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('잘못된 요청입니다. (CSRF)');
    }
}
