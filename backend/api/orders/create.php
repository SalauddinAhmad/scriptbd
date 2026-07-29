<?php
/**
 * ScriptBD - Orders API: Create Order (v2 with payment)
 * POST /api/orders/create.php
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $input = getJsonBody();
    $errors = [];

    // Required fields
    $name = trim($input['name'] ?? '');
    if ($name === '') $errors[] = 'নাম আবশ্যক';
    elseif (mb_strlen($name) > 100) $errors[] = 'নাম সর্বোচ্চ ১০০ অক্ষর';

    $phone = trim($input['phone'] ?? '');
    if ($phone === '') $errors[] = 'ফোন নম্বর আবশ্যক';
    elseif (!preg_match('/^\+?[\d\s\-()]{7,20}$/', $phone)) $errors[] = 'বৈধ ফোন নম্বর দিন';

    $email = trim($input['email'] ?? '');

    $plan = trim($input['plan'] ?? '');
    if ($plan === '') $errors[] = 'প্ল্যান সিলেক্ট করুন';

    $topic = trim($input['topic'] ?? '');

    // Payment fields
    $amount = floatval($input['amount'] ?? 0);
    $paymentMethod = trim($input['payment_method'] ?? '');
    $transactionId = trim($input['transaction_id'] ?? '');

    // Determine amount from plan if not provided
    if ($amount <= 0) {
        $planPrices = [
            'youtube-shorts' => 400,
            'facebook-reels' => 500,
            'youtube-full' => 1000,
        ];
        $amount = $planPrices[$plan] ?? 0;
    }

    // Determine payment status
    $paymentStatus = (!empty($transactionId)) ? 'submitted' : 'unpaid';

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
    }

    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        'INSERT INTO orders (name, email, phone, plan, topic, message, amount, payment_method, transaction_id, payment_status, status)
         VALUES (:name, :email, :phone, :plan, :topic, :message, :amount, :payment_method, :transaction_id, :payment_status, :status)'
    );
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':plan' => $plan,
        ':topic' => $topic,
        ':message' => $input['message'] ?? '',
        ':amount' => $amount,
        ':payment_method' => $paymentMethod,
        ':transaction_id' => $transactionId,
        ':payment_status' => $paymentStatus,
        ':status' => 'pending',
    ]);

    $orderId = (int) $pdo->lastInsertId();

    jsonResponse([
        'success' => true,
        'message' => 'অর্ডার সফলভাবে তৈরি হয়েছে! পেমেন্ট ভেরিফাই করে শীঘ্রই ডেলিভারি দেওয়া হবে।',
        'order_id' => $orderId,
        'payment_status' => $paymentStatus,
    ], 201);

} catch (PDOException $e) {
    error_log('Order Create Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'ডাটাবেজ ত্রুটি। পরে চেষ্টা করুন।'], 500);
} catch (Exception $e) {
    error_log('Order Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'সার্ভার ত্রুটি। পরে চেষ্টা করুন।'], 500);
}
