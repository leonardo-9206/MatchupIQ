// Mock data for the RouteTree component.
//
// There is no routes table in Docs/DATA_DICTIONARY.md yet. This is the PROPOSED shape for a future
// `player_routes` table and GET /api/tactics/routes.php endpoint (to agree on with Joao and Rodrigo):
//
//   route_id     INT PK
//   player_id    INT FK -> players(player_id)
//   route_type   ENUM('flat','slant','comeback','curl','out','dig','corner','post','go')
//   targets      INT          times the player was targeted on that route
//   receptions   INT          completed catches
//   yards_gained INT          total receiving yards
//   catch_rate   DECIMAL(5,2) receptions / targets * 100
//
// All values below are made up.

export const mockRouteTrees = {
  // Travis Kelce
  2: [
    { route_id: 1, player_id: 2, route_type: 'flat', targets: 9, receptions: 8, yards_gained: 52, catch_rate: 88.89 },
    { route_id: 2, player_id: 2, route_type: 'slant', targets: 11, receptions: 8, yards_gained: 87, catch_rate: 72.73 },
    { route_id: 3, player_id: 2, route_type: 'comeback', targets: 4, receptions: 3, yards_gained: 38, catch_rate: 75.0 },
    { route_id: 4, player_id: 2, route_type: 'curl', targets: 16, receptions: 13, yards_gained: 141, catch_rate: 81.25 },
    { route_id: 5, player_id: 2, route_type: 'out', targets: 10, receptions: 7, yards_gained: 76, catch_rate: 70.0 },
    { route_id: 6, player_id: 2, route_type: 'dig', targets: 14, receptions: 11, yards_gained: 158, catch_rate: 78.57 },
    { route_id: 7, player_id: 2, route_type: 'corner', targets: 6, receptions: 3, yards_gained: 61, catch_rate: 50.0 },
    { route_id: 8, player_id: 2, route_type: 'post', targets: 8, receptions: 5, yards_gained: 109, catch_rate: 62.5 },
    { route_id: 9, player_id: 2, route_type: 'go', targets: 5, receptions: 2, yards_gained: 58, catch_rate: 40.0 },
  ],
  // Christian McCaffrey (RB, so mostly short routes)
  4: [
    { route_id: 10, player_id: 4, route_type: 'flat', targets: 22, receptions: 19, yards_gained: 148, catch_rate: 86.36 },
    { route_id: 11, player_id: 4, route_type: 'slant', targets: 9, receptions: 7, yards_gained: 63, catch_rate: 77.78 },
    { route_id: 12, player_id: 4, route_type: 'curl', targets: 6, receptions: 5, yards_gained: 41, catch_rate: 83.33 },
    { route_id: 13, player_id: 4, route_type: 'out', targets: 8, receptions: 6, yards_gained: 44, catch_rate: 75.0 },
    { route_id: 14, player_id: 4, route_type: 'dig', targets: 4, receptions: 3, yards_gained: 37, catch_rate: 75.0 },
    { route_id: 15, player_id: 4, route_type: 'go', targets: 2, receptions: 1, yards_gained: 29, catch_rate: 50.0 },
  ],
};
