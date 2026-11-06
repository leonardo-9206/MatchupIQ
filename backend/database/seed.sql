-- =====================================================
-- MatchupIQ: Initial Seed Data for Testing & Development
-- DBMS: MySQL 8.0+
-- =====================================================

USE `matchupiq_db`;

-- Disable foreign key checks for clean truncation and seeding
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `player_game_logs`;
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
(6, 2, 'CD Guadalajara', 'CHIV', 8),
(7, 1, 'Arizona Cardinals', 'ARI', 1),
(8, 1, 'Atlanta Falcons', 'ATL', 2),
(9, 1, 'Carolina Panthers', 'CAR', 5),
(10, 1, 'Chicago Bears', 'CHI', 6),
(11, 1, 'Cincinnati Bengals', 'CIN', 7),
(12, 1, 'Cleveland Browns', 'CLE', 8),
(13, 1, 'Dallas Cowboys', 'DAL', 9),
(14, 1, 'Denver Broncos', 'DEN', 10),
(15, 1, 'Detroit Lions', 'DET', 11),
(16, 1, 'Green Bay Packers', 'GB', 12),
(17, 1, 'Houston Texans', 'HOU', 13),
(18, 1, 'Indianapolis Colts', 'IND', 14),
(19, 1, 'Jacksonville Jaguars', 'JAX', 15),
(20, 1, 'Las Vegas Raiders', 'LV', 17),
(21, 1, 'Los Angeles Chargers', 'LAC', 18),
(22, 1, 'Los Angeles Rams', 'LAR', 19),
(23, 1, 'Miami Dolphins', 'MIA', 20),
(24, 1, 'Minnesota Vikings', 'MIN', 21),
(25, 1, 'New England Patriots', 'NE', 22),
(26, 1, 'New Orleans Saints', 'NO', 23),
(27, 1, 'New York Giants', 'NYG', 24),
(28, 1, 'New York Jets', 'NYJ', 25),
(29, 1, 'Philadelphia Eagles', 'PHI', 26),
(30, 1, 'Pittsburgh Steelers', 'PIT', 27),
(31, 1, 'Seattle Seahawks', 'SEA', 29),
(32, 1, 'Tampa Bay Buccaneers', 'TB', 30),
(33, 1, 'Tennessee Titans', 'TEN', 31),
(34, 1, 'Washington Commanders', 'WSH', 32);

INSERT INTO `players` (`player_id`, `team_id`, `name`, `position`, `stat_average`) VALUES
(1, 1, 'Patrick Mahomes', 'QB', 310.00),
(2, 1, 'Travis Kelce', 'TE', 47.60),
(3, 1, 'Isiah Pacheco', 'RB', 26.60),
(4, 2, 'Christian McCaffrey', 'RB', 59.60),
(5, 5, 'Henry Martin', 'ST', 0.65),
(6, 2, 'Brock Purdy', 'QB', 251.20),
(7, 2, 'George Kittle', 'TE', 66.60),
(8, 3, 'Lamar Jackson', 'QB', 193.40),
(9, 3, 'Derrick Henry', 'RB', 66.20),
(10, 4, 'Josh Allen', 'QB', 242.20),
(11, 4, 'James Cook', 'RB', 0.00),
(12, 4, 'James Cook III', 'RB', 64.20),
(13, 4, 'Ray Davis', 'RB', 64.20),
(14, 4, 'DJ Moore', 'WR', 58.80),
(15, 4, 'Khalil Shakir', 'WR', 58.80),
(16, 4, 'Keon Coleman', 'WR', 58.80),
(17, 4, 'Dalton Kincaid', 'TE', 45.30),
(18, 4, 'Dawson Knox', 'TE', 45.30),
(19, 23, 'Malik Willis', 'QB', 248.50),
(20, 8, 'Tua Tagovailoa', 'QB', 248.50),
(21, 23, 'De\'Von Achane', 'RB', 64.20),
(22, 23, 'Ollie Gordon II', 'RB', 64.20),
(23, 23, 'Jaylen Wright', 'RB', 64.20),
(24, 23, 'Malik Washington', 'WR', 58.80),
(25, 23, 'Chris Bell', 'WR', 58.80),
(26, 23, 'Greg Dulcich', 'TE', 45.30),
(27, 25, 'Julian Hill', 'TE', 45.30),
(28, 25, 'Drake Maye', 'QB', 248.50),
(29, 25, 'Rhamondre Stevenson', 'RB', 64.20),
(30, 25, 'TreVeyon Henderson', 'RB', 64.20),
(31, 25, 'A.J. Brown', 'WR', 58.80),
(32, 25, 'DeMario Douglas', 'WR', 58.80),
(33, 25, 'Hunter Henry', 'TE', 45.30),
(34, 8, 'Austin Hooper', 'TE', 45.30),
(35, 25, 'Eli Raridon', 'TE', 45.30),
(36, 28, 'Geno Smith', 'QB', 248.50),
(37, 28, 'Breece Hall', 'RB', 64.20),
(38, 28, 'Braelon Allen', 'RB', 64.20),
(39, 28, 'Garrett Wilson', 'WR', 58.80),
(40, 28, 'Adonai Mitchell', 'WR', 58.80),
(41, 28, 'Mason Taylor', 'TE', 45.30),
(42, 28, 'Kenyon Sadiq', 'TE', 45.30),
(43, 28, 'Jeremy Ruckert', 'TE', 45.30),
(44, 3, 'Justice Hill', 'RB', 64.20),
(45, 3, 'Zay Flowers', 'WR', 58.80),
(46, 3, 'Rashod Bateman', 'WR', 58.80),
(47, 3, 'Mark Andrews', 'TE', 45.30),
(48, 3, 'Josh Cuevas', 'TE', 45.30),
(49, 11, 'Joe Burrow', 'QB', 248.50),
(50, 11, 'Chase Brown', 'RB', 64.20),
(51, 11, 'Samaje Perine', 'RB', 64.20),
(52, 11, 'Ja\'Marr Chase', 'WR', 58.80),
(53, 11, 'Tee Higgins', 'WR', 58.80),
(54, 11, 'Mike Gesicki', 'TE', 45.30),
(55, 11, 'Drew Sample', 'TE', 45.30),
(56, 12, 'Shedeur Sanders', 'QB', 248.50),
(57, 12, 'Deshaun Watson', 'QB', 248.50),
(58, 12, 'Quinshon Judkins', 'RB', 64.20),
(59, 12, 'Dylan Sampson', 'RB', 64.20),
(60, 12, 'Denzel Boston', 'WR', 58.80),
(61, 12, 'Jerry Jeudy', 'WR', 58.80),
(62, 12, 'KC Concepcion', 'WR', 58.80),
(63, 12, 'Harold Fannin Jr.', 'TE', 45.30),
(64, 12, 'Blake Whiteheart', 'TE', 45.30),
(65, 21, 'David Njoku', 'TE', 45.30),
(66, 30, 'Aaron Rodgers', 'QB', 248.50),
(67, 30, 'Jaylen Warren', 'RB', 64.20),
(68, 30, 'Rico Dowdle', 'RB', 64.20),
(69, 30, 'DK Metcalf', 'WR', 58.80),
(70, 30, 'Germie Bernard', 'WR', 58.80),
(71, 30, 'Ben Skowronek', 'WR', 58.80),
(72, 30, 'Pat Freiermuth', 'TE', 45.30),
(73, 30, 'Darnell Washington', 'TE', 45.30),
(74, 17, 'C.J. Stroud', 'QB', 248.50),
(75, 17, 'David Montgomery', 'RB', 64.20),
(76, 17, 'Woody Marks', 'RB', 64.20),
(77, 17, 'Dare Ogunbowale', 'RB', 64.20),
(78, 17, 'Nico Collins', 'WR', 58.80),
(79, 17, 'Tank Dell', 'WR', 58.80),
(80, 17, 'Dalton Schultz', 'TE', 45.30),
(81, 17, 'Brevin Jordan', 'TE', 45.30),
(82, 18, 'Daniel Jones', 'QB', 248.50),
(83, 18, 'Anthony Richardson Sr.', 'QB', 248.50),
(84, 18, 'Jonathan Taylor', 'RB', 64.20),
(85, 18, 'DJ Giddens', 'RB', 64.20),
(86, 18, 'Seth McGowan', 'RB', 64.20),
(87, 18, 'Josh Downs', 'WR', 58.80),
(88, 18, 'Keenan Allen', 'WR', 58.80),
(89, 30, 'Michael Pittman Jr.', 'WR', 58.80),
(90, 18, 'Tyler Warren', 'TE', 45.30),
(91, 18, 'Mo Alie-Cox', 'TE', 45.30),
(92, 19, 'Trevor Lawrence', 'QB', 248.50),
(93, 26, 'Travis Etienne Jr.', 'RB', 64.20),
(94, 19, 'Bhayshul Tuten', 'RB', 64.20),
(95, 29, 'Tank Bigsby', 'RB', 64.20),
(96, 19, 'Brian Thomas Jr.', 'WR', 58.80),
(97, 19, 'Jakobi Meyers', 'WR', 58.80),
(98, 19, 'Parker Washington', 'WR', 58.80),
(99, 19, 'Brenton Strange', 'TE', 45.30),
(100, 2, 'Luke Farrell', 'TE', 45.30),
(101, 33, 'Cam Ward', 'QB', 248.50),
(102, 33, 'Tony Pollard', 'RB', 64.20),
(103, 33, 'Tyjae Spears', 'RB', 64.20),
(104, 33, 'Calvin Ridley', 'WR', 58.80),
(105, 33, 'Carnell Tate', 'WR', 58.80),
(106, 33, 'Wan\'Dale Robinson', 'WR', 58.80),
(107, 33, 'Gunnar Helm', 'TE', 45.30),
(108, 16, 'Josh Whyle', 'TE', 45.30),
(109, 14, 'Bo Nix', 'QB', 248.50),
(110, 14, 'J.K. Dobbins', 'RB', 64.20),
(111, 14, 'Jonah Coleman', 'RB', 64.20),
(112, 26, 'Audric Estime', 'RB', 64.20),
(113, 14, 'Jaylen Waddle', 'WR', 58.80),
(114, 14, 'Courtland Sutton', 'WR', 58.80),
(115, 14, 'Evan Engram', 'TE', 45.30),
(116, 14, 'Lucas Krull', 'TE', 45.30),
(117, 1, 'Kenneth Walker III', 'RB', 64.20),
(118, 1, 'Emmett Johnson', 'RB', 64.20),
(119, 1, 'Rashee Rice', 'WR', 58.80),
(120, 1, 'Xavier Worthy', 'WR', 58.80),
(121, 1, 'Noah Gray', 'TE', 45.30),
(122, 20, 'Kirk Cousins', 'QB', 248.50),
(123, 20, 'Fernando Mendoza', 'QB', 248.50),
(124, 20, 'Ashton Jeanty', 'RB', 64.20),
(125, 20, 'Mike Washington Jr.', 'RB', 64.20),
(126, 20, 'Tre Tucker', 'WR', 58.80),
(127, 20, 'Jack Bech', 'WR', 58.80),
(128, 20, 'Brock Bowers', 'TE', 45.30),
(129, 20, 'Michael Mayer', 'TE', 45.30),
(130, 21, 'Justin Herbert', 'QB', 248.50),
(131, 21, 'Omarion Hampton', 'RB', 64.20),
(132, 21, 'Keaton Mitchell', 'RB', 64.20),
(133, 21, 'Kimani Vidal', 'RB', 64.20),
(134, 21, 'Ladd McConkey', 'WR', 58.80),
(135, 21, 'Quentin Johnston', 'WR', 58.80),
(136, 21, 'Will Dissly', 'TE', 45.30),
(137, 21, 'Charlie Kolar', 'TE', 45.30),
(138, 13, 'Dak Prescott', 'QB', 248.50),
(139, 13, 'Javonte Williams', 'RB', 64.20),
(140, 13, 'Tyler Goodson', 'RB', 64.20),
(141, 13, 'CeeDee Lamb', 'WR', 58.80),
(142, 13, 'George Pickens', 'WR', 58.80),
(143, 13, 'Jake Ferguson', 'TE', 45.30),
(144, 13, 'Brevyn Spann-Ford', 'TE', 45.30),
(145, 13, 'Luke Schoonmaker', 'TE', 45.30),
(146, 27, 'J.J. McCarthy', 'QB', 248.50),
(147, 27, 'Jaxson Dart', 'QB', 248.50),
(148, 27, 'Cam Skattebo', 'RB', 64.20),
(149, 27, 'Devin Singletary', 'RB', 64.20),
(150, 27, 'Tyrone Tracy Jr.', 'RB', 64.20),
(151, 27, 'Malik Nabers', 'WR', 58.80),
(152, 27, 'Malachi Fields', 'WR', 58.80),
(153, 27, 'Isaiah Likely', 'TE', 45.30),
(154, 27, 'Theo Johnson', 'TE', 45.30),
(155, 29, 'Jalen Hurts', 'QB', 248.50),
(156, 29, 'Tanner McKee', 'QB', 248.50),
(157, 29, 'Saquon Barkley', 'RB', 64.20),
(158, 29, 'Jaydon Blue', 'RB', 64.20),
(159, 29, 'Will Shipley', 'RB', 64.20),
(160, 29, 'DeVonta Smith', 'WR', 58.80),
(161, 29, 'Dontayvion Wicks', 'WR', 58.80),
(162, 29, 'Dallas Goedert', 'TE', 45.30),
(163, 29, 'Grant Calcaterra', 'TE', 45.30),
(164, 29, 'Johnny Mundt', 'TE', 45.30),
(165, 34, 'Jayden Daniels', 'QB', 248.50),
(166, 34, 'Jacory Croskey-Merritt', 'RB', 64.20),
(167, 34, 'Rachaad White', 'RB', 64.20),
(168, 34, 'Terry McLaurin', 'WR', 58.80),
(169, 34, 'Stefon Diggs', 'WR', 58.80),
(170, 34, 'Luke McCaffrey', 'WR', 58.80),
(171, 34, 'Chig Okonkwo', 'TE', 45.30),
(172, 34, 'Ben Sinnott', 'TE', 45.30),
(173, 10, 'Caleb Williams', 'QB', 248.50),
(174, 10, 'D\'Andre Swift', 'RB', 64.20),
(175, 10, 'Kyle Monangai', 'RB', 64.20),
(176, 10, 'Rome Odunze', 'WR', 58.80),
(177, 10, 'Luther Burden III', 'WR', 58.80),
(178, 10, 'Colston Loveland', 'TE', 45.30),
(179, 10, 'Cole Kmet', 'TE', 45.30),
(180, 10, 'Sam Roush', 'TE', 45.30),
(181, 15, 'Jared Goff', 'QB', 248.50),
(182, 15, 'Jahmyr Gibbs', 'RB', 64.20),
(183, 15, 'Sione Vaki', 'RB', 64.20),
(184, 15, 'Amon-Ra St. Brown', 'WR', 58.80),
(185, 15, 'Jameson Williams', 'WR', 58.80),
(186, 15, 'Sam LaPorta', 'TE', 45.30),
(187, 15, 'Brock Wright', 'TE', 45.30),
(188, 16, 'Jordan Love', 'QB', 248.50),
(189, 16, 'Josh Jacobs', 'RB', 64.20),
(190, 16, 'MarShawn Lloyd', 'RB', 64.20),
(191, 16, 'Christian Watson', 'WR', 58.80),
(192, 16, 'Matthew Golden', 'WR', 58.80),
(193, 16, 'Jayden Reed', 'WR', 58.80),
(194, 16, 'Tucker Kraft', 'TE', 45.30),
(195, 16, 'Jonnu Smith', 'TE', 45.30),
(196, 16, 'Luke Musgrave', 'TE', 45.30),
(197, 24, 'Kyler Murray', 'QB', 248.50),
(198, 24, 'Aaron Jones Sr.', 'RB', 64.20),
(199, 24, 'Jordan Mason', 'RB', 64.20),
(200, 24, 'Justin Jefferson', 'WR', 58.80),
(201, 24, 'Jordan Addison', 'WR', 58.80),
(202, 24, 'T.J. Hockenson', 'TE', 45.30),
(203, 24, 'Josh Oliver', 'TE', 45.30),
(204, 8, 'Michael Penix Jr.', 'QB', 248.50),
(205, 8, 'Bijan Robinson', 'RB', 64.20),
(206, 8, 'Brian Robinson Jr.', 'RB', 64.20),
(207, 8, 'Drake London', 'WR', 58.80),
(208, 8, 'Jahan Dotson', 'WR', 58.80),
(209, 27, 'Darnell Mooney', 'WR', 58.80),
(210, 8, 'Kyle Pitts Sr.', 'TE', 45.30),
(211, 8, 'Charlie Woerner', 'TE', 45.30),
(212, 9, 'Bryce Young', 'QB', 248.50),
(213, 9, 'Chuba Hubbard', 'RB', 64.20),
(214, 9, 'Trevor Etienne', 'RB', 64.20),
(215, 9, 'Jonathon Brooks', 'RB', 64.20),
(216, 9, 'Tetairoa McMillan', 'WR', 58.80),
(217, 9, 'Jalen Coker', 'WR', 58.80),
(218, 9, 'Xavier Legette', 'WR', 58.80),
(219, 9, 'Tommy Tremble', 'TE', 45.30),
(220, 9, 'Ja\'Tavion Sanders', 'TE', 45.30),
(221, 9, 'Darren Waller', 'TE', 45.30),
(222, 26, 'Tyler Shough', 'QB', 248.50),
(223, 26, 'Spencer Rattler', 'QB', 248.50),
(224, 26, 'Alvin Kamara', 'RB', 64.20),
(225, 26, 'Kendre Miller', 'RB', 64.20),
(226, 26, 'Chris Olave', 'WR', 58.80),
(227, 26, 'Devaughn Vele', 'WR', 58.80),
(228, 26, 'Juwan Johnson', 'TE', 45.30),
(229, 26, 'Noah Fant', 'TE', 45.30),
(230, 32, 'Baker Mayfield', 'QB', 248.50),
(231, 32, 'Bucky Irving', 'RB', 64.20),
(232, 32, 'Kenny Gainwell', 'RB', 64.20),
(233, 32, 'Sean Tucker', 'RB', 64.20),
(234, 32, 'Emeka Egbuka', 'WR', 58.80),
(235, 32, 'Chris Godwin Jr.', 'WR', 58.80),
(236, 32, 'Cade Otton', 'TE', 45.30),
(237, 32, 'Payne Durham', 'TE', 45.30),
(238, 7, 'Gardner Minshew II', 'QB', 248.50),
(239, 7, 'Jacoby Brissett', 'QB', 248.50),
(240, 7, 'Jeremiyah Love', 'RB', 64.20),
(241, 7, 'Tyler Allgeier', 'RB', 64.20),
(242, 7, 'Trey Benson', 'RB', 64.20),
(243, 17, 'Marvin Harrison Jr.', 'WR', 58.80),
(244, 7, 'Michael Wilson', 'WR', 58.80),
(245, 7, 'Trey McBride', 'TE', 45.30),
(246, 7, 'Tip Reiman', 'TE', 45.30),
(247, 22, 'Matthew Stafford', 'QB', 248.50),
(248, 22, 'Kyren Williams', 'RB', 64.20),
(249, 22, 'Blake Corum', 'RB', 64.20),
(250, 22, 'Puka Nacua', 'WR', 58.80),
(251, 22, 'Davante Adams', 'WR', 58.80),
(252, 22, 'Jordan Whittington', 'WR', 58.80),
(253, 22, 'Tyler Higbee', 'TE', 45.30),
(254, 22, 'Colby Parkinson', 'TE', 45.30),
(255, 2, 'Isaac Guerendo', 'RB', 64.20),
(256, 2, 'Kaelon Black', 'RB', 64.20),
(257, 2, 'Mike Evans', 'WR', 58.80),
(258, 2, 'Deebo Samuel Sr.', 'WR', 58.80),
(259, 2, 'Brandon Aiyuk', 'WR', 58.80),
(260, 24, 'Jauan Jennings', 'WR', 58.80),
(261, 31, 'Eric Saubert', 'TE', 45.30),
(262, 31, 'Sam Darnold', 'QB', 248.50),
(263, 31, 'Zach Charbonnet', 'RB', 64.20),
(264, 31, 'Jadarian Price', 'RB', 64.20),
(265, 31, 'Jaxon Smith-Njigba', 'WR', 58.80),
(266, 31, 'Rashid Shaheed', 'WR', 58.80),
(267, 31, 'Cooper Kupp', 'WR', 58.80),
(268, 31, 'AJ Barner', 'TE', 45.30),
(269, 18, 'Pharaoh Brown', 'TE', 45.30);

INSERT INTO `games` (`game_id`, `sport_id`, `home_team_id`, `away_team_id`, `game_date`) VALUES
(1, 1, 1, 2, '2026-10-20 18:30:00'),
(2, 1, 3, 4, '2026-10-21 15:00:00'),
(3, 2, 5, 6, '2026-10-22 20:00:00'),
(4, 1, 20, 2, '2026-11-08 19:15:00'),
(5, 1, 28, 1, '2026-11-09 20:20:00'),
(6, 1, 23, 4, '2026-11-10 15:00:00'),
(7, 1, 30, 3, '2026-11-10 18:00:00');

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

-- Coverage performance for Travis Kelce (player_id = 2), Isiah Pacheco (player_id = 3) and Christian McCaffrey (player_id = 4)
INSERT INTO `coverage_splits` (`split_id`, `player_id`, `coverage_type`, `targets`, `receptions`, `catch_rate`) VALUES
(1, 2, 'man', 35, 26, 74.29),
(2, 2, 'zone', 58, 46, 79.31),
(3, 3, 'man', 14, 11, 78.57),
(4, 3, 'zone', 22, 18, 81.82),
(5, 4, 'man', 29, 24, 82.76),
(6, 4, 'zone', 41, 30, 73.17);

-- =====================================================
-- 5. Player Historical Game Logs (ESPN API Data Structure)
-- 5-game recent performance logs feeding player trend charts & hit rates
-- =====================================================

-- Patrick Mahomes (player_id = 1, passing_yards, line = 265.5, avg = 282.4) -> 4/5 over
INSERT INTO `player_game_logs` (`player_id`, `opponent_abbr`, `stat_type`, `stat_value`, `game_date`, `is_home`) VALUES
(1, 'LAC', 'passing_yards', 295.00, '2026-10-18', 1),
(1, 'DEN', 'passing_yards', 272.00, '2026-10-11', 0),
(1, 'LV',  'passing_yards', 312.00, '2026-10-04', 1),
(1, 'SF',  'passing_yards', 255.00, '2026-09-27', 0),
(1, 'BAL', 'passing_yards', 291.00, '2026-09-20', 1);

-- Isiah Pacheco (player_id = 3, rushing_yards, line = 62.5, avg = 66.8) -> 4/5 over
INSERT INTO `player_game_logs` (`player_id`, `opponent_abbr`, `stat_type`, `stat_value`, `game_date`, `is_home`) VALUES
(3, 'LAC', 'rushing_yards', 74.00, '2026-10-18', 1),
(3, 'DEN', 'rushing_yards', 66.00, '2026-10-11', 0),
(3, 'LV',  'rushing_yards', 82.00, '2026-10-04', 1),
(3, 'SF',  'rushing_yards', 58.00, '2026-09-27', 0),
(3, 'BAL', 'rushing_yards', 71.00, '2026-09-20', 1);

-- Christian McCaffrey (player_id = 4, rushing_yards, line = 78.5, avg = 85.5) -> 4/5 over
INSERT INTO `player_game_logs` (`player_id`, `opponent_abbr`, `stat_type`, `stat_value`, `game_date`, `is_home`) VALUES
(4, 'ARI', 'rushing_yards', 92.00, '2026-10-18', 1),
(4, 'SEA', 'rushing_yards', 88.00, '2026-10-11', 0),
(4, 'KC',  'rushing_yards', 72.00, '2026-10-04', 1),
(4, 'LAR', 'rushing_yards', 95.00, '2026-09-27', 0),
(4, 'DAL', 'rushing_yards', 84.00, '2026-09-20', 1);

-- Travis Kelce (player_id = 2, receiving_yards, avg = 71.3)
INSERT INTO `player_game_logs` (`player_id`, `opponent_abbr`, `stat_type`, `stat_value`, `game_date`, `is_home`) VALUES
(2, 'LAC', 'receiving_yards', 78.00, '2026-10-18', 1),
(2, 'DEN', 'receiving_yards', 64.00, '2026-10-11', 0),
(2, 'LV',  'receiving_yards', 89.00, '2026-10-04', 1),
(2, 'SF',  'receiving_yards', 58.00, '2026-09-27', 0),
(2, 'BAL', 'receiving_yards', 75.00, '2026-09-20', 1);

