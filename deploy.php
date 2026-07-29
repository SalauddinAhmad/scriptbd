<?php
define("SECRET","***");
define("TARGET","/home/scriptbd_ag/public_html");
header("Content-Type: application/json; charset=utf-8");

if(($_GET["secret"]??"")!==SECRET) {http_response_code(403);die('{"ok":false}');}

$log=[];
$tmp="/tmp/sbd-".time();
mkdir($tmp,0755,true);
$zip=$tmp."/repo.zip";

// Download
$data=@file_get_contents("https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip");
if(!$data){die(json_encode(["ok"=>false,"error"=>"download failed"]));}
file_put_contents($zip,$data);
$log[]="Downloaded ".round(strlen($data)/1024)."KB";

// Extract  
$z=new ZipArchive;
$z->open($zip);
$z->extractTo($tmp);
$z->close();

// Find src dir
$src="";
foreach(scandir($tmp) as $d){
  if($d[0]!=="." && is_dir("$tmp/$d")){$src="$tmp/$d";break;}
}
$log[]="Extracted: $src";

// WIPE old files (keep deploy.php only)
$log[]="Cleaning...";
$w=0;
foreach(scandir(TARGET) as $name){
  if($name==="."||$name==="..")continue;
  if($name==="deploy.php"||$name==="cgi-bin")continue;
  $p=TARGET."/".$name;
  try{
    if(is_dir($p)){
      array_map("unlink",glob("$p/*.*"));
      @rmdir($p);
    }else{
      @unlink($p);
      $w++;
    }
  }catch(Exception $e){}
}
$log[]="Wiped $w";

// Copy frontend/dist  
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
cpdir("$src/frontend/dist",TARGET,$count);
$log[]="Frontend: $count files";

$bcount=0;  
cpdir("$src/backend",TARGET."/backend",$bcount);
$log[]="Backend: $bcount files";

// Cleanup
exec("rm -rf $tmp");
$log[]="DONE! ".($count+$bcount)." files";

echo json_encode(["ok"=>true,"files"=>$count+$bcount,"log"=>$log],JSON_UNESCAPED_UNICODE);
