<?php
/**
 * ScriptBD Auto-Deploy via GitHub Webhook
 * Usage: GitHub Actions calls this after build
 */
define('SECRET', 'scriptbd-deploy-2026-secure');
define('TARGET_DIR', '/home/scriptbd_ag/public_html');
define('TEMP_DIR', '/tmp/scriptbd-deploy');

// Verify secret
$secret = $_GET['secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '';
if ($secret !== SECRET) {
    http_response_code(403);
    die('Unauthorized');
}

// Get version info
$version = $_GET['version'] ?? 'latest';
$run_id = $_GET['run_id'] ?? '';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka');

$log = [];
$log[] = "[" . date('Y-m-d H:i:s') . "] Deploy started: $version, run=$run_id";

// Clean temp
if (is_dir(TEMP_DIR)) {
    exec("rm -rf " . escapeshellarg(TEMP_DIR));
}
mkdir(TEMP_DIR, 0755, true);

// Download latest code from GitHub
$zip_url = "https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip";
$zip_file = TEMP_DIR . "/deploy.zip";

$log[] = "Downloading: $zip_url";
$zip_data = file_get_contents($zip_url);
if (!$zip_data) {
    $log[] = "ERROR: Failed to download";
    echo json_encode(['ok' => false, 'log' => $log]);
    exit(1);
}
file_put_contents($zip_file, $zip_data);
$log[] = "Downloaded: " . round(strlen($zip_data) / 1024) . " KB";

// Extract
$zip = new ZipArchive;
if ($zip->open($zip_file) !== TRUE) {
    $log[] = "ERROR: Cannot open ZIP";
    echo json_encode(['ok' => false, 'log' => $log]);
    exit(1);
}

$extract_dir = TEMP_DIR . "/extract";
$zip->extractTo($extract_dir);
$zip->close();
$log[] = "Extracted ZIP";

// Find the extracted folder (scriptbd-main)
$folders = scandir($extract_dir);
$src = '';
foreach ($folders as $f) {
    if ($f != '.' && $f != '..' && is_dir("$extract_dir/$f")) {
        $src = "$extract_dir/$f";
        break;
    }
}

if (!$src) {
    echo json_encode(['ok' => false, 'log' => $log, 'error' => 'No source folder']);
    exit(1);
}

// Copy frontend dist to public_html
$dist_src = "$src/frontend/dist";
if (is_dir($dist_src)) {
    $log[] = "Copying frontend/dist -> " . TARGET_DIR;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dist_src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $copied = 0;
    foreach ($files as $file) {
        $dest = TARGET_DIR . '/' . $files->getSubPathName();
        if ($file->isDir()) {
            @mkdir($dest, 0755, true);
        } else {
            @copy($file, $dest);
            $copied++;
        }
    }
    $log[] = "Copied $copied files";
}

// Copy backend files too (exclude deploy.php)
$backend_src = "$src/backend";
if (is_dir($backend_src)) {
    $log[] = "Copying backend files";
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backend_src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    $bcopied = 0;
    foreach ($files as $file) {
        $dest = TARGET_DIR . '/backend/' . $files->getSubPathName();
        if ($file->isDir()) {
            @mkdir($dest, 0755, true);
        } else {
            @copy($file, $dest);
            $bcopied++;
        }
    }
    $log[] = "Backend: $bcopied files";
}

// Also copy index.html from root
$index_src = "$src/index.html";
if (file_exists($index_src)) {
    copy($index_src, TARGET_DIR . "/index.html");
    $log[] = "Copied index.html";
}

// Cleanup
exec("rm -rf " . escapeshellarg(TEMP_DIR));
$log[] = "Cleanup done";

echo json_encode([
    'ok' => true,
    'time' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['HTTP_HOST'] ?? gethostname(),
    'log' => $log
]);
