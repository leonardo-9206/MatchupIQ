import { mockHeatmaps } from '../mocks/heatmapMock.js';

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';
const TIMEOUT_MS = 2000;

/**
 * Fetches the rushing zone heatmap of a player from Joao's endpoint:
 * GET /api/tactics/heatmap.php?player_id=3
 *
 * The backend answers { status: 'success', data: { player, zones, summary } }.
 * If it is offline, times out or has no data for that player, we fall back to the local mock
 * so the field still renders while developing with Vite alone.
 *
 * @returns {Promise<{ source: 'backend' | 'mock', zones: Array, player: object | null, summary: object | null }>}
 */
export async function getPlayerHeatmap(playerId) {
  const url = `${BACKEND_URL}/api/tactics/heatmap.php?player_id=${encodeURIComponent(playerId)}`;

  try {
    // AbortController lets us cancel fetch() if the PHP server takes too long to answer
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(timeoutId);

    if (res.ok) {
      const json = await res.json();
      if (json?.status === 'success' && Array.isArray(json.data?.zones) && json.data.zones.length > 0) {
        return {
          source: 'backend',
          zones: json.data.zones,
          player: json.data.player,
          summary: json.data.summary,
        };
      }
    }
  } catch {
    // Backend offline or request aborted: use the mock below
  }

  return {
    source: 'mock',
    zones: mockHeatmaps[playerId] ?? [],
    player: null,
    summary: null,
  };
}
