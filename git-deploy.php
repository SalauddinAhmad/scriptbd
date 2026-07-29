<?php
/**
 * ScriptBD Auto-Deploy Webhook (ZIP-based — no shell/git needed)
 * GitHub push → this file downloads latest ZIP → extracts → DONE
 */
$secret = 'script…2026';

// Verify GitHub signature
$hubSig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');
$expectedSig = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!$payload || !hash_equals($expectedSig, $hubSig)) {
    http_response_code(403); die('Invalid signature');
}

$data = json_decode($payload, true);
$ref = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main') die('Skipping '.$ref);

// Download & extract latest from GitHub
$zip = file_get_contents('https://github.com/SalauddinAhmad/scriptbd/archive/main.zip');
if (!$zip || strlen($zip) < 1000) { http_response_code(500); die('Download failed'); }

file_put_contents(__DIR__.'/update.zip', $zip);
$z = new ZipArchive;
if ($z->open(__DIR__.'/update.zip')) {
    $z->extractTo(__DIR__);
    $z->close();
}

// Copy from extracted folder to root
$src = __DIR__.'/scriptbd-main';
if (is_dir($src)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $file) {
        $dest = __DIR__ . '/' . str_replace($src . '/', '', $file);
        if ($file->isDir()) { @mkdir($dest, 0755, true); }
        else { @copy($file, $dest); }
    }
    // Remove temp dir
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    ) as $f) {
        $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
    }
    @rmdir($src);
}
@unlink(__DIR__.'/update.zip');

// Log
file_put_contents(__DIR__.'/deploy-log.txt', date('Y-m-d H:i:s')." — Deployed branch: ".($data['ref']??'unknown')."\n", FILE_APPEND);
echo "OK";
