// Starts Playwright for `npm run test:e2e`.
//
// Playwright registers its own ES module loader to transpile TypeScript, and on recent Node
// versions (seen on Node 24) that loader hangs forever before the first test is even listed.
// Our tests are plain JavaScript that Node already understands, so we turn that loader off.
// The variable is inherited by the worker processes that run the tests.
process.env.PW_DISABLE_TS_ESM = '1';

await import('@playwright/test/cli');
