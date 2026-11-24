// Mock data for the FieldHeatmap component.
// The shape mirrors what GET /api/tactics/heatmap.php is expected to return:
// one row per `player_heatmaps` record (see Docs/DATA_DICTIONARY.md, table 2.10).
// When Joao's endpoint is ready, replace this import with a fetch() in src/services/api.js.

export const mockPlayers = [
  { player_id: 3, name: 'Isiah Pacheco', position: 'RB', team: 'KC' },
  { player_id: 4, name: 'Christian McCaffrey', position: 'RB', team: 'SF' },
];

export const mockHeatmaps = {
  // Same values as backend/database/seed.sql
  3: [
    { heatmap_id: 1, player_id: 3, field_zone: 'left_end', attempts: 24, yards_gained: 132, success_rate: 58.33 },
    { heatmap_id: 2, player_id: 3, field_zone: 'left_tackle', attempts: 42, yards_gained: 215, success_rate: 64.29 },
    { heatmap_id: 3, player_id: 3, field_zone: 'middle', attempts: 68, yards_gained: 310, success_rate: 52.94 },
    { heatmap_id: 4, player_id: 3, field_zone: 'right_tackle', attempts: 38, yards_gained: 198, success_rate: 60.53 },
    { heatmap_id: 5, player_id: 3, field_zone: 'right_end', attempts: 18, yards_gained: 95, success_rate: 50.0 },
  ],
  // Made-up values, only to test switching between players
  4: [
    { heatmap_id: 6, player_id: 4, field_zone: 'left_end', attempts: 31, yards_gained: 204, success_rate: 61.29 },
    { heatmap_id: 7, player_id: 4, field_zone: 'left_tackle', attempts: 36, yards_gained: 171, success_rate: 55.56 },
    { heatmap_id: 8, player_id: 4, field_zone: 'middle', attempts: 52, yards_gained: 228, success_rate: 50.0 },
    { heatmap_id: 9, player_id: 4, field_zone: 'right_tackle', attempts: 47, yards_gained: 266, success_rate: 63.83 },
    { heatmap_id: 10, player_id: 4, field_zone: 'right_end', attempts: 40, yards_gained: 281, success_rate: 67.5 },
  ],
};
