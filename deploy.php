<?php
/**
 * ScriptBD Auto-Deploy
 * Server downloads from GitHub → extracts → deploys
 * Trigger: GET with correct secret. No file upload needed.
 */
define("SECRET","sbd2026deploy");
header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Dhaka");

$secret = $_GET["secret"] ?? "";
if ($secret !== SECRET) { http_response_code(403); die('{"ok":false,"error":"bad secret"}'); }

$log = ["[" . date("H:i:s") . "] Deploy started - downloading from GitHub"];
$tmp = "/tmp/sbd-dep-" . time();
@mkdir($tmp, 0755, true);

// Download from GitHub (codeload - no cache, always fresh)
$ctx = stream_context_create(["http" => ["method"=>"GET","header"=>"User-Agent: ScriptBD-Deploy
","timeout"=>30,"follow_location"=>1]]);
$url = "https://codeload.github.com/SalauddinAhmad/scriptbd/zip/refs/heads/main";
$data = @file_get_contents($url, false, $ctx);

// Fallback
if (!$data) {
    $url = "https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip";
    $data = @file_get_contents($url, false, $ctx);
}

if (!$data) {
    $log[] = "ERROR: Download failed from both sources";
    echo json_encode(["ok"=>false,"log"=>$log], JSON_UNESCAPED_UNICODE); exit(1);
}

file_put_contents("$tmp/repo.zip", $data);
$log[] = "Downloaded " . round(strlen($data)/1024) . " KB";

// Extract
$zip = new ZipArchive;
if ($zip->open("$tmp/repo.zip") !== TRUE) {
    $log[] = "ERROR: Cannot open ZIP";
    echo json_encode(["ok"=>false,"log"=>$log], JSON_UNESCAPED_UNICODE); exit(1);
}
$zip->extractTo($tmp);
$zip->close();

// Find source folder (scriptbd-main or SalauddinAhmad-scriptbd-XXXXX)
$src = "";
foreach (scandir($tmp) as $d) {
    if ($d[0] !== "." && is_dir("$tmp/$d")) { $src = "$tmp/$d"; break; }
}
if (!$src) {
    $log[] = "ERROR: No source dir in ZIP";
    echo json_encode(["ok"=>false,"log"=>$log], JSON_UNESCAPED_UNICODE); exit(1);
}
$log[] = "Extracted: " . basename($src);

// TARGET
$target = $_SERVER["DOCUMENT_ROOT"] ?: "/home/scriptbd/public_html";
$log[] = "Target: $target";

// Wipe old files (keep deploy.php, cgi-bin, .well-known)
$w = 0;
foreach (scandir($target) as $f) {
    if ($f === "." || $f === "..") continue;
    if (in_array($f, ["deploy.php","cgi-bin",".well-known","upload-deploy.php"])) continue;
    $p = $target . "/" . $f;
    try {
        if (is_dir($p)) {
            $ri = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($ri as $item) $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            @rmdir($p);
        } else { @unlink($p); $w++; }
    } catch (Exception $e) {}
}
$log[] = "Cleaned $w old files";

// Copy function
function cpAll($from, $to, &$n) {
    if (!is_dir($from)) return;
    @mkdir($to, 0755, true);
    foreach (scandir($from) as $f) {
        if ($f === "." || $f === "..") continue;
        $sf = $from . "/" . $f; $df = $to . "/" . $f;
        if (is_dir($sf)) cpAll($sf, $df, $n);
        else { @copy($sf, $df); $n++; }
    }
}

// Deploy frontend/dist
$fc = 0;
cpAll("$src/frontend/dist", $target, $fc);
$log[] = "Frontend: $fc files";

// Deploy backend
$bc = 0;
cpAll("$src/backend", "$target/backend", $bc);
$log[] = "Backend: $bc files";

// Copy .htaccess
if (file_exists("$src/frontend/dist/.htaccess")) {
    @copy("$src/frontend/dist/.htaccess", "$target/.htaccess");
    $log[] = "Copied .htaccess";
}

// Cleanup
exec("rm -rf $tmp");
$total = $fc + $bc;
$log[] = "✅ DEPLOYED: $total files → scriptbd.com";

echo json_encode(["ok"=>true, "files"=>$total, "wiped"=>$w, "log"=>$log], JSON_UNESCAPED_UNICODE);
