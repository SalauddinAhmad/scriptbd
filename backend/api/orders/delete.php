<?php
/**
 * ScriptBD - Orders API: Delete Order
 *
 * DELETE /api/orders/delete.php
 * Requires: X-API-Key header
 * Accepts JSON: {id}  OR query param: ?id=123
 * Returns JSON: {success, message}
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed. Use DELETE.'], 405);
}

// Authentication required
authenticateApiKey();

try {
    // Try JSON body first, then query parameter
    $id = 0;
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $body = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($body['id'])) {
            $id = (int) $body['id'];
        }
    }
    if ($id <= 0) {
        $id = (int) ($_GET['id'] ?? 0);
    }

    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Valid order ID is required'], 400);
    }

    // --- Delete ---
    $pdo = getDBConnection();

    // Check order exists
    $checkStmt = $pdo->prepare('SELECT id FROM orders WHERE id = :id');
    $checkStmt->execute([':id' => $id]);

    if (!$checkStmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
    }

    $stmt = $pdo->prepare('DELETE FROM orders WHERE id = :id');
    $stmt->execute([':id' => $id]);

    jsonResponse([
        'success' => true,
        'message' => 'অর্ডার ডিলিট হয়েছে! (Order deleted successfully!)',
    ]);

} catch (PDOException $e) {
    error_log('Order Delete Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error.'], 500);
} catch (Exception $e) {
    error_log('Order Delete Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error.'], 500);
}
