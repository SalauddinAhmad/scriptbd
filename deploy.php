<?php
define("SECRET", "***");
define("TARGET", "/home/scriptbd_ag/public_html");

header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Dhaka");

$s = $_GET["secret"] ?? "";
if ($s !== SECRET) { http_response_code(403); die('{"ok":false,"error":"bad secret"}'); }

$log = [];
$tmp = "/tmp/sbd-" . time();
mkdir($tmp, 0755, true);

// Download from GitHub
$zip_url = "https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip";
$zip_file = $tmp . "/repo.zip";
$data = @file_get_contents($zip_url);
if (!$data) { die(json_encode(["ok"=>false,"log"=>["DOWNLOAD FAILED"]])); }
file_put_contents($zip_file, $data);
$log[] = "Downloaded " . round(strlen($data)/1024) . "KB";

// Extract
$z = new ZipArchive;
$z->open($zip_file);
$z->extractTo($tmp);
$z->close();

$src = "";
foreach (scandir($tmp) as $d) {
    if ($d[0] !== "." && is_dir("$tmp/$d")) { $src = "$tmp/$d"; break; }
}
$log[] = "Extracted to $src";

// WIPE old files (except deploy.php and cgi-bin)
$log[] = "Cleaning old files...";
$wiped = 0;
foreach (new DirectoryIterator(TARGET) as $item) {
    if ($item->isDot()) continue;
    $name = $item->getFilename();
    if (in_array($name, ["cgi-bin", "deploy.php", ".well-known"])) continue;
    $p = TARGET . "/" . $name;
    try {
        if ($item->isDir()) {
            $dit = new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS);
            $fit = new RecursiveIteratorIterator($dit, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($fit as $f) {
                $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
            }
            rmdir($p);
        } else {
            unlink($p);
            $wiped++;
        }
    } catch (Exception $e) { $log[] = "WARN: " . $name . " - " . $e->getMessage(); }
}
$log[] = "Wiped " . $wiped . " files";

// Copy frontend dist to target
function cpAll($from, $to, &$cnt) {
    if (!is_dir($from)) return;
    @mkdir($to, 0755, true);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $f) {
        $rel = str_replace($from, "", $f->getPathname());
        if ($f->isDir()) { @mkdir($to . $rel, 0755, true); }
        else { copy($f->getPathname(), $to . $rel); $cnt++; }
    }
}

$count = 0;
cpAll("$src/frontend/dist", TARGET, $count);
$log[] = "Frontend: " . $count . " files";

$bcount = 0;
cpAll("$src/backend", TARGET . "/backend", $bcount);
$log[] = "Backend: " . $bcount . " files";

// Copy .htaccess
$hta = "$src/frontend/dist/.htaccess";
if (file_exists($hta)) {
    copy($hta, TARGET . "/.htaccess");
    $log[] = "Copied .htaccess";
}

// Cleanup temp
exec("rm -rf $tmp");

$total = $count + $bcount;
$log[] = "DEPLOY COMPLETE: " . $total . " files";

// List current files
$files = [];
foreach (new DirectoryIterator(TARGET) as $f) {
    if (!$f->isDot()) $files[] = $f->getFilename();
}

echo json_encode([
    "ok" => true,
    "files" => $total,
    "wiped" => $wiped,
    "log" => $log,
    "current" => $files
], JSON_UNESCAPED_UNICODE);
