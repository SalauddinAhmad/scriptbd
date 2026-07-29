<?php
define("SECRET","***");
header("Content-Type: application/json; charset=utf-8");

if(($_GET["secret"]??"")!==SECRET) {http_response_code(403);die('{"ok":false}');}

// AUTO-DETECT target path
$TARGET = $_SERVER["DOCUMENT_ROOT"];
if(!$TARGET || !is_dir($TARGET)) {
  // Try common cPanel paths
  $user = get_current_user();
  foreach(["/home/$user/public_html","/home/script…/public_html","/home/scriptbd/public_html"] as $try) {
    if(is_dir($try)) { $TARGET=$try; break; }
  }
}

$log=["TARGET=$TARGET","USER=".get_current_user(),"DOCROOT=".$_SERVER["DOCUMENT_ROOT"]];

// Download from GitHub
$tmp="/tmp/sbd-".time();
mkdir($tmp,0755,true);
$data=@file_get_contents(..., false, stream_context_create(['http'=>['header'=>'User-Agent: ScriptBD-Deploy/1.0','follow_location'=>1,'timeout'=>30]])) ?: file_get_contents("https://api.github.com/repos/SalauddinAhmad/scriptbd/zipball/main");
if(!$data){die(json_encode(["ok"=>false,"log"=>$log,"error"=>"download failed"]));}
file_put_contents($tmp."/repo.zip",$data);
$log[]="Downloaded ".round(strlen($data)/1024)."KB";

// Extract
$z=new ZipArchive;
$z->open($tmp."/repo.zip");
$z->extractTo($tmp);
$z->close();

$src="";
foreach(scandir($tmp) as $d){
  if($d[0]!=="." && is_dir("$tmp/$d")){$src="$tmp/$d";break;}
}
$log[]="Extracted: $src";

// WIPE old files
$w=0;
foreach(scandir($TARGET) as $name){
  if($name==="."||$name==="..")continue;
  if($name==="deploy.php"||$name==="cgi-bin"||$name==="error_log")continue;
  $p=$TARGET."/".$name;
  try{
    if(is_dir($p)){array_map("unlink",glob("$p/*.*"));@rmdir($p);}
    else{@unlink($p);$w++;}
  }catch(Exception $e){}
}
$log[]="Wiped $w files";

// Copy
function cpdir($from,$to,&$n){
  if(!is_dir($from))return;
  @mkdir($to,0755,true);
  foreach(scandir($from) as $f){
    if($f==="."||$f==="..")continue;
    $sf=$from."/".$f;
    $df=$to."/".$f;
    if(is_dir($sf)){cpdir($sf,$df,$n);}
    else{copy($sf,$df);$n++;}
  }
}

$count=0;
cpdir("$src/frontend/dist",$TARGET,$count);
$log[]="Frontend: $count";

$bcount=0;
cpdir("$src/backend",$TARGET."/backend",$bcount);
$log[]="Backend: $bcount";

exec("rm -rf $tmp");
$log[]="DONE! ".($count+$bcount)." total";

// List final dir
$files=implode(", ",array_diff(scandir($TARGET),["..","."]));

echo json_encode(["ok"=>true,"files"=>$count+$bcount,"log"=>$log,"dir"=>$files],JSON_UNESCAPED_UNICODE);
