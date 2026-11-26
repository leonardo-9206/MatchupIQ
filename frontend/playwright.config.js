import { defineConfig, devices } from '@playwright/test';

const E2E_PORT = 5199;

/**
 * E2E tests: a real browser opens the dashboard and clicks through the tactical modal.
 * Run with `npm run test:e2e` (starts the Vite dev server by itself). Use that script instead of
 * a bare `npx playwright test`: it goes through e2e/run.js, which avoids a hang on recent Node versions.
 *
 * The PHP backend is NOT needed: every request to it is intercepted in e2e/fixtures.js,
 * so the tests always see the same data and don't depend on the cloud database.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? 'github' : 'list',
  // The dev server compiles each file the first time it is requested, so with several browsers
  // opening the app at once the first render can take longer than the default 5 s
  expect: { timeout: 15_000 },
  use: {
    baseURL: `http://localhost:${E2E_PORT}`,
    trace: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        // Locally we reuse the installed Google Chrome; in CI run `npx playwright install chromium` first
        channel: process.env.CI ? undefined : 'chrome',
      },
    },
  ],
  // Own port + strictPort: if something else is using it, the run fails right away instead of
  // silently testing another app that happens to be running on the usual 5173
  webServer: {
    command: `npm run dev -- --port ${E2E_PORT} --strictPort`,
    url: `http://localhost:${E2E_PORT}`,
    reuseExistingServer: false,
    timeout: 60_000,
  },
});
