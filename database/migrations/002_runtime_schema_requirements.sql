-- Requisitos de esquema que antes se intentaban reparar en runtime.
-- En bases existentes, aplicar antes de `001_add_foreign_keys.sql`.

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate_key` varchar(128) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rate_limits_key_time` (`rate_key`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_type` varchar(60) NOT NULL DEFAULT 'general',
  `recipient_email` varchar(190) NOT NULL,
  `recipient_name` varchar(190) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_logs_type` (`email_type`),
  KEY `idx_email_logs_recipient` (`recipient_email`),
  KEY `idx_email_logs_status` (`status`),
  KEY `idx_email_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_user_id` (`user_id`),
  KEY `idx_password_resets_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_question_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `difficulty_level` tinyint(4) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_category_difficulty` (`room_id`,`category`,`difficulty_level`),
  KEY `idx_room_requirements_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_role` varchar(40) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(80) NOT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `related_url` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_role` (`target_role`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `session_token` varchar(64) DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `session_updated_at` datetime DEFAULT NULL AFTER `session_token`,
  ADD COLUMN IF NOT EXISTS `avatar_key` varchar(40) DEFAULT NULL AFTER `session_updated_at`,
  ADD COLUMN IF NOT EXISTS `custom_avatar_path` varchar(255) DEFAULT NULL AFTER `avatar_key`,
  ADD COLUMN IF NOT EXISTS `country` varchar(8) DEFAULT NULL AFTER `custom_avatar_path`,
  ADD COLUMN IF NOT EXISTS `city` varchar(80) DEFAULT NULL AFTER `country`,
  ADD COLUMN IF NOT EXISTS `institution` varchar(140) DEFAULT NULL AFTER `city`,
  ADD COLUMN IF NOT EXISTS `occupation` varchar(120) DEFAULT NULL AFTER `institution`,
  ADD COLUMN IF NOT EXISTS `age` tinyint(3) unsigned DEFAULT NULL AFTER `occupation`,
  ADD COLUMN IF NOT EXISTS `career` varchar(140) DEFAULT NULL AFTER `age`,
  ADD COLUMN IF NOT EXISTS `education_level` varchar(80) DEFAULT NULL AFTER `career`,
  ADD COLUMN IF NOT EXISTS `bio` varchar(500) DEFAULT NULL AFTER `education_level`,
  ADD COLUMN IF NOT EXISTS `current_correct_streak` int(11) NOT NULL DEFAULT 0 AFTER `bio`,
  ADD COLUMN IF NOT EXISTS `best_correct_streak` int(11) NOT NULL DEFAULT 0 AFTER `current_correct_streak`,
  ADD COLUMN IF NOT EXISTS `current_daily_streak` int(11) NOT NULL DEFAULT 0 AFTER `best_correct_streak`,
  ADD COLUMN IF NOT EXISTS `best_daily_streak` int(11) NOT NULL DEFAULT 0 AFTER `current_daily_streak`,
  ADD COLUMN IF NOT EXISTS `last_played_date` date DEFAULT NULL AFTER `best_daily_streak`;

ALTER TABLE `questions`
  ADD COLUMN IF NOT EXISTS `created_by_user_id` int(11) DEFAULT NULL AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `visibility` varchar(20) NOT NULL DEFAULT 'global' AFTER `created_by_user_id`,
  ADD COLUMN IF NOT EXISTS `global_request_status` varchar(20) NOT NULL DEFAULT 'approved' AFTER `visibility`,
  ADD COLUMN IF NOT EXISTS `global_requested_at` datetime DEFAULT NULL AFTER `global_request_status`,
  ADD COLUMN IF NOT EXISTS `global_reviewed_by` int(11) DEFAULT NULL AFTER `global_requested_at`,
  ADD COLUMN IF NOT EXISTS `global_reviewed_at` datetime DEFAULT NULL AFTER `global_reviewed_by`;

ALTER TABLE `game_answers`
  MODIFY `difficulty_level` decimal(3,1) NOT NULL DEFAULT 1.0;

ALTER TABLE `game_results`
  MODIFY `final_difficulty` decimal(3,1) NOT NULL DEFAULT 1.0;

UPDATE `questions`
SET `visibility` = 'global',
    `global_request_status` = CASE
      WHEN `status` = 'pending' THEN 'pending'
      WHEN `status` = 'rejected' THEN 'rejected'
      ELSE 'approved'
    END
WHERE `visibility` IS NULL OR `visibility` = '';
