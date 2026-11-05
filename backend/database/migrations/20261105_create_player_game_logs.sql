-- =====================================================
-- Migration: 20261105_create_player_game_logs.sql
-- Description: Add player_game_logs table for recent 5-game trends and streak hits (Section 2.12 DATA_DICTIONARY.md)
-- Author: Rodrigo Vazquez
-- =====================================================

USE `matchupiq_db`;

CREATE TABLE IF NOT EXISTS `player_game_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `player_id` INT NOT NULL,
    `opponent_abbr` VARCHAR(10) NOT NULL,
    `stat_type` VARCHAR(50) NOT NULL,
    `stat_value` DECIMAL(6,2) NOT NULL,
    `game_date` DATE NOT NULL,
    `is_home` TINYINT(1) NOT NULL DEFAULT 1,
    INDEX `idx_player_stat_date` (`player_id`, `stat_type`, `game_date`),
    CONSTRAINT `fk_game_logs_player` FOREIGN KEY (`player_id`)
        REFERENCES `players` (`player_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
