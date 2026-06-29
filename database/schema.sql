
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `colesterol_game_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `colesterol_game_db`;
DROP TABLE IF EXISTS `app_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_role` varchar(40) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(80) NOT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `related_url` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_role` (`target_role`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_type` varchar(60) NOT NULL DEFAULT 'general',
  `recipient_email` varchar(190) NOT NULL,
  `recipient_name` varchar(190) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_logs_type` (`email_type`),
  KEY `idx_email_logs_recipient` (`recipient_email`),
  KEY `idx_email_logs_status` (`status`),
  KEY `idx_email_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `player_name` varchar(100) DEFAULT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` varchar(1) DEFAULT NULL,
  `correct_option` varchar(1) NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `response_time` int(11) DEFAULT 0,
  `difficulty_level` decimal(3,1) NOT NULL DEFAULT 1.0,
  `score_earned` int(11) DEFAULT 0,
  `game_mode` enum('solo','room') DEFAULT 'solo',
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_game_answers_user` (`user_id`),
  KEY `idx_game_answers_room` (`room_id`),
  KEY `idx_game_answers_question` (`question_id`),
  KEY `idx_game_answers_mode` (`game_mode`),
  KEY `idx_game_answers_answered_at` (`answered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `player_name` varchar(100) DEFAULT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `correct_answers` int(11) NOT NULL DEFAULT 0,
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `lives_remaining` int(11) NOT NULL DEFAULT 0,
  `final_difficulty` decimal(3,1) NOT NULL DEFAULT 1.0,
  `room_id` int(11) DEFAULT NULL,
  `game_mode` varchar(20) DEFAULT 'solo',
  `played_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_game_results_user` (`user_id`),
  KEY `idx_game_results_room` (`room_id`),
  KEY `idx_game_results_mode` (`game_mode`),
  KEY `idx_game_results_played_at` (`played_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `difficulty` varchar(20) DEFAULT 'easy',
  `language` varchar(10) DEFAULT 'es',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'waiting',
  `question_count` int(11) DEFAULT 10,
  `time_limit` int(11) DEFAULT 20,
  `question_mode` varchar(20) DEFAULT 'random',
  `started_at` datetime DEFAULT NULL,
  `current_question_index` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `initial_difficulty` tinyint(4) NOT NULL DEFAULT 1,
  `question_started_at` datetime DEFAULT NULL,
  `paused_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_code` (`room_code`),
  KEY `idx_game_rooms_status` (`status`),
  KEY `idx_game_rooms_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` char(1) NOT NULL,
  `explanation` text NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `difficulty` varchar(20) DEFAULT 'easy',
  `language` varchar(10) DEFAULT 'es',
  `difficulty_level` tinyint(4) NOT NULL DEFAULT 1,
  `status` varchar(20) DEFAULT 'pending',
  `origin` varchar(20) DEFAULT 'manual',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by_user_id` int(11) DEFAULT NULL,
  `visibility` varchar(20) NOT NULL DEFAULT 'global',
  `global_request_status` varchar(20) NOT NULL DEFAULT 'approved',
  `global_requested_at` datetime DEFAULT NULL,
  `global_reviewed_by` int(11) DEFAULT NULL,
  `global_reviewed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_questions_language` (`language`),
  KEY `idx_questions_category` (`category`),
  KEY `idx_questions_status` (`status`),
  KEY `idx_questions_active` (`is_active`),
  KEY `idx_questions_difficulty_level` (`difficulty_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `player_name` varchar(100) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_player` (`room_id`,`player_name`),
  KEY `idx_room_players_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_question_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_question_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `difficulty_level` tinyint(4) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_category_difficulty` (`room_id`,`category`,`difficulty_level`),
  KEY `idx_room_requirements_room` (`room_id`),
  KEY `idx_room_requirements_category` (`category`),
  KEY `idx_room_requirements_difficulty` (`difficulty_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_question` (`room_id`,`question_id`),
  KEY `idx_room_questions_room` (`room_id`),
  KEY `idx_room_questions_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate_key` varchar(128) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rate_limits_key_time` (`rate_key`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_key` varchar(80) NOT NULL,
  `badge_name` varchar(120) NOT NULL,
  `badge_description` text NOT NULL,
  `badge_icon` varchar(30) DEFAULT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_badge` (`user_id`,`badge_key`),
  KEY `idx_user_badges_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'player',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `session_token` varchar(64) DEFAULT NULL,
  `session_updated_at` datetime DEFAULT NULL,
  `avatar_key` varchar(40) DEFAULT NULL,
  `custom_avatar_path` varchar(255) DEFAULT NULL,
  `country` varchar(8) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `institution` varchar(140) DEFAULT NULL,
  `occupation` varchar(120) DEFAULT NULL,
  `age` tinyint(3) unsigned DEFAULT NULL,
  `career` varchar(140) DEFAULT NULL,
  `education_level` varchar(80) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `current_correct_streak` int(11) NOT NULL DEFAULT 0,
  `best_correct_streak` int(11) NOT NULL DEFAULT 0,
  `current_daily_streak` int(11) NOT NULL DEFAULT 0,
  `best_daily_streak` int(11) NOT NULL DEFAULT 0,
  `last_played_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
