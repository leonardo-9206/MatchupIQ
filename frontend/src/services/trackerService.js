/**
 * trackerService.js
 * Asynchronous CRUD client for Bet Tracker.
 * Communicates with backend endpoints (/api/tracker/) with graceful fallback to LocalStorage.
 * Follows schema: bet_id, user_id, prop_id, taken_odds, stake_amount, status ('pending'|'won'|'lost'|'void'), notes, created_at.
 */

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';
const STORAGE_KEY = 'matchupiq_bet_tracker';

const DEFAULT_MOCK_BETS = [
  {
    bet_id: 1,
    user_id: 2,
    prop_id: 1,
    player_name: 'Patrick Mahomes',
    player_position: 'QB',
    team_abbreviation: 'KC',
    opponent_abbreviation: 'SF',
    bet_type_display: 'Pases Completados / Yardas',
    line_over: 265.5,
    taken_odds: 1.95,
    american_odds: '+105',
    stake_amount: 500.0,
    status: 'pending', // 'pending' | 'won' | 'lost' | 'void'
    bookmaker_name: 'DraftKings',
    notes: 'Excelente matchup contra secundaria mermada de SF. Clima despejado.',
    created_at: '2026-10-25 14:30:00',
  },
  {
    bet_id: 2,
    user_id: 2,
    prop_id: 3,
    player_name: 'Isiah Pacheco',
    player_position: 'RB',
    team_abbreviation: 'KC',
    opponent_abbreviation: 'SF',
    bet_type_display: 'Yardas por Acarreo',
    line_over: 64.5,
    taken_odds: 2.00,
    american_odds: '+100',
    stake_amount: 400.0,
    status: 'won',
    bookmaker_name: 'FanDuel',
    notes: 'Racha de 5/5 cubiertos. Acarreos en zona izquierda dominantes.',
    created_at: '2026-10-24 18:00:00',
  },
  {
    bet_id: 3,
    user_id: 2,
    prop_id: 5,
    player_name: 'Henry Martín',
    player_position: 'DEL',
    team_abbreviation: 'AME',
    opponent_abbreviation: 'CHIV',
    bet_type_display: 'Tiros al Arco',
    line_over: 1.5,
    taken_odds: 2.10,
    american_odds: '+110',
    stake_amount: 300.0,
    status: 'lost',
    bookmaker_name: 'DraftKings',
    notes: 'Clásico Nacional cerrado, solo tuvo 1 tiro a puerta.',
    created_at: '2026-10-22 20:00:00',
  },
];

function getLocalBets() {
  const data = localStorage.getItem(STORAGE_KEY);
  if (!data) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(DEFAULT_MOCK_BETS));
    return DEFAULT_MOCK_BETS;
  }
  try {
    return JSON.parse(data);
  } catch {
    return DEFAULT_MOCK_BETS;
  }
}

function saveLocalBets(bets) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(bets));
}

/**
 * Fetch all bets for current user
 */
export async function getBets() {
  try {
    const res = await fetch(`${BACKEND_URL}/api/tracker/index.php`, {
      method: 'GET',
      credentials: 'include',
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.status === 'success' && Array.isArray(json.data?.bets)) {
        return { source: 'backend', bets: json.data.bets };
      }
    }
  } catch {
    // Offline fallback
  }

  return { source: 'local', bets: getLocalBets() };
}

/**
 * Create a new bet
 */
export async function createBet(betData) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/tracker/create.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(betData),
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.status === 'success') {
        return { success: true, bet: json.data.bet };
      }
    }
  } catch {
    // Offline fallback
  }

  // Local fallback
  const bets = getLocalBets();
  const newBet = {
    bet_id: Date.now(),
    user_id: betData.user_id || 2,
    prop_id: betData.prop_id || null,
    player_name: betData.player_name || 'Apuesta Personalizada',
    player_position: betData.player_position || 'PROP',
    team_abbreviation: betData.team_abbreviation || 'NFL',
    opponent_abbreviation: betData.opponent_abbreviation || 'OPP',
    bet_type_display: betData.bet_type_display || 'Player Prop',
    line_over: parseFloat(betData.line_over || 0),
    taken_odds: parseFloat(betData.taken_odds || 1.9),
    american_odds: betData.american_odds || '-110',
    stake_amount: parseFloat(betData.stake_amount || 100),
    status: betData.status || 'pending',
    bookmaker_name: betData.bookmaker_name || 'DraftKings',
    notes: betData.notes || '',
    created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
  };

  const updated = [newBet, ...bets];
  saveLocalBets(updated);
  return { success: true, bet: newBet };
}

/**
 * Update an existing bet (status, notes, stake)
 */
export async function updateBet(betId, updates) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/tracker/update.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ bet_id: betId, ...updates }),
    });
    if (res.ok) {
      const json = await res.json();
      if (json && json.status === 'success') {
        return { success: true, bet: json.data.bet };
      }
    }
  } catch {
    // Offline fallback
  }

  const bets = getLocalBets();
  const updated = bets.map((b) => (b.bet_id === betId ? { ...b, ...updates } : b));
  saveLocalBets(updated);
  return { success: true, bet: updated.find((b) => b.bet_id === betId) };
}

/**
 * Delete a bet
 */
export async function deleteBet(betId) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/tracker/delete.php?id=${betId}`, {
      method: 'DELETE',
      credentials: 'include',
    });
    if (res.ok) {
      return { success: true };
    }
  } catch {
    // Offline fallback
  }

  const bets = getLocalBets();
  const filtered = bets.filter((b) => b.bet_id !== betId);
  saveLocalBets(filtered);
  return { success: true };
}

/**
 * Calculate summary metrics for Bet Tracker
 */
export function calculateTrackerMetrics(bets) {
  let totalStaked = 0;
  let totalProfit = 0;
  let wonCount = 0;
  let lostCount = 0;
  let pendingCount = 0;

  for (const bet of bets) {
    totalStaked += bet.stake_amount;

    if (bet.status === 'won') {
      wonCount++;
      const returnAmount = bet.stake_amount * bet.taken_odds;
      totalProfit += returnAmount - bet.stake_amount;
    } else if (bet.status === 'lost') {
      lostCount++;
      totalProfit -= bet.stake_amount;
    } else if (bet.status === 'pending') {
      pendingCount++;
    }
  }

  const finishedCount = wonCount + lostCount;
  const winRate = finishedCount > 0 ? (wonCount / finishedCount) * 100 : 0;
  const roi = totalStaked > 0 ? (totalProfit / totalStaked) * 100 : 0;

  return {
    totalBets: bets.length,
    wonCount,
    lostCount,
    pendingCount,
    winRate: winRate.toFixed(1),
    totalStaked: totalStaked.toFixed(2),
    totalProfit: totalProfit.toFixed(2),
    roi: roi.toFixed(1),
    isProfitable: totalProfit >= 0,
  };
}
