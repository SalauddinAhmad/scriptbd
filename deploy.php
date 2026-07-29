<?php
/**
 * ScriptBD Auto-Deploy
 * GitHub Actions POSTs a tar.gz → deploy.php extracts to public_html
 */
define('SECRET', 'scriptbd-deploy-2026-secure');
define('TARGET_DIR', '/home/scriptbd_ag/public_html');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka');

// Verify
$secret = $_GET['secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '';
if ($secret !== SECRET) { http_response_code(403); die('{"ok":false,"error":"unauthorized"}'); }

$log = ["[" . date('Y-m-d H:i:s') . "] Deploy started"];

// Get uploaded file
if (!isset($_FILES['package']) || $_FILES['package']['error'] !== UPLOAD_ERR_OK) {
    $log[] = "ERROR: No package uploaded (error=" . ($_FILES['package']['error']??'none').")";
    echo json_encode(['ok'=>false,'log'=>$log,'files'=>array_keys($_FILES)]);
    exit(1);
}

$tmp = $_FILES['package']['tmp_name'];
$size = filesize($tmp);
$log[] = "Package: " . round($size/1024) . " KB";

// Copy to /tmp for extraction
$tmp_dir = "/tmp/scriptbd-deploy-" . time();
mkdir($tmp_dir, 0755, true);
$tgz_file = $tmp_dir . "/deploy.tar.gz";
move_uploaded_file($tmp, $tgz_file);
$log[] = "Saved to " . $tgz_file;

// Extract
$phar = new PharData($tgz_file);
$phar->extractTo($tmp_dir);
$log[] = "Extracted";

// Copy files to target
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
$skip_prefixes = ['deploy.tar.gz'];
foreach ($iter as $item) {
    $rel = str_replace($tmp_dir . '/', '', $item->getPathname());
    
    // Skip the package itself
    $skip = false;
    foreach ($skip_prefixes as $pf) {
        if (strpos($rel, $pf) === 0) { $skip = true; break; }
    }
    if ($skip) continue;
    
    $dest = (strpos($rel, 'backend/') === 0) 
        ? TARGET_DIR . '/' . $rel 
        : TARGET_DIR . '/' . $rel;
    
    if ($item->isDir()) {
        @mkdir($dest, 0755, true);
    } else {
        @copy($item->getPathname(), $dest);
        $count++;
    }
}
$log[] = "Copied $count files";

// Also copy index.html from root if it exists in extracted content
foreach (['index.html'] as $root_file) {
    $src = $tmp_dir . '/' . $root_file;
    if (file_exists($src)) {
        copy($src, TARGET_DIR . '/' . $root_file);
        $log[] = "Copied $root_file";
    }
}

// Cleanup
exec("rm -rf $tmp_dir");
$log[] = "Done!";

echo json_encode(['ok'=>true,'count'=>$count,'log'=>$log,'time'=>date('Y-m-d H:i:s')]);
