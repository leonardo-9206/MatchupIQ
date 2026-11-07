import { mockHeatmaps } from '../mocks/heatmapMock.js';
import { mockCoverage } from '../mocks/coverageMock.js';
import { mockRouteTrees } from '../mocks/routeTreeMock.js';

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';
const TIMEOUT_MS = 2000;

/**
 * Shared GET helper for the /api/tactics/ endpoints.
 * Every endpoint answers { status: 'success', data: { player, <listKey>: [...], summary } }.
 *
 * Returns one of:
 *   { reachable: true,  data }        -> the backend has rows for this player
 *   { reachable: true,  data: null }  -> the backend answered but has no rows (e.g. 404 "No ... data found")
 *   { reachable: false, data: null }  -> offline, timeout or server error (5xx): the caller uses its mock
 */
async function fetchTactics(endpoint, playerId, listKey) {
  const url = `${BACKEND_URL}/api/tactics/${endpoint}?player_id=${encodeURIComponent(playerId)}`;

  try {
    // AbortController lets us cancel fetch() if the PHP server takes too long to answer
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(timeoutId);

    if (res.status >= 500) {
      return { reachable: false, data: null };
    }
    if (res.ok) {
      const json = await res.json();
      if (json?.status === 'success' && Array.isArray(json.data?.[listKey]) && json.data[listKey].length > 0) {
        return { reachable: true, data: json.data };
      }
    }
    return { reachable: true, data: null };
  } catch {
    // Backend offline or request aborted
    return { reachable: false, data: null };
  }
}

/**
 * Turns the helper's answer into the shape the hooks expect.
 * Mock rows are only used when the backend can't be reached: if it answers that a real
 * athlete has no data, we show an empty state instead of someone else's made-up numbers.
 */
async function loadTactics(endpoint, playerId, listKey, mock) {
  const { reachable, data } = await fetchTactics(endpoint, playerId, listKey);
  if (data) {
    return { source: 'backend', [listKey]: data[listKey], player: data.player, summary: data.summary };
  }
  if (reachable) {
    return { source: 'backend', [listKey]: [], player: null, summary: null };
  }
  return { source: 'mock', [listKey]: mock[playerId] ?? [], player: null, summary: null };
}

/**
 * Rushing zone heatmap of a player: GET /api/tactics/heatmap.php?player_id=3
 *
 * @returns {Promise<{ source: 'backend' | 'mock', zones: Array, player: object | null, summary: object | null }>}
 */
export function getPlayerHeatmap(playerId) {
  return loadTactics('heatmap.php', playerId, 'zones', mockHeatmaps);
}

/**
 * Man vs Zone coverage splits of a receiver: GET /api/tactics/coverage.php?player_id=2
 *
 * @returns {Promise<{ source: 'backend' | 'mock', splits: Array, player: object | null, summary: object | null }>}
 */
export function getPlayerCoverage(playerId) {
  return loadTactics('coverage.php', playerId, 'splits', mockCoverage);
}

/**
 * Route tree of a receiver: GET /api/tactics/routes.php?player_id=2
 *
 * @returns {Promise<{ source: 'backend' | 'mock', routes: Array, player: object | null, summary: object | null }>}
 */
export function getPlayerRouteTree(playerId) {
  return loadTactics('routes.php', playerId, 'routes', mockRouteTrees);
}
