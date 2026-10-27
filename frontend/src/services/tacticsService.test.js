import { describe, it, expect, vi } from 'vitest';
import { getPlayerHeatmap, getPlayerCoverage, getPlayerRouteTree } from './tacticsService.js';
import { mockHeatmaps } from '../mocks/heatmapMock.js';
import { mockCoverage } from '../mocks/coverageMock.js';
import { mockRouteTrees } from '../mocks/routeTreeMock.js';

// Fakes fetch() so it answers like the PHP backend
const respondWith = (status, body) =>
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: status < 400, status, json: async () => body }));

describe('getPlayerHeatmap', () => {
  it('uses the backend data when the API answers with zones', async () => {
    const zones = [{ field_zone: 'middle', attempts: 10, yards_gained: 40, success_rate: 50 }];
    respondWith(200, { status: 'success', data: { player: { player_id: 3 }, zones, summary: { total_attempts: 10 } } });

    const result = await getPlayerHeatmap(3);

    expect(fetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/tactics/heatmap.php?player_id=3'),
      expect.any(Object)
    );
    expect(result.source).toBe('backend');
    expect(result.zones).toEqual(zones);
    expect(result.summary.total_attempts).toBe(10);
  });

  it('falls back to the mock when the backend is offline', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));

    const result = await getPlayerHeatmap(3);

    expect(result.source).toBe('mock');
    expect(result.zones).toEqual(mockHeatmaps[3]);
  });

  it('falls back to the mock when the API returns an error status', async () => {
    respondWith(404, { status: 'error', message: 'Player not found.' });

    const result = await getPlayerHeatmap(4);

    expect(result.source).toBe('mock');
    expect(result.zones).toEqual(mockHeatmaps[4]);
  });

  it('returns an empty list when neither backend nor mock have the player', async () => {
    respondWith(404, { status: 'error', message: 'Player not found.' });

    const result = await getPlayerHeatmap(999);

    expect(result).toMatchObject({ source: 'mock', zones: [] });
  });
});

describe('getPlayerCoverage', () => {
  it('reads data.splits from /api/tactics/coverage.php', async () => {
    const splits = [{ coverage_type: 'man', targets: 1, receptions: 1, catch_rate: 100 }];
    respondWith(200, { status: 'success', data: { player: {}, splits, summary: { preferred_scheme: 'man' } } });

    const result = await getPlayerCoverage(2);

    expect(fetch).toHaveBeenCalledWith(expect.stringContaining('/api/tactics/coverage.php?player_id=2'), expect.any(Object));
    expect(result).toMatchObject({ source: 'backend', splits });
  });

  it('falls back to the coverage mock when the backend is offline', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));

    expect(await getPlayerCoverage(2)).toMatchObject({ source: 'mock', splits: mockCoverage[2] });
  });
});

describe('getPlayerRouteTree', () => {
  it('uses the route mock while the routes endpoint does not exist', async () => {
    respondWith(404, {});

    expect(await getPlayerRouteTree(2)).toMatchObject({ source: 'mock', routes: mockRouteTrees[2] });
  });
});
