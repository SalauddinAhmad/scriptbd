<?php
// Alternative deploy: accepts POST tar.gz from GitHub Actions
define('SECRET', 'script…cure');
define('TARGET', '/home/scriptbd_ag/public_html');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka');
$secret = $_GET['secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '';
if ($secret !== SECRET) { http_response_code(403); die('{"ok":false}'); }
if (!isset($_FILES['package']) || $_FILES['package']['error']!==UPLOAD_ERR_OK) {
    die('{"ok":false,"error":"no package"}');
}
$tmp="/tmp/sbd-post-".time(); mkdir($tmp,0755,true);
$tgz=$tmp."/pkg.tar.gz";
move_uploaded_file($_FILES['package']['tmp_name'],$tgz);
$p=new PharData($tgz); $p->extractTo($tmp);

$cnt=0;
$iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
foreach($iter as $f){
    $rel=str_replace($tmp.'/','',$f->getPathname());
    if(strpos($rel,'pkg.tar.gz')!==false) continue;
    $dest=TARGET.'/'.$rel;
    if($f->isDir()){@mkdir($dest,0755,true);}
    else{@copy($f->getPathname(),$dest); $cnt++;}
}
exec("rm -rf $tmp");
echo json_encode(['ok'=>true,'files'=>$cnt]);
