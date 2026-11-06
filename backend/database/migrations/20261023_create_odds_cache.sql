-- =====================================================
-- Migration: 20261023_create_odds_cache.sql
-- Description: Add odds_cache table for The Odds API caching proxy (15 min TTL)
-- Author: Rodrigo Vazquez
-- =====================================================

USE `matchupiq_db`;

CREATE TABLE IF NOT EXISTS `odds_cache` (
    `cache_id` INT AUTO_INCREMENT PRIMARY KEY,
    `cache_key` VARCHAR(120) NOT NULL UNIQUE,
    `sport_key` VARCHAR(50) NOT NULL,
    `response_data` LONGTEXT NOT NULL,
    `fetched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    INDEX `idx_cache_key_expires` (`cache_key`, `expires_at`),
    INDEX `idx_sport_key` (`sport_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
