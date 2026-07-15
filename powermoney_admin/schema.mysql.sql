-- =====================================================
--  파워머니플랜 어드민 — 닷홈(MySQL/MariaDB) 스키마
--  닷홈 phpMyAdmin > SQL 탭에서 이 파일 내용을 실행하세요.
-- =====================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pm_apply (
    id           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    uid          INT UNSIGNED     NOT NULL,
    created_at   DATETIME         NOT NULL,
    updated_at   DATETIME         NOT NULL,
    name         VARCHAR(40)      NOT NULL,
    phone        VARCHAR(20)      NOT NULL,
    product      VARCHAR(40)      NOT NULL,
    amount       VARCHAR(40)      NOT NULL,
    ip           VARCHAR(45)      NOT NULL,
    status       VARCHAR(20)      NOT NULL DEFAULT '접수중',
    channel      VARCHAR(60)      NOT NULL DEFAULT '직접유입',
    keyword      VARCHAR(200)     NOT NULL DEFAULT '',
    utm_source   VARCHAR(200)     NOT NULL DEFAULT '',
    utm_medium   VARCHAR(200)     NOT NULL DEFAULT '',
    utm_campaign VARCHAR(200)     NOT NULL DEFAULT '',
    utm_term     VARCHAR(200)     NOT NULL DEFAULT '',
    utm_content  VARCHAR(200)     NOT NULL DEFAULT '',
    referrer     VARCHAR(500)     NOT NULL DEFAULT '',
    landing_url  VARCHAR(500)     NOT NULL DEFAULT '',
    user_agent   VARCHAR(300)     NOT NULL DEFAULT '',
    memo         TEXT             NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_apply_uid (uid),
    KEY idx_apply_created (created_at),
    KEY idx_apply_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pm_visit (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at  DATETIME     NOT NULL,
    ip          VARCHAR(45)  NOT NULL,
    channel     VARCHAR(60)  NOT NULL DEFAULT '직접유입',
    keyword     VARCHAR(200) NOT NULL DEFAULT '',
    utm_source  VARCHAR(200) NOT NULL DEFAULT '',
    referrer    VARCHAR(500) NOT NULL DEFAULT '',
    landing_url VARCHAR(500) NOT NULL DEFAULT '',
    browser     VARCHAR(40)  NOT NULL DEFAULT '',
    os          VARCHAR(40)  NOT NULL DEFAULT '',
    device      VARCHAR(20)  NOT NULL DEFAULT '',
    user_agent  VARCHAR(300) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_visit_created (created_at),
    KEY idx_visit_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pm_admin (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username   VARCHAR(30)  NOT NULL,
    pass_hash  VARCHAR(255) NOT NULL,
    created_at DATETIME     NOT NULL,
    last_login DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pm_ratelimit (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip         VARCHAR(45)  NOT NULL,
    action     VARCHAR(20)  NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_rate_ip (ip, action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pm_block (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip            VARCHAR(45)  NOT NULL,
    reason        VARCHAR(200) NOT NULL DEFAULT '',
    blocked_until INT UNSIGNED NOT NULL,
    created_at    DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_block_ip (ip, blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
