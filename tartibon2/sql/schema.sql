-- TartibOn — baza sxemasi (MySQL 5.7+ / MariaDB 10.3+)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  tg_id       BIGINT       NOT NULL PRIMARY KEY,
  first_name  VARCHAR(128) NOT NULL DEFAULT '',
  username    VARCHAR(64)  NOT NULL DEFAULT '',
  balance     BIGINT       NOT NULL DEFAULT 0,   -- so'mda, butun son
  hold        BIGINT       NOT NULL DEFAULT 0,   -- muzlatilgan mablag'
  created_at  DATETIME     NOT NULL,
  seen_at     DATETIME     NOT NULL,
  KEY idx_seen (seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chats (
  id         BIGINT       NOT NULL PRIMARY KEY,  -- Telegram chat id
  owner_id   BIGINT       NOT NULL,
  type       ENUM('channel','group') NOT NULL,
  title      VARCHAR(255) NOT NULL DEFAULT '',
  members    INT          NOT NULL DEFAULT 0,
  active     TINYINT(1)   NOT NULL DEFAULT 1,
  added_at   DATETIME     NOT NULL,
  KEY idx_owner (owner_id),
  KEY idx_active (active, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS topups (
  id         BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tg_id      BIGINT       NOT NULL,
  amount     BIGINT       NOT NULL,
  status     ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending',
  note       VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME     NOT NULL,
  closed_at  DATETIME     NULL,
  KEY idx_user (tg_id, created_at),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS withdrawals (
  id         BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tg_id      BIGINT       NOT NULL,
  amount     BIGINT       NOT NULL,
  card       VARCHAR(32)  NOT NULL DEFAULT '',
  status     ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME     NOT NULL,
  closed_at  DATETIME     NULL,
  KEY idx_user (tg_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erkin sozlamalar: hidden_services = ["growth","esim"] ko'rinishidagi JSON
CREATE TABLE IF NOT EXISTS settings (
  name  VARCHAR(64) NOT NULL PRIMARY KEY,
  value TEXT        NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (name, value) VALUES ('hidden_services', '[]');

-- Foydalanuvchining qaysi xizmatlari yoniq va o'ng tomonda nima ko'rsatiladi
CREATE TABLE IF NOT EXISTS user_services (
  tg_id      BIGINT      NOT NULL,
  service_id VARCHAR(32) NOT NULL,
  enabled    TINYINT(1)  NOT NULL DEFAULT 0,
  value      VARCHAR(64) NOT NULL DEFAULT '',
  updated_at DATETIME    NOT NULL,
  PRIMARY KEY (tg_id, service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rejalashtirilgan avtomatik amallar ("Keyingisi 12:28 · Peshin" uchun)
CREATE TABLE IF NOT EXISTS schedule (
  id       BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tg_id    BIGINT       NOT NULL,
  chat_id  BIGINT       NULL,
  title    VARCHAR(128) NOT NULL DEFAULT '',
  run_at   DATETIME     NOT NULL,
  done     TINYINT(1)   NOT NULL DEFAULT 0,
  KEY idx_next (tg_id, run_at, done)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
