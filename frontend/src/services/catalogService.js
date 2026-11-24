/**
 * catalogService.js
 * Asynchronous service for Managing Teams and Players Catalogs (Admin CRUD - Sprint 4).
 * Supports backend endpoints with fallback to LocalStorage for offline dev.
 */

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';
const TEAMS_KEY = 'matchupiq_admin_teams';
const PLAYERS_KEY = 'matchupiq_admin_players';

const DEFAULT_TEAMS = [
  { team_id: 1, sport_id: 1, sport_name: 'NFL', name: 'Kansas City Chiefs', abbreviation: 'KC', defensive_rank: 4 },
  { team_id: 2, sport_id: 1, sport_name: 'NFL', name: 'San Francisco 49ers', abbreviation: 'SF', defensive_rank: 7 },
  { team_id: 3, sport_id: 1, sport_name: 'NFL', name: 'Baltimore Ravens', abbreviation: 'BAL', defensive_rank: 3 },
  { team_id: 4, sport_id: 1, sport_name: 'NFL', name: 'Buffalo Bills', abbreviation: 'BUF', defensive_rank: 11 },
  { team_id: 5, sport_id: 1, sport_name: 'NFL', name: 'Detroit Lions', abbreviation: 'DET', defensive_rank: 10 },
  { team_id: 6, sport_id: 1, sport_name: 'NFL', name: 'Philadelphia Eagles', abbreviation: 'PHI', defensive_rank: 5 },
  { team_id: 7, sport_id: 2, sport_name: 'Liga MX', name: 'Club América', abbreviation: 'AME', defensive_rank: 2 },
  { team_id: 8, sport_id: 2, sport_name: 'Liga MX', name: 'CD Guadalajara', abbreviation: 'CHIV', defensive_rank: 8 },
  { team_id: 9, sport_id: 2, sport_name: 'Liga MX', name: 'Cruz Azul', abbreviation: 'CAZ', defensive_rank: 1 },
  { team_id: 10, sport_id: 2, sport_name: 'Liga MX', name: 'Tigres UANL', abbreviation: 'TIG', defensive_rank: 4 },
];

const DEFAULT_PLAYERS = [
  { player_id: 1, team_id: 1, team_name: 'Kansas City Chiefs', team_abbreviation: 'KC', name: 'Patrick Mahomes', position: 'QB', stat_average: 282.40, sport_name: 'NFL' },
  { player_id: 2, team_id: 1, team_name: 'Kansas City Chiefs', team_abbreviation: 'KC', name: 'Travis Kelce', position: 'TE', stat_average: 71.30, sport_name: 'NFL' },
  { player_id: 3, team_id: 5, team_name: 'Detroit Lions', team_abbreviation: 'DET', name: 'Isiah Pacheco', position: 'RB', stat_average: 66.80, sport_name: 'NFL' },
  { player_id: 4, team_id: 2, team_name: 'San Francisco 49ers', team_abbreviation: 'SF', name: 'Christian McCaffrey', position: 'RB', stat_average: 85.50, sport_name: 'NFL' },
  { player_id: 5, team_id: 7, team_name: 'Club América', team_abbreviation: 'AME', name: 'Henry Martín', position: 'DEL', stat_average: 0.65, sport_name: 'Liga MX' },
  { player_id: 6, team_id: 8, team_name: 'CD Guadalajara', team_abbreviation: 'CHIV', name: 'Javier Hernández', position: 'DEL', stat_average: 0.40, sport_name: 'Liga MX' },
  { player_id: 7, team_id: 3, team_name: 'Baltimore Ravens', team_abbreviation: 'BAL', name: 'Lamar Jackson', position: 'QB', stat_average: 245.80, sport_name: 'NFL' },
  { player_id: 8, team_id: 4, team_name: 'Buffalo Bills', team_abbreviation: 'BUF', name: 'Josh Allen', position: 'QB', stat_average: 268.20, sport_name: 'NFL' },
];

function getStored(key, fallback) {
  const data = localStorage.getItem(key);
  if (!data) {
    localStorage.setItem(key, JSON.stringify(fallback));
    return fallback;
  }
  try {
    return JSON.parse(data);
  } catch {
    return fallback;
  }
}

function setStored(key, data) {
  localStorage.setItem(key, JSON.stringify(data));
}

// ----------------- TEAMS CRUD -----------------

export async function getTeams() {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/teams/index.php`, {
      method: 'GET',
      credentials: 'include',
    });
    if (res.ok) {
      const json = await res.json();
      if (json && (json.status === 'success' || json.success) && Array.isArray(json.data?.teams)) {
        return { source: 'backend', teams: json.data.teams };
      }
    }
  } catch {
    // Offline
  }
  return { source: 'local', teams: getStored(TEAMS_KEY, DEFAULT_TEAMS) };
}

export async function createTeam(teamData) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/teams/create.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(teamData),
    });
    if (res.ok) {
      const json = await res.json();
      if (json && (json.status === 'success' || json.success)) {
        return { success: true, team: json.data.team };
      }
    }
  } catch {
    // Offline
  }

  const teams = getStored(TEAMS_KEY, DEFAULT_TEAMS);
  const newTeam = {
    team_id: Date.now(),
    sport_id: parseInt(teamData.sport_id) || 1,
    sport_name: parseInt(teamData.sport_id) === 2 ? 'Liga MX' : 'NFL',
    name: teamData.name.trim(),
    abbreviation: teamData.abbreviation.trim().toUpperCase(),
    defensive_rank: parseInt(teamData.defensive_rank) || 16,
  };
  const updated = [newTeam, ...teams];
  setStored(TEAMS_KEY, updated);
  return { success: true, team: newTeam };
}

export async function updateTeam(teamId, updates) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/teams/update.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ team_id: teamId, ...updates }),
    });
    if (res.ok) {
      const json = await res.json();
      if (json && (json.status === 'success' || json.success)) {
        return { success: true, team: json.data.team };
      }
    }
  } catch {
    // Offline
  }

  const teams = getStored(TEAMS_KEY, DEFAULT_TEAMS);
  const updated = teams.map((t) => (t.team_id === teamId ? { ...t, ...updates } : t));
  setStored(TEAMS_KEY, updated);
  return { success: true, team: updated.find((t) => t.team_id === teamId) };
}

export async function deleteTeam(teamId) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/teams/delete.php?id=${teamId}`, {
      method: 'DELETE',
      credentials: 'include',
    });
    if (res.ok) return { success: true };
  } catch {
    // Offline
  }

  const teams = getStored(TEAMS_KEY, DEFAULT_TEAMS);
  const filtered = teams.filter((t) => t.team_id !== teamId);
  setStored(TEAMS_KEY, filtered);
  return { success: true };
}

// ----------------- PLAYERS CRUD -----------------

export async function getPlayers() {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/players/index.php`, {
      method: 'GET',
      credentials: 'include',
    });
    if (res.ok) {
      const json = await res.json();
      if (json && (json.status === 'success' || json.success) && Array.isArray(json.data?.players)) {
        return { source: 'backend', players: json.data.players };
      }
    }
  } catch {
    // Offline
  }
  return { source: 'local', players: getStored(PLAYERS_KEY, DEFAULT_PLAYERS) };
}

export async function createPlayer(playerData) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/players/create.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(playerData),
    });
    if (res.ok) {
      const json = await res.json();
      if (json && (json.status === 'success' || json.success)) {
        return { success: true, player: json.data.player };
      }
    }
  } catch {
    // Offline
  }

  const players = getStored(PLAYERS_KEY, DEFAULT_PLAYERS);
  const teams = getStored(TEAMS_KEY, DEFAULT_TEAMS);
  const team = teams.find((t) => t.team_id === parseInt(playerData.team_id)) || teams[0];

  const newPlayer = {
    player_id: Date.now(),
    team_id: team ? team.team_id : 1,
    team_name: team ? team.name : 'Kansas City Chiefs',
    team_abbreviation: team ? team.abbreviation : 'KC',
    name: playerData.name.trim(),
    position: playerData.position.trim().toUpperCase(),
    stat_average: parseFloat(playerData.stat_average) || 0.0,
    sport_name: team ? team.sport_name : 'NFL',
  };
  const updated = [newPlayer, ...players];
  setStored(PLAYERS_KEY, updated);
  return { success: true, player: newPlayer };
}

export async function updatePlayer(playerId, updates) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/players/update.php`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ player_id: playerId, ...updates }),
    });
    if (res.ok) {
      const json = await res.json();
      if (json && (json.status === 'success' || json.success)) {
        return { success: true, player: json.data.player };
      }
    }
  } catch {
    // Offline
  }

  const players = getStored(PLAYERS_KEY, DEFAULT_PLAYERS);
  const teams = getStored(TEAMS_KEY, DEFAULT_TEAMS);
  const updated = players.map((p) => {
    if (p.player_id === playerId) {
      const teamId = updates.team_id ? parseInt(updates.team_id) : p.team_id;
      const team = teams.find((t) => t.team_id === teamId);
      return {
        ...p,
        ...updates,
        team_id: teamId,
        team_name: team ? team.name : p.team_name,
        team_abbreviation: team ? team.abbreviation : p.team_abbreviation,
        sport_name: team ? team.sport_name : p.sport_name,
      };
    }
    return p;
  });
  setStored(PLAYERS_KEY, updated);
  return { success: true, player: updated.find((p) => p.player_id === playerId) };
}

export async function deletePlayer(playerId) {
  try {
    const res = await fetch(`${BACKEND_URL}/api/admin/players/delete.php?id=${playerId}`, {
      method: 'DELETE',
      credentials: 'include',
    });
    if (res.ok) return { success: true };
  } catch {
    // Offline
  }

  const players = getStored(PLAYERS_KEY, DEFAULT_PLAYERS);
  const filtered = players.filter((p) => p.player_id !== playerId);
  setStored(PLAYERS_KEY, filtered);
  return { success: true };
}
