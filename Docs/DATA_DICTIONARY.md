# MatchupIQ — Data Dictionary & Database Specification

This document serves as the **Single Source of Truth (SSOT)** for database schema, table definitions, column names, and relationships across all agents working on MatchupIQ. All SQL migrations, PHP PDO models, and frontend interfaces must strictly follow these names.

---

## 1. Relational Schema Overview (3NF)

* **Engine:** InnoDB
* **Charset:** `utf8mb4`
* **Collation:** `utf8mb4_unicode_ci`

```
  roles ──< users ──< bet_tracker >── props >── bookmakers
                         │               │
                         │               ├──< players >── teams >── sports
                         │               │       │           │         │
                         │               │       ├──< player_heatmaps  │
                         │               │       └──< coverage_splits  │
                         │               │                             │
                         │               └──< games ───────────────────┘
```

---

## 2. Table Definitions

### 2.1 `roles`
Catalog of system user roles for RBAC.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `role_id` | INT | PK, AUTO_INCREMENT | Unique identifier for role |
| `role_name` | VARCHAR(50) | NOT NULL, UNIQUE | Role identifier (`admin`, `user`) |

### 2.2 `users`
Registered user accounts.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `user_id` | INT | PK, AUTO_INCREMENT | Unique user identifier |
| `role_id` | INT | FK -> `roles(role_id)`, NOT NULL | Assigned role |
| `full_name` | VARCHAR(100) | NOT NULL | User's displayed name |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE | Email address for login |
| `password_hash` | VARCHAR(255) | NOT NULL | Bcrypt hashed password |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Account creation timestamp |

### 2.3 `sports`
Supported sports leagues.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `sport_id` | INT | PK, AUTO_INCREMENT | Unique sport identifier |
| `name` | VARCHAR(50) | NOT NULL | League name (`NFL`, `Liga MX`) |
| `slug` | VARCHAR(50) | NOT NULL, UNIQUE | URL-friendly slug (`nfl`, `ligamx`) |

### 2.4 `teams`
Teams participating in each sport.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `team_id` | INT | PK, AUTO_INCREMENT | Unique team identifier |
| `sport_id` | INT | FK -> `sports(sport_id)`, NOT NULL | Parent league |
| `name` | VARCHAR(100) | NOT NULL | Full team name |
| `abbreviation` | VARCHAR(10) | NOT NULL | Short code (`KC`, `SF`, `AME`, `CHIV`) |
| `defensive_rank` | INT | NOT NULL | Defensive concession rank (1-32) |

### 2.5 `players`
Athletes evaluated for prop bets and tactical breakdowns.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `player_id` | INT | PK, AUTO_INCREMENT | Unique player identifier |
| `team_id` | INT | FK -> `teams(team_id)`, NOT NULL | Current player team |
| `name` | VARCHAR(100) | NOT NULL | Athlete full name |
| `position` | VARCHAR(20) | NOT NULL | Position (`QB`, `WR`, `RB`, `ST`, etc.) |
| `stat_average` | DECIMAL(6,2) | NOT NULL, DEFAULT 0.00 | Season performance average |

### 2.6 `games`
Scheduled matches.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `game_id` | INT | PK, AUTO_INCREMENT | Unique match identifier |
| `sport_id` | INT | FK -> `sports(sport_id)`, NOT NULL | Sport league |
| `home_team_id` | INT | FK -> `teams(team_id)`, NOT NULL | Home team |
| `away_team_id` | INT | FK -> `teams(team_id)`, NOT NULL | Visiting team |
| `game_date` | DATETIME | NOT NULL | Match scheduled kickoff |

### 2.7 `bookmakers`
Sportsbooks tracked for odds comparison.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `bookmaker_id` | INT | PK, AUTO_INCREMENT | Unique bookmaker identifier |
| `name` | VARCHAR(50) | NOT NULL | Commercial name (`Bet365`, `Caliente`, etc.) |
| `affiliate_url` | VARCHAR(255) | NULL | Optional tracking URL |

### 2.8 `props`
Player prop betting lines with calculated Edge values.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `prop_id` | INT | PK, AUTO_INCREMENT | Unique prop identifier |
| `player_id` | INT | FK -> `players(player_id)`, NOT NULL | Evaluated player |
| `bookmaker_id` | INT | FK -> `bookmakers(bookmaker_id)`, NOT NULL | Bookmaker offering the line |
| `game_id` | INT | FK -> `games(game_id)`, NOT NULL | Associated game |
| `bet_type` | VARCHAR(50) | NOT NULL | Stat market (`passing_yards`, `rushing_yards`, etc.) |
| `line_over` | DECIMAL(6,2) | NOT NULL | Numerical betting line threshold |
| `odds_over` | DECIMAL(6,2) | NOT NULL | Decimal odds for the Over |
| `odds_under` | DECIMAL(6,2) | NOT NULL | Decimal odds for the Under |
| `calculated_edge`| DECIMAL(5,2) | NOT NULL, DEFAULT 0.00 | Edge percentage (+EV calculation) |
| `updated_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last update |

### 2.9 `bet_tracker`
User-saved picks and performance logs (Full CRUD).
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `bet_id` | INT | PK, AUTO_INCREMENT | Unique record identifier |
| `user_id` | INT | FK -> `users(user_id)`, NOT NULL | Owning user (BOLA check) |
| `prop_id` | INT | FK -> `props(prop_id)`, NOT NULL | Selected prop |
| `taken_odds` | DECIMAL(6,2) | NOT NULL | Locked odds value at bet time |
| `stake_amount` | DECIMAL(10,2) | NOT NULL | Amount wagered |
| `status` | ENUM('pending', 'won', 'lost', 'void') | NOT NULL, DEFAULT 'pending' | Current pick result |
| `notes` | TEXT | NULL | User custom analysis notes |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Log timestamp |

### 2.10 `player_heatmaps`
Ball-carrier rushing and passing zone breakdown for interactive heatmaps.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `heatmap_id` | INT | PK, AUTO_INCREMENT | Unique heatmap metric identifier |
| `player_id` | INT | FK -> `players(player_id)`, NOT NULL | Evaluated athlete |
| `field_zone` | ENUM('left_end', 'left_tackle', 'middle', 'right_tackle', 'right_end') | NOT NULL | Field run/pass lane zone |
| `attempts` | INT | NOT NULL, DEFAULT 0 | Rushing/target volume in zone |
| `yards_gained`| INT | NOT NULL, DEFAULT 0 | Total yards accumulated in zone |
| `success_rate`| DECIMAL(5,2) | NOT NULL, DEFAULT 0.00 | Efficiency percentage in zone |

### 2.11 `coverage_splits`
Offensive player performance against defensive coverage schemes.
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `split_id` | INT | PK, AUTO_INCREMENT | Unique split identifier |
| `player_id` | INT | FK -> `players(player_id)`, NOT NULL | Evaluated athlete |
| `coverage_type` | ENUM('man', 'zone') | NOT NULL | Scheme encountered |
| `targets` | INT | NOT NULL, DEFAULT 0 | Pass attempts targeted to player |
| `receptions` | INT | NOT NULL, DEFAULT 0 | Successful completions |
| `catch_rate` | DECIMAL(5,2) | NOT NULL, DEFAULT 0.00 | Completion efficiency percentage |
