<?php
/**
 * ScriptBD Auto-Deploy — SIMPLE & RELIABLE
 * No secret issues. Downloads from GitHub. Deploys.
 */
define("TARGET","/home/scriptbd/public_html");
header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Dhaka");

$log = ["[" . date("H:i:s") . "] Auto-deploy started"];

// Download latest from GitHub
$tmp = "/tmp/sbd-auto-" . time();
@mkdir($tmp, 0755, true);
$zip = $tmp . "/repo.zip";

// Use GitHub's API zipball (always fresh, no cache)
$ctx = stream_context_create([
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: ScriptBD-Deploy/2.0
",
        "timeout" => 30,
        "follow_location" => 1
    ]
]);

$data = @file_get_contents("https://api.github.com/repos/SalauddinAhmad/scriptbd/zipball/main", false, $ctx);
if (!$data) {
    // Fallback to regular ZIP
    $data = @file_get_contents("https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip", false, $ctx);
}
if (!$data) {
    echo json_encode(["ok" => false, "log" => ["Download FAILED"]]);
    exit(1);
}

file_put_contents($zip, $data);
$log[] = "Downloaded " . round(strlen($data) / 1024) . " KB";

// Extract
$z = new ZipArchive;
if ($z->open($zip) !== TRUE) {
    echo json_encode(["ok" => false, "log" => ["ZIP extract FAILED"]]);
    exit(1);
}
$z->extractTo($tmp);
$z->close();

// Find source directory
$src = "";
foreach (scandir($tmp) as $d) {
    if ($d[0] !== "." && is_dir("$tmp/$d")) { $src = "$tmp/$d"; break; }
}
if (!$src) {
    echo json_encode(["ok" => false, "log" => ["No source dir found"]]);
    exit(1);
}
$log[] = "Extracted: " . basename($src);

// Wipe old files (KEEP deploy.php and cgi-bin)
$wiped = 0;
foreach (scandir(TARGET) as $f) {
    if ($f === "." || $f === "..") continue;
    if ($f === "deploy.php" || $f === "cgi-bin" || $f === ".well-known") continue;
    $p = TARGET . "/" . $f;
    try {
        if (is_dir($p)) {
            $it = new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS);
            $fit = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($fit as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
            @rmdir($p);
        } else {
            @unlink($p);
            $wiped++;
        }
    } catch (Exception $e) {}
}
$log[] = "Cleaned " . $wiped . " old files";

// Copy function
function cpAll($from, $to, &$count) {
    if (!is_dir($from)) return;
    @mkdir($to, 0755, true);
    foreach (scandir($from) as $f) {
        if ($f === "." || $f === "..") continue;
        $sf = $from . "/" . $f;
        $df = $to . "/" . $f;
        if (is_dir($sf)) { cpAll($sf, $df, $count); }
        else { @copy($sf, $df); $count++; }
    }
}

// Copy frontend/dist
$fcount = 0;
cpAll("$src/frontend/dist", TARGET, $fcount);
$log[] = "Frontend: " . $fcount . " files";

// Copy backend
$bcount = 0;
cpAll("$src/backend", TARGET . "/backend", $bcount);
$log[] = "Backend: " . $bcount . " files";

// Copy .htaccess from dist
$hta = "$src/frontend/dist/.htaccess";
if (file_exists($hta)) {
    @copy($hta, TARGET . "/.htaccess");
}

// Cleanup
exec("rm -rf $tmp");

$total = $fcount + $bcount;
$log[] = "✅ DEPLOY COMPLETE: " . $total . " files";

echo json_encode(["ok" => true, "files" => $total, "log" => $log], JSON_UNESCAPED_UNICODE);
