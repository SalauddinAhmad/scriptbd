<?php
/**
 * ScriptBD Auto-Deploy — Downloads from GitHub ZIP
 */
define('SECRET', 'script…cure');
define('TARGET', '/home/scriptbd_ag/public_html');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka');

$secret = $_GET['secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '';
if ($secret !== SECRET) { http_response_code(403); die('{"ok":false,"error":"unauthorized"}'); }

$log = ["[" . date('H:i:s') . "] Deploy start"];
$tmp = "/tmp/sbd-" . time();
mkdir($tmp, 0755, true);

// Download ZIP
$zip = $tmp . "/repo.zip";
$data = file_get_contents("https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip");
if (!$data) { echo json_encode(['ok'=>false,'log'=>['Download failed']]); exit(1); }
file_put_contents($zip, $data);
$log[] = "Downloaded " . round(strlen($data)/1024) . "KB";

// Extract
$za = new ZipArchive;
$za->open($zip);
$za->extractTo($tmp);
$za->close();

// Find extracted dir
$src = '';
foreach (scandir($tmp) as $d) {
    if ($d[0] !== '.' && is_dir("$tmp/$d")) { $src = "$tmp/$d"; break; }
}

// Deploy function
function deployDir($src, $target, &$log, $label) {
    if (!is_dir($src)) { $log[] = "$label: not found"; return 0; }
    $cnt = 0;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $f) {
        $rel = str_replace($src . '/', '', $f->getPathname());
        $dest = rtrim($target, '/') . '/' . $rel;
        if ($f->isDir()) { @mkdir($dest, 0755, true); }
        else { @copy($f->getPathname(), $dest); $cnt++; }
    }
    $log[] = "$label: $cnt files";
    return $cnt;
}

// Deploy frontend dist
$total = deployDir("$src/frontend/dist", TARGET, $log, "Frontend");

// Deploy backend
$total += deployDir("$src/backend", TARGET . "/backend", $log, "Backend");

// Deploy root index.html
foreach (glob("$src/*.html") as $f) {
    $name = basename($f);
    @copy($f, TARGET . "/$name");
    $log[] = "Copied $name";
}

// Cleanup
exec("rm -rf $tmp");
$log[] = "Done! $total total files";

echo json_encode(['ok'=>true, 'files'=>$total, 'log'=>$log], JSON_UNESCAPED_UNICODE);
