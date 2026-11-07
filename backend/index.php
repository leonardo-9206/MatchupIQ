<?php
/**
 * MatchupIQ API - Cloud Root Gateway
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status'    => 'ok',
    'service'   => 'MatchupIQ API',
    'version'   => '1.0.0',
    'endpoints' => [
        'health'      => '/api/health-check.php',
        'props'       => '/api/props/index.php',
        'tracker'     => '/api/tracker/index.php',
        'auth_login'  => '/api/auth/login.php',
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
