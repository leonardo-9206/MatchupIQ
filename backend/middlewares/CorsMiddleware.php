<?php
/**
 * CorsMiddleware
 * Handles Cross-Origin Resource Sharing (CORS) headers and preflight OPTIONS requests.
 * Allows the React frontend (running e.g. on localhost:5173 or 3000) to communicate with this PHP API.
 */

class CorsMiddleware
{
    /**
     * Allowed origins for CORS.
     */
    private static array $allowedOrigins = [
        'http://localhost:5173', // Vite React dev server
        'http://localhost:3000', // Alternative React port
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000'
    ];

    /**
     * Applies CORS headers and handles preflight OPTIONS requests.
     */
    public static function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // If origin matches allowed list or in development mode, allow it
        if (in_array($origin, self::$allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } elseif (empty($origin)) {
            // Direct script access or same-origin
            header("Access-Control-Allow-Origin: *");
        } else {
            // For development flexibility, allow localhost on any port
            if (preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
                header("Access-Control-Allow-Origin: {$origin}");
            }
        }

        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');

        // Handle preflight OPTIONS request
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
