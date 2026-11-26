import { test as base } from '@playwright/test';

// Same shape GET /api/tactics/*.php answers (TacticsController + HeatmapEngine)
export const LIVE_HEATMAP = {
  status: 'success',
  data: {
    player: { player_id: 3, name: 'Isiah Pacheco', position: 'RB' },
    zones: [
      { field_zone: 'left_end', attempts: 10, yards_gained: 40, success_rate: 40 },
      { field_zone: 'left_tackle', attempts: 20, yards_gained: 90, success_rate: 50 },
      { field_zone: 'middle', attempts: 99, yards_gained: 420, success_rate: 55 },
      { field_zone: 'right_tackle', attempts: 30, yards_gained: 150, success_rate: 60 },
      { field_zone: 'right_end', attempts: 15, yards_gained: 80, success_rate: 65 },
    ],
    summary: { total_attempts: 174 },
  },
};

/**
 * Every test gets a page where the PHP backend (localhost:8000) is faked:
 * - by default all backend calls fail like an offline server, so props and tactics use their mocks;
 * - `backend.tactics(endpoint, response)` makes one tactics endpoint answer like the live API.
 */
export const test = base.extend({
  // auto: true installs the fake backend even in tests that don't ask for `backend`
  backend: [async ({ page }, use) => {
    const tacticsResponses = new Map();

    await page.route('http://localhost:8000/**', (route) => {
      const url = new URL(route.request().url());
      const endpoint = url.pathname.split('/').pop();
      const fake = url.pathname.startsWith('/api/tactics/') && tacticsResponses.get(endpoint);
      if (fake) {
        return route.fulfill({ status: fake.status, json: fake.body });
      }
      return route.abort('connectionrefused');
    });

    await use({
      tactics: (endpoint, body, status = 200) => tacticsResponses.set(endpoint, { status, body }),
    });
  }, { auto: true }],
});

export { expect } from '@playwright/test';

/** Card of a prop in the "Picks Destacados" grid, found by the athlete's name. */
export function propCard(page, playerName) {
  return page.locator('div.group').filter({ has: page.getByRole('heading', { name: playerName, exact: true }) });
}
