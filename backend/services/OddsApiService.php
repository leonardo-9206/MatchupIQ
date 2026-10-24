<?php
/**
 * OddsApiService
 * Middleware service to consume The Odds API via cURL with local MySQL caching (15 min TTL).
 * Prevents redundant external calls, protects quota, and delivers sub-250ms responses.
 */

require_once __DIR__ . '/../config/Database.php';

class OddsApiService
{
    private PDO $db;
    private string $apiKey;
    private string $baseUrl;
    private int $defaultTtl; // Cache validity duration in seconds (900 = 15 minutes)

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->apiKey = getenv('ODDS_API_KEY') ?: '';
        $this->baseUrl = getenv('ODDS_API_BASE_URL') ?: 'https://api.the-odds-api.com/v4';
        $this->defaultTtl = 900; // 15 minutes as defined in HU-05
    }

    /**
     * Retrieve odds for a given sport and markets, checking local cache first.
     *
     * @param string $sportKey Sport identifier (e.g. 'americanfootball_nfl', 'soccer_mexico_ligamx')
     * @param array $markets Betting markets (e.g. ['h2h', 'spreads', 'totals'])
     * @param bool $forceRefresh Bypass cache and force a new external cURL call
     * @return array Standardized response containing data, source, and cache metadata
     */
    public function getOdds(string $sportKey = 'americanfootball_nfl', array $markets = ['h2h'], bool $forceRefresh = false): array
    {
        $marketsStr = implode(',', $markets);
        $cacheKey = "odds_{$sportKey}_{$marketsStr}";

        // 1. Check local cache if not forcing refresh
        if (!$forceRefresh) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== null) {
                return [
                    'success'    => true,
                    'source'     => 'cache',
                    'cache_key'  => $cacheKey,
                    'fetched_at' => $cached['fetched_at'],
                    'expires_at' => $cached['expires_at'],
                    'ttl_left'   => max(0, strtotime($cached['expires_at']) - time()),
                    'count'      => count($cached['data']),
                    'data'       => $cached['data']
                ];
            }
        }

        // 2. Fetch fresh data from The Odds API via cURL
        $endpoint = "/sports/{$sportKey}/odds/?apiKey={$this->apiKey}&regions=us&markets={$marketsStr}&oddsFormat=decimal";
        $url = $this->baseUrl . $endpoint;

        $apiResponse = $this->executeCurl($url);

        if (!$apiResponse['success']) {
            // Fallback: If external API call fails, check if stale cache exists to maintain uptime
            $stale = $this->getStaleCache($cacheKey);
            if ($stale !== null) {
                return [
                    'success'    => true,
                    'source'     => 'stale_cache_fallback',
                    'warning'    => 'External API failed, returning cached data: ' . $apiResponse['error'],
                    'cache_key'  => $cacheKey,
                    'fetched_at' => $stale['fetched_at'],
                    'data'       => $stale['data']
                ];
            }

            return [
                'success' => false,
                'source'  => 'api_error',
                'error'   => $apiResponse['error'],
                'data'    => []
            ];
        }

        $freshData = $apiResponse['data'];

        // 3. Persist fresh data in MySQL cache table
        $this->saveToCache($cacheKey, $sportKey, $freshData, $this->defaultTtl);

        return [
            'success'            => true,
            'source'             => 'external_api',
            'cache_key'          => $cacheKey,
            'requests_remaining' => $apiResponse['requests_remaining'] ?? null,
            'count'              => count($freshData),
            'data'               => $freshData
        ];
    }

    /**
     * Retrieve active sports list from The Odds API with caching.
     */
    public function getSports(bool $forceRefresh = false): array
    {
        $cacheKey = 'active_sports_catalog';

        if (!$forceRefresh) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== null) {
                return [
                    'success' => true,
                    'source'  => 'cache',
                    'data'    => $cached['data']
                ];
            }
        }

        $url = "{$this->baseUrl}/sports/?apiKey={$this->apiKey}";
        $apiResponse = $this->executeCurl($url);

        if ($apiResponse['success']) {
            // Sports catalog cached for 24 hours (86400 seconds)
            $this->saveToCache($cacheKey, 'catalog', $apiResponse['data'], 86400);
            return [
                'success' => true,
                'source'  => 'external_api',
                'data'    => $apiResponse['data']
            ];
        }

        return $apiResponse;
    }

    /**
     * Check if a valid, unexpired cache record exists in MySQL.
     */
    private function getFromCache(string $cacheKey): ?array
    {
        $sql = "SELECT `response_data`, `fetched_at`, `expires_at` 
                FROM `odds_cache` 
                WHERE `cache_key` = :cache_key 
                  AND `expires_at` > NOW() 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cache_key' => $cacheKey]);
        $row = $stmt->fetch();

        if ($row) {
            $decoded = json_decode($row['response_data'], true);
            if (is_array($decoded)) {
                return [
                    'data'       => $decoded,
                    'fetched_at' => $row['fetched_at'],
                    'expires_at' => $row['expires_at']
                ];
            }
        }

        return null;
    }

    /**
     * Fetch stale cache if available (for error resilience / network failure).
     */
    private function getStaleCache(string $cacheKey): ?array
    {
        $sql = "SELECT `response_data`, `fetched_at` 
                FROM `odds_cache` 
                WHERE `cache_key` = :cache_key 
                ORDER BY `fetched_at` DESC 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cache_key' => $cacheKey]);
        $row = $stmt->fetch();

        if ($row) {
            $decoded = json_decode($row['response_data'], true);
            if (is_array($decoded)) {
                return [
                    'data'       => $decoded,
                    'fetched_at' => $row['fetched_at']
                ];
            }
        }

        return null;
    }

    /**
     * Store or update API response in MySQL cache with calculated expiration timestamp.
     */
    private function saveToCache(string $cacheKey, string $sportKey, array $data, int $ttlSeconds): void
    {
        $encoded = json_encode($data);

        $sql = "INSERT INTO `odds_cache` (`cache_key`, `sport_key`, `response_data`, `fetched_at`, `expires_at`)
                VALUES (:cache_key, :sport_key, :response_data, NOW(), DATE_ADD(NOW(), INTERVAL :ttl SECOND))
                ON DUPLICATE KEY UPDATE 
                    `response_data` = VALUES(`response_data`),
                    `fetched_at`    = VALUES(`fetched_at`),
                    `expires_at`    = VALUES(`expires_at`)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cache_key', $cacheKey, PDO::PARAM_STR);
        $stmt->bindValue(':sport_key', $sportKey, PDO::PARAM_STR);
        $stmt->bindValue(':response_data', $encoded, PDO::PARAM_STR);
        $stmt->bindValue(':ttl', $ttlSeconds, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Perform an HTTP GET request using cURL with error handling and quota tracking.
     */
    private function executeCurl(string $url): array
    {
        $ch = curl_init();

        $responseHeaders = [];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: MatchupIQ-Proxy/1.0'
            ],
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            }
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            return [
                'success' => false,
                'error'   => "cURL network error: {$curlError}"
            ];
        }

        if ($httpCode !== 200) {
            $msg = "External API responded with HTTP {$httpCode}";
            if ($httpCode === 401) {
                $msg = "Invalid or missing The Odds API key (HTTP 401).";
            } elseif ($httpCode === 429) {
                $msg = "The Odds API monthly quota exceeded (HTTP 429).";
            }
            return [
                'success'   => false,
                'http_code' => $httpCode,
                'error'     => $msg
            ];
        }

        $decoded = json_decode($body, true);
        if ($decoded === null) {
            return [
                'success' => false,
                'error'   => 'Failed to parse JSON response from external API.'
            ];
        }

        return [
            'success'            => true,
            'data'               => $decoded,
            'requests_remaining' => $responseHeaders['x-requests-remaining'] ?? null
        ];
    }

    /**
     * Housekeeping: Purge expired cache rows older than specified minutes.
     */
    public function purgeExpiredCache(int $olderThanMinutes = 60): int
    {
        $sql = "DELETE FROM `odds_cache` WHERE `expires_at` < DATE_SUB(NOW(), INTERVAL :mins MINUTE)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':mins', $olderThanMinutes, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
