-- BPC Complaint Management System: sign-in / sign-up database
-- Import this file in phpMyAdmin (Import tab). It creates the database and tables.

CREATE DATABASE IF NOT EXISTS `bpc_cms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bpc_cms`;

CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80)  NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('user','staff','admin') NOT NULL DEFAULT 'user',
  `phone`      VARCHAR(20)  NOT NULL DEFAULT '',
  `active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `attempt_key`  CHAR(64) NOT NULL,
  `fail_count`   INT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`attempt_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NULL,
  `user_name`  VARCHAR(150) NOT NULL DEFAULT 'Guest',
  `action`     VARCHAR(255) NOT NULL,
  `related_id` INT UNSIGNED NULL,
  `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   VARCHAR(60) NOT NULL,
  `setting_value` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('school_name', 'Bulacan Polytechnic College'),
  ('org_name',    'Complaint Management System');

-- Demo accounts (admin / staff / user) are created automatically by the app on the
-- first page load while SEED_DEMO_DATA is true in config/app.php.
