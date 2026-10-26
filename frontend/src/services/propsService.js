/**
 * propsService.js
 * Asynchronous data client for player props and odds comparison.
 * Connects via Fetch API to the PHP backend (/api/props/) with graceful fallback to rich mock data.
 * Complies with Docs/DATA_DICTIONARY.md and US-06 requirements.
 */

// USA Sportsbooks only (The Odds API supported sportsbooks for props)
export const USA_BOOKMAKERS = [
  { id: 1, name: 'DraftKings', code: 'draftkings', color: '#53d337' },
  { id: 2, name: 'FanDuel', code: 'fanduel', color: '#1493ff' },
  { id: 3, name: 'BetMGM', code: 'betmgm', color: '#c3a25b' },
  { id: 4, name: 'Caesars', code: 'caesars', color: '#257551' },
];

export const MOCK_PROPS = [
  {
    prop_id: 1,
    sport_name: 'NFL',
    sport_slug: 'nfl',
    player_id: 1,
    player_name: 'Patrick Mahomes',
    player_position: 'QB',
    team_name: 'Kansas City Chiefs',
    team_abbreviation: 'KC',
    opponent_abbreviation: 'SF',
    opponent_rank: 7,
    bet_type: 'passing_yards',
    bet_type_display: 'Pases Completados / Yardas',
    line_over: 265.5,
    stat_average: 282.4,
    odds_over: 1.95,
    odds_under: 1.85,
    american_odds: '+105',
    calculated_edge: 14.8,
    is_featured: true,
    streak_hits: '4/5',
    game_date: '2026-10-27 18:30:00',
    best_bookmaker: 'DraftKings',
    recent_games: [
      { game: 'vs DEN', value: 291, line: 265.5, hit: true },
      { game: '@ LAC', value: 275, line: 265.5, hit: true },
      { game: 'vs LV', value: 242, line: 265.5, hit: false },
      { game: '@ BAL', value: 310, line: 265.5, hit: true },
      { game: 'vs CIN', value: 288, line: 265.5, hit: true },
    ],
    bookmakers_odds: [
      { name: 'DraftKings', line: 265.5, over: '+105', decimal: 2.05, is_best: true },
      { name: 'FanDuel', line: 266.5, over: '-110', decimal: 1.91, is_best: false },
      { name: 'BetMGM', line: 265.5, over: '-115', decimal: 1.87, is_best: false },
      { name: 'Caesars', line: 267.5, over: '-108', decimal: 1.92, is_best: false },
    ],
  },
  {
    prop_id: 2,
    sport_name: 'NFL',
    sport_slug: 'nfl',
    player_id: 3,
    player_name: 'Isiah Pacheco',
    player_position: 'RB',
    team_name: 'Kansas City Chiefs',
    team_abbreviation: 'KC',
    opponent_abbreviation: 'SF',
    opponent_rank: 12,
    bet_type: 'rushing_yards',
    bet_type_display: 'Yardas por Acarreo',
    line_over: 64.5,
    stat_average: 72.8,
    odds_over: 1.91,
    odds_under: 1.89,
    american_odds: '-110',
    calculated_edge: 12.3,
    is_featured: true,
    streak_hits: '5/5',
    game_date: '2026-10-27 18:30:00',
    best_bookmaker: 'FanDuel',
    recent_games: [
      { game: 'vs DEN', value: 78, line: 64.5, hit: true },
      { game: '@ LAC', value: 84, line: 64.5, hit: true },
      { game: 'vs LV', value: 68, line: 64.5, hit: true },
      { game: '@ BAL', value: 92, line: 64.5, hit: true },
      { game: 'vs CIN', value: 71, line: 64.5, hit: true },
    ],
    bookmakers_odds: [
      { name: 'DraftKings', line: 65.5, over: '-112', decimal: 1.89, is_best: false },
      { name: 'FanDuel', line: 64.5, over: '+100', decimal: 2.00, is_best: true },
      { name: 'BetMGM', line: 64.5, over: '-115', decimal: 1.87, is_best: false },
      { name: 'Caesars', line: 65.0, over: '-110', decimal: 1.91, is_best: false },
    ],
  },
  {
    prop_id: 3,
    sport_name: 'NFL',
    sport_slug: 'nfl',
    player_id: 2,
    player_name: 'Travis Kelce',
    player_position: 'TE',
    team_name: 'Kansas City Chiefs',
    team_abbreviation: 'KC',
    opponent_abbreviation: 'SF',
    opponent_rank: 15,
    bet_type: 'receiving_yards',
    bet_type_display: 'Yardas por Recepción',
    line_over: 68.5,
    stat_average: 74.2,
    odds_over: 1.88,
    odds_under: 1.92,
    american_odds: '-114',
    calculated_edge: 10.9,
    is_featured: true,
    streak_hits: '4/5',
    game_date: '2026-10-27 18:30:00',
    best_bookmaker: 'BetMGM',
    recent_games: [
      { game: 'vs DEN', value: 85, line: 68.5, hit: true },
      { game: '@ LAC', value: 72, line: 68.5, hit: true },
      { game: 'vs LV', value: 54, line: 68.5, hit: false },
      { game: '@ BAL', value: 90, line: 68.5, hit: true },
      { game: 'vs CIN', value: 81, line: 68.5, hit: true },
    ],
    bookmakers_odds: [
      { name: 'DraftKings', line: 69.5, over: '-115', decimal: 1.87, is_best: false },
      { name: 'FanDuel', line: 68.5, over: '-112', decimal: 1.89, is_best: false },
      { name: 'BetMGM', line: 68.5, over: '-105', decimal: 1.95, is_best: true },
      { name: 'Caesars', line: 69.0, over: '-110', decimal: 1.91, is_best: false },
    ],
  },
  {
    prop_id: 4,
    sport_name: 'NFL',
    sport_slug: 'nfl',
    player_id: 4,
    player_name: 'Christian McCaffrey',
    player_position: 'RB',
    team_name: 'San Francisco 49ers',
    team_abbreviation: 'SF',
    opponent_abbreviation: 'KC',
    opponent_rank: 4,
    bet_type: 'rushing_yards',
    bet_type_display: 'Yardas por Acarreo',
    line_over: 82.5,
    stat_average: 88.5,
    odds_over: 1.90,
    odds_under: 1.90,
    american_odds: '-110',
    calculated_edge: 8.7,
    is_featured: false,
    streak_hits: '3/5',
    game_date: '2026-10-27 18:30:00',
    best_bookmaker: 'Caesars',
    recent_games: [
      { game: 'vs SEA', value: 95, line: 82.5, hit: true },
      { game: '@ LAR', value: 76, line: 82.5, hit: false },
      { game: 'vs ARI', value: 104, line: 82.5, hit: true },
      { game: '@ DAL', value: 85, line: 82.5, hit: true },
      { game: 'vs MIN', value: 68, line: 82.5, hit: false },
    ],
    bookmakers_odds: [
      { name: 'DraftKings', line: 83.5, over: '-115', decimal: 1.87, is_best: false },
      { name: 'FanDuel', line: 82.5, over: '-110', decimal: 1.91, is_best: false },
      { name: 'BetMGM', line: 83.5, over: '-112', decimal: 1.89, is_best: false },
      { name: 'Caesars', line: 82.5, over: '+102', decimal: 2.02, is_best: true },
    ],
  },
  {
    prop_id: 5,
    sport_name: 'Liga MX',
    sport_slug: 'ligamx',
    player_id: 5,
    player_name: 'Henry Martín',
    player_position: 'DEL',
    team_name: 'Club América',
    team_abbreviation: 'AME',
    opponent_abbreviation: 'CHIV',
    opponent_rank: 8,
    bet_type: 'shots_on_target',
    bet_type_display: 'Tiros al Arco',
    line_over: 1.5,
    stat_average: 2.1,
    odds_over: 2.10,
    odds_under: 1.72,
    american_odds: '+110',
    calculated_edge: 11.4,
    is_featured: true,
    streak_hits: '4/5',
    game_date: '2026-10-28 20:00:00',
    best_bookmaker: 'DraftKings',
    recent_games: [
      { game: 'vs CRUZ', value: 3, line: 1.5, hit: true },
      { game: '@ PUM', value: 2, line: 1.5, hit: true },
      { game: 'vs TOL', value: 1, line: 1.5, hit: false },
      { game: '@ MON', value: 2, line: 1.5, hit: true },
      { game: 'vs TIG', value: 2, line: 1.5, hit: true },
    ],
    bookmakers_odds: [
      { name: 'DraftKings', line: 1.5, over: '+110', decimal: 2.10, is_best: true },
      { name: 'FanDuel', line: 1.5, over: '+102', decimal: 2.02, is_best: false },
      { name: 'BetMGM', line: 1.5, over: '-105', decimal: 1.95, is_best: false },
      { name: 'Caesars', line: 1.5, over: '+105', decimal: 2.05, is_best: false },
    ],
  },
  {
    prop_id: 6,
    sport_name: 'Liga MX',
    sport_slug: 'ligamx',
    player_id: 6,
    player_name: 'Alexis Vega',
    player_position: 'MED',
    team_name: 'Toluca FC',
    team_abbreviation: 'TOL',
    opponent_abbreviation: 'LEON',
    opponent_rank: 14,
    bet_type: 'shots_on_target',
    bet_type_display: 'Tiros al Arco',
    line_over: 1.5,
    stat_average: 1.9,
    odds_over: 1.95,
    odds_under: 1.85,
    american_odds: '-105',
    calculated_edge: 7.8,
    is_featured: false,
    streak_hits: '3/5',
    game_date: '2026-10-28 21:05:00',
    best_bookmaker: 'FanDuel',
    recent_games: [
      { game: 'vs PAC', value: 2, line: 1.5, hit: true },
      { game: '@ JUA', value: 2, line: 1.5, hit: true },
      { game: 'vs SAN', value: 0, line: 1.5, hit: false },
      { game: '@ AME', value: 1, line: 1.5, hit: false },
      { game: 'vs QUE', value: 3, line: 1.5, hit: true },
    ],
    bookmakers_odds: [
      { name: 'DraftKings', line: 1.5, over: '-110', decimal: 1.91, is_best: false },
      { name: 'FanDuel', line: 1.5, over: '+104', decimal: 2.04, is_best: true },
      { name: 'BetMGM', line: 1.5, over: '-115', decimal: 1.87, is_best: false },
      { name: 'Caesars', line: 1.5, over: '-105', decimal: 1.95, is_best: false },
    ],
  },
];

/**
 * Fetch props with optional sport filter.
 * Tries the PHP backend first; falls back smoothly to MOCK_PROPS if offline.
 */
export async function getProps(sport = null) {
  const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';
  const url = sport && sport !== 'all' 
    ? `${backendUrl}/api/props/index.php?sport=${encodeURIComponent(sport)}`
    : `${backendUrl}/api/props/index.php`;

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 2000); // 2s timeout for snappy UI

    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(timeoutId);

    if (res.ok) {
      const data = await res.json();
      if (data && data.success && Array.isArray(data.data?.props) && data.data.props.length > 0) {
        return {
          source: 'backend',
          props: data.data.props,
        };
      }
    }
  } catch (err) {
    // Backend offline or running in standalone Vite mode
  }

  // Fallback to client mock data
  const filtered = sport && sport !== 'all'
    ? MOCK_PROPS.filter(p => p.sport_slug === sport)
    : MOCK_PROPS;

  return {
    source: 'mock',
    props: filtered,
  };
}
