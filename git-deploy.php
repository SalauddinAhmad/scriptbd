<?php
/**
 * GitHub Auto-Deploy Webhook
 * Place this on the server at: public_html/git-deploy.php
 * Set GitHub webhook to: https://scriptbd.com/git-deploy.php
 * 
 * When you git push, GitHub calls this URL which auto-pulls and deploys
 */

$secret = 'scriptbd_webhook_secret_2026';

// Verify GitHub signature
$hubSig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');
$expectedSig = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expectedSig, $hubSig)) {
    http_response_code(403);
    die('Invalid signature');
}

$data = json_decode($payload, true);
$branch = $data['ref'] ?? '';
if ($branch !== 'refs/heads/main' && $branch !== 'refs/heads/master') {
    die('Ignored branch: ' . $branch);
}

// Deploy!
$log = "=== Auto Deploy " . date('Y-m-d H:i:s') . " ===\n";
$log .= shell_exec('cd /home/scriptbd/public_html && git pull origin main 2>&1') . "\n";

// Rebuild frontend if changed
if (strpos($log, 'frontend/') !== false) {
    $log .= shell_exec('cd /home/scriptbd/public_html/frontend && npm ci --production 2>&1 && npm run build 2>&1') . "\n";
}

// Update index.html with new asset names
$log .= shell_exec('cd /home/scriptbd/public_html && ls -la frontend/dist/assets/ 2>&1') . "\n";

file_put_contents(__DIR__ . '/deploy-log.txt', $log, FILE_APPEND);
echo "Deployed\n";
