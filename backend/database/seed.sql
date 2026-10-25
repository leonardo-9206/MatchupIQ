-- =====================================================
-- MatchupIQ: Initial Seed Data for Testing & Development
-- DBMS: MySQL 8.0+
-- =====================================================

USE `matchupiq_db`;

-- Disable foreign key checks for clean truncation and seeding
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `coverage_splits`;
DELETE FROM `player_heatmaps`;
DELETE FROM `bet_tracker`;
DELETE FROM `props`;
DELETE FROM `games`;
DELETE FROM `players`;
DELETE FROM `teams`;
DELETE FROM `sports`;
DELETE FROM `bookmakers`;
DELETE FROM `users`;
DELETE FROM `roles`;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. Roles & Initial Users
-- Default passwords: 'Password123!' (hashed with Bcrypt cost 12)
-- =====================================================

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'user');

INSERT INTO `users` (`user_id`, `role_id`, `full_name`, `email`, `password_hash`, `created_at`) VALUES
(1, 1, 'Admin User', 'admin@matchupiq.com', '$2y$12$eA8b7kU9K3Jz6bHjYqA7teYh5v5wW1C6Lw9F1.d/0fW4z6L2eFf.y', NOW()),
(2, 2, 'Rodrigo Vazquez', 'rodrigo@matchupiq.com', '$2y$12$eA8b7kU9K3Jz6bHjYqA7teYh5v5wW1C6Lw9F1.d/0fW4z6L2eFf.y', NOW());

-- =====================================================
-- 2. Sports, Teams & Players
-- =====================================================

INSERT INTO `sports` (`sport_id`, `name`, `slug`) VALUES
(1, 'NFL', 'nfl'),
(2, 'Liga MX', 'ligamx');

INSERT INTO `teams` (`team_id`, `sport_id`, `name`, `abbreviation`, `defensive_rank`) VALUES
(1, 1, 'Kansas City Chiefs', 'KC', 4),
(2, 1, 'San Francisco 49ers', 'SF', 7),
(3, 1, 'Baltimore Ravens', 'BAL', 3),
(4, 1, 'Buffalo Bills', 'BUF', 11),
(5, 2, 'Club América', 'AME', 2),
(6, 2, 'CD Guadalajara', 'CHIV', 8);

INSERT INTO `players` (`player_id`, `team_id`, `name`, `position`, `stat_average`) VALUES
(1, 1, 'Patrick Mahomes', 'QB', 282.40),
(2, 1, 'Travis Kelce', 'TE', 71.30),
(3, 1, 'Isiah Pacheco', 'RB', 66.80),
(4, 2, 'Christian McCaffrey', 'RB', 85.50),
(5, 5, 'Henry Martin', 'ST', 0.65);

INSERT INTO `games` (`game_id`, `sport_id`, `home_team_id`, `away_team_id`, `game_date`) VALUES
(1, 1, 1, 2, '2026-10-20 18:30:00'),
(2, 1, 3, 4, '2026-10-21 15:00:00'),
(3, 2, 5, 6, '2026-10-22 20:00:00');

-- =====================================================
-- 3. Bookmakers, Props & Bet Tracker
-- =====================================================

INSERT INTO `bookmakers` (`bookmaker_id`, `name`, `affiliate_url`) VALUES
(1, 'Bet365', 'https://www.bet365.com'),
(2, 'Caliente', 'https://www.caliente.mx'),
(3, 'Playdoit', 'https://www.playdoit.mx');

INSERT INTO `props` (`prop_id`, `player_id`, `bookmaker_id`, `game_id`, `bet_type`, `line_over`, `odds_over`, `odds_under`, `calculated_edge`, `updated_at`) VALUES
(1, 1, 1, 1, 'passing_yards', 265.50, 1.90, 1.90, 11.20, NOW()),
(2, 1, 2, 1, 'passing_yards', 268.50, 1.95, 1.85, 9.40, NOW()),
(3, 3, 1, 1, 'rushing_yards', 62.50, 1.88, 1.92, 14.50, NOW()),
(4, 4, 2, 1, 'rushing_yards', 78.50, 1.91, 1.91, 8.70, NOW());

INSERT INTO `bet_tracker` (`bet_id`, `user_id`, `prop_id`, `taken_odds`, `stake_amount`, `status`, `notes`, `created_at`) VALUES
(1, 2, 1, 1.90, 500.00, 'pending', 'Favorable weather conditions and weak opposing secondary', NOW());

-- =====================================================
-- 4. NFL Tactical Heatmaps & Coverage Splits
-- =====================================================

-- Rushing zone breakdown for Isiah Pacheco (player_id = 3) and Christian McCaffrey (player_id = 4)
INSERT INTO `player_heatmaps` (`heatmap_id`, `player_id`, `field_zone`, `attempts`, `yards_gained`, `success_rate`) VALUES
(1, 3, 'left_end', 24, 132, 58.33),
(2, 3, 'left_tackle', 42, 215, 64.29),
(3, 3, 'middle', 68, 310, 52.94),
(4, 3, 'right_tackle', 38, 198, 60.53),
(5, 3, 'right_end', 18, 95, 50.00),
(6, 4, 'left_end', 31, 204, 61.29),
(7, 4, 'left_tackle', 36, 171, 55.56),
(8, 4, 'middle', 52, 228, 50.00),
(9, 4, 'right_tackle', 47, 266, 63.83),
(10, 4, 'right_end', 40, 281, 67.50);

-- Coverage performance for Travis Kelce (player_id = 2)
INSERT INTO `coverage_splits` (`split_id`, `player_id`, `coverage_type`, `targets`, `receptions`, `catch_rate`) VALUES
(1, 2, 'man', 35, 26, 74.29),
(2, 2, 'zone', 58, 46, 79.31);
