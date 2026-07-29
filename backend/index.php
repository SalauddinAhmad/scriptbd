<?php
/**
 * ScriptBD - Health Check Endpoint
 *
 * Returns server status and basic info.
 * Access at: /
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config/database.php';

$response = [
    'success'   => true,
    'message'   => 'স্ক্রিপ্টবিডি API সার্ভার চলছে! (ScriptBD API server is running!)',
    'version'   => '1.0.0',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
];

// Optional DB connectivity check
if (isset($_GET['db_check']) && $_GET['db_check'] === '1') {
    try {
        $pdo = getDBConnection();
        $pdo->query('SELECT 1');
        $response['database'] = 'connected';
    } catch (Exception $e) {
        $response['database'] = 'error: ' . $e->getMessage();
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
