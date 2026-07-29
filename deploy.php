<?php
define('SECRET', 'sbd2026deploy');
define('TARGET', '/home/scriptbd_ag/public_html');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka');
$secret = $_GET['secret'] ?? ($_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '');
if ($secret !== SECRET) { http_response_code(403); die('{"ok":false}'); }

$log = ["[" . date('H:i:s') . "] Clean deploy start"];
$tmp = "/tmp/sbd-clean-" . time();

// Download ZIP
mkdir($tmp, 0755, true);
$zip = $tmp . "/repo.zip";
$data = file_get_contents("https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip");
if (!$data) { die(json_encode(['ok'=>false,'log'=>['Download failed']])); }
file_put_contents($zip, $data);
$log[] = "Downloaded " . round(strlen($data)/1024) . "KB";

// Extract
$z = new ZipArchive;
$z->open($zip); $z->extractTo($tmp); $z->close();
$src = ''; 
foreach (scandir($tmp) as $d) { 
    if ($d[0]!=='.' && is_dir("$tmp/$d")) { $src="$tmp/$d"; break; }
}
$log[] = "Extracted";

// DELETE old assets first
$old_assets = glob(TARGET . '/assets/*');
$deleted = 0;
foreach ($old_assets as $f) {
    if (is_file($f)) { unlink($f); $deleted++; }
}
$log[] = "Deleted " . $deleted . " old assets";

// Remove old index.html
if (file_exists(TARGET.'/index.html')) { 
    @unlink(TARGET.'/index.html'); 
    $log[] = "Removed old index.html"; 
}

// Copy frontend/dist
function cpDir($src, $dest, &$ct) {
    if (!is_dir($src)) return;
    @mkdir($dest, 0755, true);
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    ) as $f) {
        $rel = str_replace($src, '', $f->getPathname());
        $d = $dest . $rel;
        if ($f->isDir()) { @mkdir($d, 0755, true); }
        else { @copy($f->getPathname(), $d); $ct++; }
    }
}

$count = 0;
cpDir("$src/frontend/dist", TARGET, $count);
$log[] = "Frontend: " . $count . " files";

$bcount = 0;
cpDir("$src/backend", TARGET . "/backend", $bcount);
$log[] = "Backend: " . $bcount . " files";

// Copy .htaccess if exists
if (file_exists("$src/frontend/dist/.htaccess")) {
    copy("$src/frontend/dist/.htaccess", TARGET . "/.htaccess");
    $log[] = "Copied .htaccess";
}

exec("rm -rf $tmp");
$total = $count + $bcount;
$log[] = "DONE! " . $total . " files deployed";

echo json_encode(['ok'=>true, 'files'=>$total, 'log'=>$log, 'secret'=>SECRET], JSON_UNESCAPED_UNICODE);
