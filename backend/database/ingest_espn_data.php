<?php
/**
 * CLI Script: Ingest Real Historical Game Logs & Sync Rosters from ESPN API
 * Usage:
 *   php backend/database/ingest_espn_data.php              (Syncs teams, preset game logs)
 *   php backend/database/ingest_espn_data.php --rosters    (Syncs all 32 NFL teams & full player rosters)
 *   php backend/database/ingest_espn_data.php --all        (Syncs teams, full rosters & game logs)
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/EspnApiService.php';

echo "====================================================\n";
echo " MatchupIQ — ESPN API Live Data Ingestion Runner\n";
echo "====================================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$options = getopt('', ['rosters', 'all']);
$syncRosters = isset($options['rosters']) || isset($options['all']);

try {
    $espnService = new EspnApiService();

    // 1. Sync 32 NFL teams
    echo "[*] Step 1: Synchronizing 32 NFL franchises with ESPN API...\n";
    $teamsMap = $espnService->syncNflTeams();
    echo "    [+] NFL Franchises Verified: " . count($teamsMap) . " teams active in database.\n\n";

    // 2. Sync Rosters if requested
    if ($syncRosters) {
        echo "[*] Step 2: Synchronizing offensive player rosters from ESPN 32-team rosters...\n";
        // Pre-cataloged offensive list from Sprint 3
        $defaultSkillPlayers = [
            'Josh Allen', 'James Cook III', 'Ray Davis', 'DJ Moore', 'Khalil Shakir', 'Keon Coleman', 'Dalton Kincaid', 'Dawson Knox',
            'Malik Willis', 'Tua Tagovailoa', "De'Von Achane", 'Ollie Gordon II', 'Jaylen Wright', 'Malik Washington', 'Chris Bell', 'Greg Dulcich', 'Julian Hill',
            'Drake Maye', 'Rhamondre Stevenson', 'TreVeyon Henderson', 'A.J. Brown', 'DeMario Douglas', 'Hunter Henry', 'Austin Hooper', 'Eli Raridon',
            'Geno Smith', 'Breece Hall', 'Braelon Allen', 'Garrett Wilson', 'Adonai Mitchell', 'Mason Taylor', 'Kenyon Sadiq', 'Jeremy Ruckert',
            'Lamar Jackson', 'Derrick Henry', 'Justice Hill', 'Zay Flowers', 'Rashod Bateman', 'Mark Andrews', 'Josh Cuevas',
            'Joe Burrow', 'Chase Brown', 'Samaje Perine', "Ja'Marr Chase", 'Tee Higgins', 'Mike Gesicki', 'Drew Sample',
            'Shedeur Sanders', 'Deshaun Watson', 'Quinshon Judkins', 'Dylan Sampson', 'Denzel Boston', 'Jerry Jeudy', 'KC Concepcion', 'Harold Fannin Jr.', 'Blake Whiteheart', 'David Njoku',
            'Aaron Rodgers', 'Jaylen Warren', 'Rico Dowdle', 'DK Metcalf', 'Germie Bernard', 'Ben Skowronek', 'Pat Freiermuth', 'Darnell Washington',
            'C.J. Stroud', 'David Montgomery', 'Woody Marks', 'Dare Ogunbowale', 'Nico Collins', 'Tank Dell', 'Dalton Schultz', 'Brevin Jordan',
            'Daniel Jones', 'Anthony Richardson', 'Jonathan Taylor', 'DJ Giddens', 'Seth McGowan', 'Josh Downs', 'Keenan Allen', 'Michael Pittman Jr.', 'Tyler Warren', 'Mo Alie-Cox',
            'Trevor Lawrence', 'Travis Etienne Jr.', 'Bhayshul Tuten', 'Tank Bigsby', 'Brian Thomas Jr.', 'Jakobi Meyers', 'Parker Washington', 'Brenton Strange', 'Luke Farrell',
            'Cam Ward', 'Tony Pollard', 'Tyjae Spears', 'Calvin Ridley', 'Carnell Tate', "Wan'Dale Robinson", 'Gunnar Helm', 'Josh Whyle',
            'Bo Nix', 'J.K. Dobbins', 'Jonah Coleman', 'Audric Estime', 'Jaylen Waddle', 'Courtland Sutton', 'Evan Engram', 'Lucas Krull',
            'Patrick Mahomes', 'Kenneth Walker III', 'Isiah Pacheco', 'Emmett Johnson', 'Rashee Rice', 'Xavier Worthy', 'Travis Kelce', 'Noah Gray',
            'Kirk Cousins', 'Fernando Mendoza', 'Ashton Jeanty', 'Mike Washington Jr.', 'Tre Tucker', 'Jack Bech', 'Brock Bowers', 'Michael Mayer',
            'Justin Herbert', 'Omarion Hampton', 'Keaton Mitchell', 'Kimani Vidal', 'Ladd McConkey', 'Quentin Johnston', 'Will Dissly', 'Charlie Kolar',
            'Dak Prescott', 'Javonte Williams', 'Tyler Goodson', 'CeeDee Lamb', 'George Pickens', 'Jake Ferguson', 'Brevyn Spann-Ford', 'Luke Schoonmaker',
            'J.J. McCarthy', 'Jaxson Dart', 'Cam Skattebo', 'Devin Singletary', 'Tyrone Tracy Jr.', 'Malik Nabers', 'Malachi Fields', 'Isaiah Likely', 'Theo Johnson',
            'Jalen Hurts', 'Tanner McKee', 'Saquon Barkley', 'Jaydon Blue', 'Will Shipley', 'DeVonta Smith', 'Dontayvion Wicks', 'Dallas Goedert', 'Grant Calcaterra', 'Johnny Mundt',
            'Jayden Daniels', 'Jacory Croskey-Merritt', 'Rachaad White', 'Terry McLaurin', 'Stefon Diggs', 'Luke McCaffrey', 'Chig Okonkwo', 'Ben Sinnott',
            'Caleb Williams', "D'Andre Swift", 'Kyle Monangai', 'Rome Odunze', 'Luther Burden III', 'Colston Loveland', 'Cole Kmet', 'Sam Roush',
            'Jared Goff', 'Jahmyr Gibbs', 'Sione Vaki', 'Amon-Ra St. Brown', 'Jameson Williams', 'Sam LaPorta', 'Brock Wright',
            'Jordan Love', 'Josh Jacobs', 'MarShawn Lloyd', 'Christian Watson', 'Matthew Golden', 'Jayden Reed', 'Tucker Kraft', 'Jonnu Smith', 'Luke Musgrave',
            'Kyler Murray', 'Aaron Jones Sr.', 'Jordan Mason', 'Justin Jefferson', 'Jordan Addison', 'T.J. Hockenson', 'Josh Oliver',
            'Michael Penix Jr.', 'Bijan Robinson', 'Brian Robinson Jr.', 'Drake London', 'Jahan Dotson', 'Darnell Mooney', 'Kyle Pitts', 'Charlie Woerner',
            'Bryce Young', 'Chuba Hubbard', 'Trevor Etienne', 'Jonathon Brooks', 'Tetairoa McMillan', 'Jalen Coker', 'Xavier Legette', 'Tommy Tremble', "Ja'Tavion Sanders", 'Darren Waller',
            'Tyler Shough', 'Spencer Rattler', 'Alvin Kamara', 'Kendre Miller', 'Chris Olave', 'Devaughn Vele', 'Juwan Johnson', 'Noah Fant',
            'Baker Mayfield', 'Bucky Irving', 'Kenneth Gainwell', 'Sean Tucker', 'Emeka Egbuka', 'Chris Godwin Jr.', 'Cade Otton', 'Payne Durham',
            'Gardner Minshew II', 'Jacoby Brissett', 'Jeremiyah Love', 'Tyler Allgeier', 'Trey Benson', 'Marvin Harrison Jr.', 'Michael Wilson', 'Trey McBride', 'Tip Reiman',
            'Matthew Stafford', 'Kyren Williams', 'Blake Corum', 'Puka Nacua', 'Davante Adams', 'Jordan Whittington', 'Tyler Higbee', 'Colby Parkinson',
            'Brock Purdy', 'Christian McCaffrey', 'Isaac Guerendo', 'Kaelon Black', 'Mike Evans', 'Deebo Samuel Sr.', 'Brandon Aiyuk', 'Jauan Jennings', 'George Kittle', 'Eric Saubert',
            'Sam Darnold', 'Zach Charbonnet', 'Jadarian Price', 'Jaxon Smith-Njigba', 'Rashid Shaheed', 'Cooper Kupp', 'AJ Barner', 'Pharaoh Brown'
        ];

        $rosterResult = $espnService->syncPlayerList($defaultSkillPlayers);
        echo "    [+] Rosters Synced:\n";
        echo "        - Total Processed: " . $rosterResult['total_names'] . "\n";
        echo "        - Resolved via ESPN: " . $rosterResult['resolved'] . " (Fallback searches: " . $rosterResult['fallback_used'] . ")\n";
        echo "        - Newly Inserted: " . $rosterResult['inserted'] . "\n";
        echo "        - Updated: " . $rosterResult['updated'] . "\n\n";
    }

    // 3. Sync recent game logs (Cleaning stale seed history before ingestion)
    echo "[*] Step 3: Fetching empirical 5-game performance logs from ESPN...\n";
    echo "    [*] Wiping obsolete seed history (DELETE FROM player_game_logs WHERE player_id = :id) to prevent duplicate records...\n";
    $result = $espnService->syncAllPresetAthletes(5);

    echo "\n[+] Game Logs Ingestion Finished Successfully!\n";
    echo "  - Athletes Processed:   " . $result['athletes_processed'] . "\n";
    echo "  - Athletes Synced:      " . $result['athletes_synced'] . "\n";
    echo "  - Total Logs Ingested:  " . $result['total_logs_ingested'] . "\n\n";

    echo "--- Recent Ingestions ---\n";
    foreach ($result['details'] as $detail) {
        echo sprintf(
            "  [%s] %-22s (%s - %s) -> %d games ingested\n",
            $detail['source'],
            $detail['player'],
            $detail['position'],
            $detail['team'],
            $detail['logs_count']
        );
    }

    echo "\n[+] Done! Database contains full 32 NFL teams, players, and live ESPN game logs.\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n[!] Ingestion Error: " . $e->getMessage() . "\n";
    exit(1);
}
