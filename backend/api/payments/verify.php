<?php
/**
 * ScriptBD - Payment API: Verify Transaction
 * POST /api/payments/verify.php
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $input = getJsonBody();

    $orderId = (int) ($input['order_id'] ?? 0);
    $transactionId = trim($input['transaction_id'] ?? '');
    $paymentMethod = trim($input['payment_method'] ?? '');

    if ($orderId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid order ID'], 400);
    }

    if ($transactionId === '') {
        jsonResponse(['success' => false, 'message' => 'ট্রানজেকশন আইডি আবশ্যক'], 400);
    }

    $validMethods = ['bkash', 'nagad', 'rocket'];
    if (!in_array($paymentMethod, $validMethods)) {
        jsonResponse(['success' => false, 'message' => 'Invalid payment method'], 400);
    }

    $pdo = getDBConnection();

    // Check order exists
    $stmt = $pdo->prepare('SELECT id, payment_status, amount FROM orders WHERE id = :id');
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
    }

    // Update with transaction ID (admin will verify manually)
    $stmt = $pdo->prepare(
        'UPDATE orders SET payment_method = :method, transaction_id = :trxid, payment_status = :status, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        ':method' => $paymentMethod,
        ':trxid' => $transactionId,
        ':status' => 'submitted',
        ':id' => $orderId,
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'ট্রানজেকশন আইডি জমা হয়েছে! আমরা ভেরিফাই করে শীঘ্রই কনফার্ম করবো।',
        'order_id' => $orderId,
        'payment_status' => 'submitted',
    ]);

} catch (PDOException $e) {
    error_log('Payment Verify Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error'], 500);
} catch (Exception $e) {
    error_log('Payment Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}
