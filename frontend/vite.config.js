import { defineConfig } from 'vite';
import { configDefaults } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  resolve: {
    preserveSymlinks: true,
  },
  server: {
    host: true,
    port: 5173,
    fs: {
      strict: false,
    },
  },
  // Vitest: runs the component tests in a simulated browser (jsdom)
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: './src/test/setup.js',
    // e2e/ holds the Playwright tests, which run in a real browser with `npm run test:e2e`
    exclude: [...configDefaults.exclude, 'e2e/**'],
  },
});
