<?php
/**
 * ScriptBD - Payment Settings API: Get active payment numbers
 * GET /api/payments/settings.php
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $pdo = getDBConnection();

    // Get payment methods
    $stmt = $pdo->query('SELECT method, account_type, account_number, account_name FROM payment_settings WHERE is_active = 1');
    $methods = $stmt->fetchAll();

    // Get instructions
    $stmt2 = $pdo->query("SELECT value FROM settings WHERE key_name = 'payment_instructions_bn'");
    $instructions = $stmt2->fetchColumn();

    jsonResponse([
        'success' => true,
        'data' => [
            'methods' => $methods,
            'instructions' => $instructions ?: '১. নিচের নম্বরে Send Money করুন\n২. TrxID কপি করে জমা দিন',
        ],
    ]);

} catch (PDOException $e) {
    error_log('Payment Settings Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error'], 500);
}
