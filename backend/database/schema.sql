-- =====================================================
-- MatchupIQ: Relational Database Schema (3NF)
-- DBMS: MySQL 8.0+
-- Engine: InnoDB
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci
-- =====================================================

CREATE DATABASE IF NOT EXISTS `matchupiq_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `matchupiq_db`;

-- Drop tables in reverse dependency order to ensure clean execution
DROP TABLE IF EXISTS `odds_cache`;
DROP TABLE IF EXISTS `coverage_splits`;
DROP TABLE IF EXISTS `player_heatmaps`;
DROP TABLE IF EXISTS `bet_tracker`;
DROP TABLE IF EXISTS `props`;
DROP TABLE IF EXISTS `games`;
DROP TABLE IF EXISTS `players`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `sports`;
DROP TABLE IF EXISTS `bookmakers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

-- =====================================================
-- 1. Security & Authentication Catalogs
-- =====================================================

CREATE TABLE `roles` (
    `role_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`)
        REFERENCES `roles` (`role_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. Sports, Teams & Players Catalogs
-- =====================================================

CREATE TABLE `sports` (
    `sport_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teams` (
    `team_id` INT AUTO_INCREMENT PRIMARY KEY,
    `sport_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `abbreviation` VARCHAR(10) NOT NULL,
    `defensive_rank` INT NOT NULL,
    CONSTRAINT `fk_teams_sport` FOREIGN KEY (`sport_id`)
        REFERENCES `sports` (`sport_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `players` (
    `player_id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `position` VARCHAR(20) NOT NULL,
    `stat_average` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_players_team` FOREIGN KEY (`team_id`)
        REFERENCES `teams` (`team_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `games` (
    `game_id` INT AUTO_INCREMENT PRIMARY KEY,
    `sport_id` INT NOT NULL,
    `home_team_id` INT NOT NULL,
    `away_team_id` INT NOT NULL,
    `game_date` DATETIME NOT NULL,
    CONSTRAINT `fk_games_sport` FOREIGN KEY (`sport_id`)
        REFERENCES `sports` (`sport_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_games_home_team` FOREIGN KEY (`home_team_id`)
        REFERENCES `teams` (`team_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_games_away_team` FOREIGN KEY (`away_team_id`)
        REFERENCES `teams` (`team_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. Market & Betting Lines
-- =====================================================

CREATE TABLE `bookmakers` (
    `bookmaker_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `affiliate_url` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `props` (
    `prop_id` INT AUTO_INCREMENT PRIMARY KEY,
    `player_id` INT NOT NULL,
    `bookmaker_id` INT NOT NULL,
    `game_id` INT NOT NULL,
    `bet_type` VARCHAR(50) NOT NULL,
    `line_over` DECIMAL(6,2) NOT NULL,
    `odds_over` DECIMAL(6,2) NOT NULL,
    `odds_under` DECIMAL(6,2) NOT NULL,
    `calculated_edge` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_props_player` FOREIGN KEY (`player_id`)
        REFERENCES `players` (`player_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_props_bookmaker` FOREIGN KEY (`bookmaker_id`)
        REFERENCES `bookmakers` (`bookmaker_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_props_game` FOREIGN KEY (`game_id`)
        REFERENCES `games` (`game_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bet_tracker` (
    `bet_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `prop_id` INT NOT NULL,
    `taken_odds` DECIMAL(6,2) NOT NULL,
    `stake_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'won', 'lost', 'void') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bet_tracker_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_bet_tracker_prop` FOREIGN KEY (`prop_id`)
        REFERENCES `props` (`prop_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. NFL Tactical Analytics (Heatmaps & Coverage Splits)
-- =====================================================

CREATE TABLE `player_heatmaps` (
    `heatmap_id` INT AUTO_INCREMENT PRIMARY KEY,
    `player_id` INT NOT NULL,
    `field_zone` ENUM('left_end', 'left_tackle', 'middle', 'right_tackle', 'right_end') NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `yards_gained` INT NOT NULL DEFAULT 0,
    `success_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_heatmaps_player` FOREIGN KEY (`player_id`)
        REFERENCES `players` (`player_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coverage_splits` (
    `split_id` INT AUTO_INCREMENT PRIMARY KEY,
    `player_id` INT NOT NULL,
    `coverage_type` ENUM('man', 'zone') NOT NULL,
    `targets` INT NOT NULL DEFAULT 0,
    `receptions` INT NOT NULL DEFAULT 0,
    `catch_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_coverage_player` FOREIGN KEY (`player_id`)
        REFERENCES `players` (`player_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. External API Caching & Optimization
-- =====================================================

CREATE TABLE `odds_cache` (
    `cache_id` INT AUTO_INCREMENT PRIMARY KEY,
    `cache_key` VARCHAR(120) NOT NULL UNIQUE,
    `sport_key` VARCHAR(50) NOT NULL,
    `response_data` LONGTEXT NOT NULL,
    `fetched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    INDEX `idx_cache_key_expires` (`cache_key`, `expires_at`),
    INDEX `idx_sport_key` (`sport_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
