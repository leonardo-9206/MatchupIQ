<?php
/**
 * Response Helper
 * Provides standardized JSON responses and HTTP status codes for the MatchupIQ API.
 */

class Response
{
    /**
     * Sends a JSON response and terminates script execution.
     *
     * @param mixed $data Payload or associative array
     * @param int $statusCode HTTP status code (default: 200 OK)
     */
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Convenience method for successful operations.
     */
    public static function success(string $message, mixed $data = null, int $statusCode = 200): void
    {
        $payload = ['status' => 'success', 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        self::json($payload, $statusCode);
    }

    /**
     * Convenience method for errors.
     */
    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        $payload = ['status' => 'error', 'message' => $message];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $statusCode);
    }
}
