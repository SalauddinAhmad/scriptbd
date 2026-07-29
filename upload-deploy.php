<?php
/**
 * ScriptBD — Receive Deploy Package
 * GitHub Actions POSTs tar.gz → deploys to public_html
 */
header("Content-Type: application/json; charset=utf-8");

$secret = $_GET["secret"] ?? "";
if ($secret !== "***") {
    http_response_code(403); die('{"ok":false}');
}

$tmp = "/tmp/sbd-gh-" . time();
mkdir($tmp, 0755, true);

// Check if file was uploaded
if (!isset($_FILES["pkg"]) || $_FILES["pkg"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["ok" => false, "error" => "No file uploaded", "errno" => $_FILES["pkg"]["error"] ?? "none"]);
    exit(1);
}

$tgz = $tmp . "/deploy.tar.gz";
move_uploaded_file($_FILES["pkg"]["tmp_name"], $tgz);

// Extract
$phar = new PharData($tgz);
$phar->extractTo($tmp);

// Wipe old files (keep deploy.php, cgi-bin, upload-deploy.php)
$target = "/home/scriptbd/public_html";
$w = 0;
foreach (scandir($target) as $f) {
    if ($f === "." || $f === "..") continue;
    if (in_array($f, ["deploy.php", "cgi-bin", "upload-deploy.php", ".well-known"])) continue;
    $p = $target . "/" . $f;
    try {
        if (is_dir($p)) {
            $it = new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS);
            $fit = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($fit as $item) $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            @rmdir($p);
        } else { @unlink($p); $w++; }
    } catch (Exception $e) {}
}

// Copy all files from temp to target
$count = 0;
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iter as $item) {
    $rel = str_replace($tmp . "/", "", $item->getPathname());
    if (strpos($rel, "deploy.tar.gz") !== false) continue;
    $dest = $target . "/" . $rel;
    if ($item->isDir()) { @mkdir($dest, 0755, true); }
    else { @copy($item->getPathname(), $dest); $count++; }
}

exec("rm -rf $tmp");
echo json_encode(["ok" => true, "files" => $count, "wiped" => $w], JSON_UNESCAPED_UNICODE);
