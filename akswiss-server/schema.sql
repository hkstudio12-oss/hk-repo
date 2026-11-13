-- AK SWISS backend schema (MySQL / MariaDB)
-- Import this once via hPanel -> phpMyAdmin -> Import.
-- Safe to re-run: it only creates tables if they do not exist.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(120) DEFAULT NULL,
  avatar        VARCHAR(255) DEFAULT NULL,
  provider      VARCHAR(20)  NOT NULL DEFAULT 'email',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS verification_codes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email      VARCHAR(190) NOT NULL,
  code       VARCHAR(10)  NOT NULL,
  purpose    VARCHAR(20)  NOT NULL DEFAULT 'register', -- register | reset
  expires_at DATETIME NOT NULL,
  used       TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_vc_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS devices (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  device_uid VARCHAR(120) DEFAULT NULL, -- BLE id / serial
  name       VARCHAR(120) NOT NULL DEFAULT 'Sports Utility Refrigerator',
  last_temp  INT DEFAULT NULL,
  last_seen  DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_dev_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS warranties (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  serial        VARCHAR(120) NOT NULL,
  model         VARCHAR(120) DEFAULT 'Sports Utility Refrigerator',
  purchase_date DATE DEFAULT NULL,
  seller        VARCHAR(190) DEFAULT NULL,
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at    DATE DEFAULT NULL,
  status        VARCHAR(20) NOT NULL DEFAULT 'active', -- active | expired
  PRIMARY KEY (id),
  UNIQUE KEY uq_warranty_serial (serial),
  KEY idx_war_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS warranty_claims (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  warranty_id INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  issue       TEXT,
  status      VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending | approved | rejected | completed
  admin_note  TEXT,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_claim_war (warranty_id),
  KEY idx_claim_user (user_id),
  KEY idx_claim_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS device_alarms (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  device_id  INT UNSIGNED DEFAULT NULL,
  code       VARCHAR(20) DEFAULT NULL,
  title      VARCHAR(190) DEFAULT NULL,
  message    TEXT,
  severity   VARCHAR(10) NOT NULL DEFAULT 'info', -- info | danger | good
  read_at    DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_alarm_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(190) NOT NULL,
  price      DECIMAL(10,2) DEFAULT NULL,
  image      VARCHAR(255) DEFAULT NULL,
  category   VARCHAR(60) DEFAULT NULL,
  description TEXT,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS app_config (
  cfg_key   VARCHAR(60) NOT NULL,
  cfg_value TEXT,
  PRIMARY KEY (cfg_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO app_config (cfg_key, cfg_value) VALUES
  ('warranty_months', '24'),
  ('support_phone', ''),
  ('about_html', '');
