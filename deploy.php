<?php
define('SECRET', 'sbd2026deploy');
define('TARGET', '/home/scriptbd_ag/public_html');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka');

$secret = $_GET['secret'] ?? ($_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '');
if ($secret !== SECRET) { http_response_code(403); die('{"ok":false}'); }

$log = ["[" . date('H:i:s') . "] NUCLEAR DEPLOY"];

// NUCLEAR: wipe all public_html contents EXCEPT backend/ and cgi-bin
$log[] = "Wiping old files...";
$wipe = 0;
foreach (new DirectoryIterator(TARGET) as $f) {
    if ($f->isDot()) continue;
    $name = $f->getFilename();
    // Keep only: cgi-bin, deploy.php itself, and .well-known
    if (in_array($name, ['cgi-bin', 'deploy.php', '.well-known'])) continue;
    
    $path = TARGET . '/' . $name;
    try {
        if ($f->isDir()) {
            $dit = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
            $fit = new RecursiveIteratorIterator($dit, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($fit as $item) {
                if ($item->isDir()) { rmdir($item->getPathname()); }
                else { unlink($item->getPathname()); $wipe++; }
            }
            rmdir($path);
        } else {
            unlink($path);
            $wipe++;
        }
    } catch (Exception $e) {
        $log[] = "  WARN: Could not delete $name: " . $e->getMessage();
    }
}
$log[] = "Wiped $wipe files";

// Now download fresh copy
$tmp = "/tmp/sbd-nuke-" . time();
mkdir($tmp, 0755, true);
$zip = $tmp . "/repo.zip";
$data = file_get_contents("https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip");
if (!$data) { die(json_encode(['ok'=>false,'log'=>array_merge($log,['Download failed'])])); }
file_put_contents($zip, $data);
$log[] = "Downloaded " . round(strlen($data)/1024) . "KB";

$z = new ZipArchive;
$z->open($zip); $z->extractTo($tmp); $z->close();

$src = '';
foreach (scandir($tmp) as $d) {
    if ($d[0] !== '.' && is_dir("$tmp/$d")) { $src = "$tmp/$d"; break; }
}

// Copy frontend/dist
function cpAll($from, $to, &$cnt, &$log) {
    if (!is_dir($from)) { $log[] = "  MISSING: $from"; return; }
    @mkdir($to, 0755, true);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $f) {
        $rel = str_replace($from, '', $f->getPathname());
        $dest = $to . $rel;
        if ($f->isDir()) { @mkdir($dest, 0755, true); }
        else { copy($f->getPathname(), $dest); $cnt++; }
    }
}

$count = 0;
cpAll("$src/frontend/dist", TARGET, $count, $log);
$log[] = "Frontend: $count files";

$bcount = 0;
cpAll("$src/backend", TARGET . "/backend", $bcount, $log);
$log[] = "Backend: $bcount files";

exec("rm -rf $tmp");
$total = $count + $bcount;
$log[] = "NUCLEAR DEPLOY COMPLETE: $total files";

// Verify
$after = [];
foreach (new DirectoryIterator(TARGET) as $f) {
    if (!$f->isDot()) $after[] = $f->getFilename();
}
$log[] = "Current files: " . implode(', ', $after);

echo json_encode(['ok'=>true, 'files'=>$total, 'wiped'=>$wipe, 'log'=>$log, 'current'=>$after], JSON_UNESCAPED_UNICODE);
