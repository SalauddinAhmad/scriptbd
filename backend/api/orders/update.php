<?php
/**
 * ScriptBD - Orders API: Update Order Status
 *
 * PUT /api/orders/update.php
 * Requires: X-API-Key header
 * Accepts JSON: {id, status}
 * Returns JSON: {success, message}
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed. Use PUT.'], 405);
}

// Authentication required
authenticateApiKey();

try {
    $input = getJsonBody();

    // --- Validation ---
    $errors = [];

    $id = (int) ($input['id'] ?? 0);
    if ($id <= 0) {
        $errors[] = 'Valid order ID is required';
    }

    $status = trim($input['status'] ?? '');
    $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses, true)) {
        $errors[] = 'Invalid status. Allowed: ' . implode(', ', $validStatuses);
    }

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
    }

    // --- Update ---
    $pdo = getDBConnection();

    // Check order exists
    $checkStmt = $pdo->prepare('SELECT id FROM orders WHERE id = :id');
    $checkStmt->execute([':id' => $id]);

    if (!$checkStmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
    }

    $stmt = $pdo->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        ':status' => $status,
        ':id'     => $id,
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'অর্ডার স্ট্যাটাস আপডেট হয়েছে! (Order status updated successfully!)',
    ]);

} catch (PDOException $e) {
    error_log('Order Update Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error.'], 500);
} catch (Exception $e) {
    error_log('Order Update Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error.'], 500);
}
