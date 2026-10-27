// Mock data for the CoverageSplits component.
// The shape mirrors GET /api/tactics/coverage.php: one row per `coverage_splits` record
// (see Docs/DATA_DICTIONARY.md, table 2.11).
// Only used as a fallback by src/services/tacticsService.js when the backend is offline.

export const mockCoverage = {
  // Same values as backend/database/seed.sql
  2: [
    { split_id: 1, player_id: 2, coverage_type: 'man', targets: 35, receptions: 26, catch_rate: 74.29 },
    { split_id: 2, player_id: 2, coverage_type: 'zone', targets: 58, receptions: 46, catch_rate: 79.31 },
  ],
  // Made-up values (not in the seed yet) so a second player can be tested
  4: [
    { split_id: 3, player_id: 4, coverage_type: 'man', targets: 29, receptions: 24, catch_rate: 82.76 },
    { split_id: 4, player_id: 4, coverage_type: 'zone', targets: 41, receptions: 30, catch_rate: 73.17 },
  ],
};
