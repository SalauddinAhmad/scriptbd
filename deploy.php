<?php
define("SECRET","sbd2026deploy");
define("TARGET","/home/scriptbd/public_html");
header("Content-Type: application/json");
$s=$_GET["secret"]??""; if($s!==SECRET){die('{"ok":false}');}
$log=["[".date("H:i:s")."] Deploy start"];
$tmp="/tmp/sbd-".time(); mkdir($tmp,0755,true);

// Download — use GitHub API zipball which is always up-to-date  
$ctx=stream_context_create(["http"=>["method"=>"GET","header"=>"User-Agent: ScriptBD/1.0
","timeout"=>30,"follow_location"=>1]]);
$data=@file_get_contents("https://api.github.com/repos/SalauddinAhmad/scriptbd/zipball/main",false,$ctx);
if(!$data){die(json_encode(["ok"=>false,"log"=>["Download failed"]]));}
file_put_contents("$tmp/repo.zip",$data);
$log[]="Downloaded ".round(strlen($data)/1024)."KB";

$z=new ZipArchive; $z->open("$tmp/repo.zip"); $z->extractTo($tmp); $z->close();
$src=""; foreach(scandir($tmp) as $d){if($d[0]!=="."&&is_dir("$tmp/$d")){$src="$tmp/$d";break;}}
$log[]="Extracted: ".basename($src);

// Wipe old files
$w=0;
foreach(scandir(TARGET) as $f){
  if($f==="."||$f==="..")continue;
  if($f==="deploy.php"||$f==="cgi-bin"||$f==="error_log")continue;
  $p=TARGET."/".$f;
  try{ if(is_dir($p)){array_map("unlink",glob("$p/*.*"));@rmdir($p);}else{@unlink($p);$w++;} }
  catch(Exception $e){}
}
$log[]="Wiped $w";

// Copy
function cpDir($from,$to,&$n){
  if(!is_dir($from))return;
  @mkdir($to,0755,true);
  foreach(scandir($from) as $f){
    if($f==="."||$f==="..")continue;
    $sf=$from."/".$f; $df=$to."/".$f;
    if(is_dir($sf))cpDir($sf,$df,$n); else{copy($sf,$df);$n++;}
  }
}

$c=0; $srcDist=$src."/frontend/dist";
if(is_dir($srcDist)){cpDir($srcDist,TARGET,$c);} else{cpDir($src."/frontend/dist",TARGET,$c);}
$log[]="Frontend: $c";

$bc=0; cpDir($src."/backend",TARGET."/backend",$bc);
$log[]="Backend: $bc";

exec("rm -rf $tmp");
$tt=$c+$bc;
$log[]="DONE: $tt files";
echo json_encode(["ok"=>true,"files"=>$tt,"log"=>$log],JSON_UNESCAPED_UNICODE);
