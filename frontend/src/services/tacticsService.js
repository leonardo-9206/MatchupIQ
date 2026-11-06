import { mockHeatmaps } from '../mocks/heatmapMock.js';
import { mockCoverage } from '../mocks/coverageMock.js';
import { mockRouteTrees } from '../mocks/routeTreeMock.js';

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';
const TIMEOUT_MS = 2000;

/**
 * Shared GET helper for the /api/tactics/ endpoints.
 * Every endpoint answers { status: 'success', data: { player, <listKey>: [...], summary } }.
 * Returns that `data` object, or null if the backend is offline, times out or has no rows,
 * so each caller can decide which mock to use instead.
 */
async function fetchTactics(endpoint, playerId, listKey) {
  const url = `${BACKEND_URL}/api/tactics/${endpoint}?player_id=${encodeURIComponent(playerId)}`;

  try {
    // AbortController lets us cancel fetch() if the PHP server takes too long to answer
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(timeoutId);

    if (res.ok) {
      const json = await res.json();
      if (json?.status === 'success' && Array.isArray(json.data?.[listKey]) && json.data[listKey].length > 0) {
        return json.data;
      }
    }
  } catch {
    // Backend offline or request aborted: the caller falls back to its mock
  }

  return null;
}

/**
 * Rushing zone heatmap of a player (Joao's endpoint):
 * GET /api/tactics/heatmap.php?player_id=3
 *
 * @returns {Promise<{ source: 'backend' | 'mock', zones: Array, player: object | null, summary: object | null }>}
 */
export async function getPlayerHeatmap(playerId) {
  const data = await fetchTactics('heatmap.php', playerId, 'zones');
  if (data) {
    return { source: 'backend', zones: data.zones, player: data.player, summary: data.summary };
  }
  return { source: 'mock', zones: mockHeatmaps[playerId] ?? [], player: null, summary: null };
}

/**
 * Man vs Zone coverage splits of a receiver (Joao's endpoint):
 * GET /api/tactics/coverage.php?player_id=2
 *
 * @returns {Promise<{ source: 'backend' | 'mock', splits: Array, player: object | null, summary: object | null }>}
 */
export async function getPlayerCoverage(playerId) {
  const data = await fetchTactics('coverage.php', playerId, 'splits');
  if (data) {
    return { source: 'backend', splits: data.splits, player: data.player, summary: data.summary };
  }
  return { source: 'mock', splits: mockCoverage[playerId] ?? [], player: null, summary: null };
}

/**
 * Route tree of a receiver.
 * There is no table or endpoint for routes yet, so this always uses the mock for now.
 * The proposed contract is GET /api/tactics/routes.php?player_id=2 -> data.routes (see routeTreeMock.js);
 * once the backend exists, this function already knows how to read it.
 *
 * @returns {Promise<{ source: 'backend' | 'mock', routes: Array, player: object | null, summary: object | null }>}
 */
export async function getPlayerRouteTree(playerId) {
  const data = await fetchTactics('routes.php', playerId, 'routes');
  if (data) {
    return { source: 'backend', routes: data.routes, player: data.player, summary: data.summary };
  }
  return { source: 'mock', routes: mockRouteTrees[playerId] ?? [], player: null, summary: null };
}
