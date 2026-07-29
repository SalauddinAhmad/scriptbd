<?php
/**
 * ScriptBD - Auth API: Admin Login
 *
 * POST /api/auth/login.php
 * Accepts JSON: {username, password}
 * Returns JSON: {success, message, token}  (simple token-based)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
}

try {
    $input = getJsonBody();

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($username === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => 'ইউজারনেম ও পাসওয়ার্ড আবশ্যক (Username and password required)'], 400);
    }

    $pdo = getDBConnection();

    $stmt = $pdo->prepare('SELECT id, username, password FROM admin WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        jsonResponse(['success' => false, 'message' => 'ভুল ইউজারনেম অথবা পাসওয়ার্ড (Invalid username or password)'], 401);
    }

    // Generate a simple token (base64-encoded JSON with expiry)
    $payload = [
        'admin_id'  => (int) $admin['id'],
        'username'  => $admin['username'],
        'expires_at' => time() + 86400, // 24 hours
    ];

    $tokenPayload = json_encode($payload);
    $token = base64_encode($tokenPayload);

    // Also generate a signature for basic verification
    $secretKey = 'scriptbd_secret_signing_key_2026';
    $signature = hash_hmac('sha256', $tokenPayload, $secretKey);

    jsonResponse([
        'success'  => true,
        'message'  => 'লগইন সফল! (Login successful!)',
        'token'    => $token,
        'signature' => $signature,
        'admin'    => [
            'id'       => (int) $admin['id'],
            'username' => $admin['username'],
        ],
    ]);

} catch (PDOException $e) {
    error_log('Auth Login Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error.'], 500);
} catch (Exception $e) {
    error_log('Auth Login Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error.'], 500);
}
