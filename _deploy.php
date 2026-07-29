<?php
/**
 * ScriptBD — Fresh Auto-Deploy
 * Updated with Error Handling & removed exec()
 */
define("SECRET","sbd2026deploy");
header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set("Asia/Dhaka");

try {
    if(($_GET["secret"]??"")!==SECRET){
        http_response_code(403);
        die(json_encode(["ok"=>false, "error"=>"Invalid Secret"]));
    }

    $log=["[".date("H:i:s")."] Deploy start"];
    
    // /tmp এর বদলে ডাইনামিক টেম্প ফোল্ডার ব্যবহার করা হলো
    $tmp = sys_get_temp_dir() . "/sbd-".time(); 
    @mkdir($tmp,0755,true);

    // Download — GitHub API zipball
    $ctx=stream_context_create(["http"=>["method"=>"GET","header"=>"User-Agent: ScriptBD/3.0\r\n","timeout"=>30,"follow_location"=>1]]);
    
    $data=@file_get_contents("https://codeload.github.com/SalauddinAhmad/scriptbd/zip/refs/heads/main",false,$ctx);
    if(!$data) $data=@file_get_contents("https://github.com/SalauddinAhmad/scriptbd/archive/refs/heads/main.zip",false,$ctx);
    
    if(!$data){
        die(json_encode(["ok"=>false,"error"=>"GitHub Download FAILED"]));
    }
    
    file_put_contents("$tmp/repo.zip",$data);
    $log[]="Download: ".round(strlen($data)/1024)."KB";

    // ZipArchive চেক করা
    if(!class_exists('ZipArchive')) {
        die(json_encode(["ok"=>false,"error"=>"ZipArchive PHP extension is missing in cPanel!"]));
    }

    $z=new ZipArchive;
    if($z->open("$tmp/repo.zip") === TRUE) {
        $z->extractTo($tmp);
        $z->close();
    } else {
        die(json_encode(["ok"=>false,"error"=>"Failed to extract ZIP file!"]));
    }

    $src="";
    foreach(scandir($tmp)as$d){
        if($d[0]!=="."&&is_dir("$tmp/$d")){
            $src="$tmp/$d";
            break;
        }
    }
    $log[]="Extracted: ".basename($src);

    $target=$_SERVER["DOCUMENT_ROOT"]?:"/home/scriptbd/public_html";

    // Wipe target (Skipping specific files)
    $w=0;
    foreach(scandir($target)as$f){
        if($f==="."||$f==="..")continue;
        if(in_array($f,["deploy.php","cgi-bin",".well-known","_deploy.php"]))continue;
        $p=$target."/".$f;
        try{
            if(is_dir($p)){
                $ri=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p,RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
                foreach($ri as $i) $i->isDir() ? @rmdir($i->getPathname()) : @unlink($i->getPathname());
                @rmdir($p);
            }else{ @unlink($p); $w++; }
        }catch(Exception $e){}
    }
    $log[]="Wiped $w files";

    // Copy new files
    function cpAll($from,$to,&$n){
        if(!is_dir($from))return;
        @mkdir($to,0755,true);
        foreach(scandir($from)as$f){
            if($f==="."||$f==="..")continue;
            $sf=$from."/".$f;$df=$to."/".$f;
            if(is_dir($sf)) cpAll($sf,$df,$n); else { @copy($sf,$df); $n++; }
        }
    }
    
    $fc=0;
    if($src !== "") {
        cpAll("$src/frontend/dist",$target,$fc);
        cpAll("$src/backend","$target/backend",$fc);
        if(file_exists("$src/frontend/dist/.htaccess")) @copy("$src/frontend/dist/.htaccess","$target/.htaccess");
    }

    // exec() রিমুভ করা হয়েছে কারণ এটি cPanel-এ ব্লক থাকে
    // টেম্পোরারি ফাইলগুলো সিস্টেম নিজে থেকেই মুছে ফেলবে

    $log[]="DONE: ".$fc." files";
    echo json_encode(["ok"=>true,"files"=>$fc,"log"=>$log], 256);

} catch (Throwable $e) {
    // যেকোনো Error যেন JSON ফরম্যাটে যায়, তাহলে গিটহাব অ্যাকশন ক্র্যাশ করবে না
    echo json_encode([
        "ok" => false, 
        "error" => "PHP Error: " . $e->getMessage(), 
        "line" => $e->getLine()
    ], 256);
}
