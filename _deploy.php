<?php
/**
 * ScriptBD — Fresh Auto-Deploy
 * Called by GitHub Actions. Downloads from GitHub, deploys.
 */
define("SECRET","sbd2026deploy");
header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Dhaka");

if(($_GET["secret"]??"")!==SECRET){http_response_code(403);die('{"ok":false}');}

$log=["[".date("H:i:s")."] Deploy start"];
$tmp="/tmp/sbd-".time();
@mkdir($tmp,0755,true);

// Download — GitHub API zipball (always fresh)
$ctx=stream_context_create(["http"=>["method"=>"GET","header"=>"User-Agent: ScriptBD/3.0
","timeout"=>30,"follow_location"=>1]]);
$data=@file_get_contents("https://codeload.github.com/SalauddinAhmad/scriptbd/zip/refs/heads/main",false,$ctx);
if(!$data)$data=@file_get_contents("https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip",false,$ctx);
if(!$data){echo json_encode(["ok"=>false,"log"=>["Download FAILED"]],256);exit(1);}
file_put_contents("$tmp/repo.zip",$data);
$log[]="Download: ".round(strlen($data)/1024)."KB";

$z=new ZipArchive;$z->open("$tmp/repo.zip");$z->extractTo($tmp);$z->close();
$src="";foreach(scandir($tmp)as$d){if($d[0]!=="."&&is_dir("$tmp/$d")){$src="$tmp/$d";break;}}
$log[]="Extracted: ".basename($src);

$target=$_SERVER["DOCUMENT_ROOT"]?:"/home/scriptbd/public_html";

// Wipe + Deploy
$w=0;
foreach(scandir($target)as$f){
  if($f==="."||$f==="..")continue;
  if(in_array($f,["deploy.php","cgi-bin",".well-known","_deploy.php"]))continue;
  $p=$target."/".$f;
  try{
    if(is_dir($p)){
      $ri=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
      foreach($ri as $i)$i->isDir()?@rmdir($i->getPathname()):@unlink($i->getPathname());
      @rmdir($p);
    }else{@unlink($p);$w++;}
  }catch(Exception $e){}
}
$log[]="Wiped $w";

function cpAll($from,$to,&$n){
  if(!is_dir($from))return;
  @mkdir($to,0755,true);
  foreach(scandir($from)as$f){
    if($f==="."||$f==="..")continue;
    $sf=$from."/".$f;$df=$to."/".$f;
    if(is_dir($sf))cpAll($sf,$df,$n);else{@copy($sf,$df);$n++;}
  }
}
$fc=0;cpAll("$src/frontend/dist",$target,$fc);
$bc=0;cpAll("$src/backend","$target/backend",$bc);
if(file_exists("$src/frontend/dist/.htaccess"))@copy("$src/frontend/dist/.htaccess","$target/.htaccess");

exec("rm -rf $tmp");
$log[]="DONE: ".($fc+$bc)." files";
echo json_encode(["ok"=>true,"files"=>$fc+$bc,"log"=>$log],256);
