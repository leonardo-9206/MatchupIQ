<?php
/**
 * AuthMiddleware
 * Manages secure PHP sessions and route access protection.
 * Follows OWASP recommendations for session cookie security.
 */

require_once __DIR__ . '/../config/Response.php';

class AuthMiddleware
{
    /**
     * Initializes the PHP session with strict cookie security parameters.
     */
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure cookie attributes before session_start()
            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            session_set_cookie_params([
                'lifetime' => 86400 * 7, // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,      // Mitigate XSS attacks by preventing JS cookie access
                'samesite' => 'Lax'      // Mitigate CSRF attacks
            ]);

            session_start();
        }
    }

    /**
     * Checks if a user is currently logged in.
     */
    public static function isAuthenticated(): bool
    {
        self::initSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Protects an endpoint, blocking unauthenticated requests with 401 Unauthorized.
     */
    public static function requireAuth(): array
    {
        if (!self::isAuthenticated()) {
            Response::error('Unauthorized. Please log in to continue.', 401);
        }

        return self::getCurrentUser();
    }

    /**
     * Protects an endpoint by checking if the user has the required role (e.g. 'admin').
     * Blocks unauthorized roles with 403 Forbidden.
     */
    public static function requireRole(string $requiredRole): array
    {
        $user = self::requireAuth();

        if (($user['role_name'] ?? '') !== $requiredRole) {
            Response::error('Forbidden. Insufficient permissions for this resource.', 403);
        }

        return $user;
    }

    /**
     * Returns the authenticated user session information.
     */
    public static function getCurrentUser(): ?array
    {
        self::initSession();

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'user_id' => (int) $_SESSION['user_id'],
            'role_id' => (int) ($_SESSION['role_id'] ?? 2),
            'role_name' => (string) ($_SESSION['role_name'] ?? 'user'),
            'full_name' => (string) ($_SESSION['full_name'] ?? ''),
            'email' => (string) ($_SESSION['email'] ?? '')
        ];
    }
}
