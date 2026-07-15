<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * PDO 커넥션 (싱글턴).
 * 모든 쿼리는 반드시 prepared statement 로만 실행합니다.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // 진짜 prepared statement 사용
    ];

    if (DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $options);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA busy_timeout=5000');
        install_sqlite_schema($pdo);
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}

/** 로컬(SQLite) 전용 — 최초 접속 시 스키마 자동 생성 */
function install_sqlite_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pm_apply (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            uid         INTEGER NOT NULL UNIQUE,
            created_at  TEXT    NOT NULL,
            updated_at  TEXT    NOT NULL,
            name        TEXT    NOT NULL,
            phone       TEXT    NOT NULL,
            product     TEXT    NOT NULL,
            amount      TEXT    NOT NULL,
            ip          TEXT    NOT NULL,
            status      TEXT    NOT NULL DEFAULT '접수중',
            channel     TEXT    NOT NULL DEFAULT '직접유입',
            keyword     TEXT    NOT NULL DEFAULT '',
            utm_source  TEXT    NOT NULL DEFAULT '',
            utm_medium  TEXT    NOT NULL DEFAULT '',
            utm_campaign TEXT   NOT NULL DEFAULT '',
            utm_term    TEXT    NOT NULL DEFAULT '',
            utm_content TEXT    NOT NULL DEFAULT '',
            referrer    TEXT    NOT NULL DEFAULT '',
            landing_url TEXT    NOT NULL DEFAULT '',
            user_agent  TEXT    NOT NULL DEFAULT '',
            memo        TEXT    NOT NULL DEFAULT ''
        )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_apply_created ON pm_apply(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_apply_status  ON pm_apply(status)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pm_visit (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at  TEXT NOT NULL,
            ip          TEXT NOT NULL,
            channel     TEXT NOT NULL DEFAULT '직접유입',
            keyword     TEXT NOT NULL DEFAULT '',
            utm_source  TEXT NOT NULL DEFAULT '',
            referrer    TEXT NOT NULL DEFAULT '',
            landing_url TEXT NOT NULL DEFAULT '',
            browser     TEXT NOT NULL DEFAULT '',
            os          TEXT NOT NULL DEFAULT '',
            device      TEXT NOT NULL DEFAULT '',
            user_agent  TEXT NOT NULL DEFAULT ''
        )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visit_created ON pm_visit(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visit_ip ON pm_visit(ip, created_at)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pm_admin (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT NOT NULL UNIQUE,
            pass_hash  TEXT NOT NULL,
            created_at TEXT NOT NULL,
            last_login TEXT
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pm_ratelimit (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            ip         TEXT NOT NULL,
            action     TEXT NOT NULL,
            created_at INTEGER NOT NULL
        )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rate_ip ON pm_ratelimit(ip, action, created_at)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pm_block (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            ip            TEXT NOT NULL,
            reason        TEXT NOT NULL DEFAULT '',
            blocked_until INTEGER NOT NULL,
            created_at    TEXT NOT NULL
        )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_block_ip ON pm_block(ip, blocked_until)");
}
