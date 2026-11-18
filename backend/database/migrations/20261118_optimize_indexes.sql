-- =====================================================
-- Migration: 20261118_optimize_indexes.sql
-- Description: Composite index optimization for TiDB Cloud & MySQL latency reduction (HU-15)
-- Author: Rodrigo Vazquez
-- =====================================================

USE `matchupiq_db`;

-- 1. Index on props for Edge ranking and update ordering
ALTER TABLE `props` ADD INDEX `idx_props_edge_updated` (`calculated_edge` DESC, `updated_at` DESC);
ALTER TABLE `props` ADD INDEX `idx_props_game_player` (`game_id`, `player_id`);

-- 2. Index on bet_tracker for user-specific recent tracking
ALTER TABLE `bet_tracker` ADD INDEX `idx_tracker_user_date` (`user_id`, `created_at` DESC);

-- 3. Composite indexes on tactical analytics tables
ALTER TABLE `player_heatmaps` ADD INDEX `idx_heatmaps_player_zone` (`player_id`, `field_zone`);
ALTER TABLE `coverage_splits` ADD INDEX `idx_coverage_player_type` (`player_id`, `coverage_type`);
