<?php
/**
 * ScriptBD — Admin Dashboard v5
 * STANDALONE — no require_once needed
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

// Allow auto-login & form-based login
if (isset($_GET['auto']) && $_GET['auto'] === '1') {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
}
if (isset($_POST['user']) && $_POST['user'] === 'admin' && $_POST['pass'] === 'admin123') {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
}
if (empty($_SESSION['admin_logged_in'])) {
    // Show a quick inline login instead of redirect
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="bn"><head><meta charset="UTF-8"><title>Admin Login - ScriptBD</title>';
    echo '<style>body{background:#08080f;color:#e8e6f0;font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}';
    echo 'form{background:#141428;padding:30px;border-radius:14px;border:1px solid #22223a;text-align:center}';
    echo 'input{display:block;width:100%;padding:10px;margin:8px 0;background:#0f0f1a;border:1px solid #22223a;color:#e8e6f0;border-radius:8px}';
    echo 'button{background:#ff6b35;color:#fff;border:none;padding:10px 30px;border-radius:8px;cursor:pointer;margin-top:10px;font-weight:600}';
    echo 'h2{margin-bottom:20px;color:#ff6b35}</style></head><body>';
    echo '<form method="POST"><h2>🔐 ScriptBD Admin</h2>';
    echo '<input name="user" placeholder="Username" value="admin">';
    echo '<input name="pass" type="password" placeholder="Password" value="admin123">';
    echo '<button type="submit">Login</button></form></body></html>';
    exit;
}

// DB config inline
define('DB_HOST','localhost');
define('DB_NAME','scriptbd_scriptbd_db');
define('DB_USER','scriptbd_scriptbd_user');
define('DB_PASS','Sbd@2026!Pro');

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die('<div style="background:#300;color:#f88;padding:30px;margin:40px;border-radius:12px;font-family:sans-serif">
        <h2>⚠️ Database Error</h2><p>'.$e->getMessage().'</p></div>');
}

// ─── AJAX ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['a'])) {
    header('Content-Type: application/json');
    $a = $_POST['a']; $id = (int)($_POST['id'] ?? 0);
    try {
        if ($a === 'v') $db->exec("UPDATE orders SET payment_status='verified' WHERE id=$id");
        elseif ($a === 'd') $db->exec("UPDATE orders SET delivery_status='delivered', status='completed' WHERE id=$id");
        elseif ($a === 'x') $db->exec("DELETE FROM orders WHERE id=$id");
        elseif ($a === 's') $db->exec("UPDATE orders SET status='".$_POST['status']."' WHERE id=$id");
        elseif ($a === 'g') {
            $r = $db->query("SELECT * FROM orders WHERE id=$id")->fetch();
            echo json_encode(['ok'=>true,'data'=>$r]); exit;
        }
        echo json_encode(['ok'=>true]); exit;
    } catch (Exception $e) { echo json_encode(['ok'=>false,'err'=>$e->getMessage()]); exit; }
}

// ─── Fetch ───
$f = $_GET['f'] ?? '';
$q = trim($_GET['q'] ?? '');
$pg = max(1, (int)($_GET['pg'] ?? 1));
$lim = 10;
$off = ($pg-1)*$lim;

$w = '';
if (in_array($f, ['pending','processing','completed','cancelled'])) $w = "WHERE status='$f'";
elseif ($f === 'submitted') $w = "WHERE payment_status='submitted'";
elseif ($f === 'verified') $w = "WHERE payment_status='verified'";

if ($q) {
    $s = $db->quote('%'.$q.'%');
    $n = (int)$q;
    $w .= ($w?' AND':'WHERE')." (name LIKE $s OR email LIKE $s OR phone LIKE $s OR topic LIKE $s OR id=$n)";
}

$total = $db->query("SELECT COUNT(*) FROM orders $w")->fetchColumn();
$pages = ceil($total/$lim);
$orders = $db->query("SELECT * FROM orders $w ORDER BY created_at DESC LIMIT $lim OFFSET $off")->fetchAll();

$sc = $db->query("SELECT status, COUNT(*) FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$pc = $db->query("SELECT payment_status, COUNT(*) FROM orders GROUP BY payment_status")->fetchAll(PDO::FETCH_KEY_PAIR);
$ta = array_sum($sc);

function badge($t,$v){
    $m=['pending'=>['#fbbf24','⏳ পেন্ডিং'],'processing'=>['#3b82f6','⚡ প্রসেসিং'],
    'completed'=>['#10b981','✅ সম্পন্ন'],'cancelled'=>['#ef4444','❌ বাতিল'],
    'unpaid'=>['#6b7280','💤 অনাদায়ী'],'submitted'=>['#f59e0b','📩 জমা'],
    'verified'=>['#10b981','💎 ভেরিফাইড'],'delivered'=>['#8b5cf6','📦 ডেলিভার্ড'],
    'not_delivered'=>['#6b7280','⏳ বাকি']];
    $x=$m[$v]??$m['pending'];
    return "<span style='background:{$x[0]}20;color:{$x[0]};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid {$x[0]}40'>{$x[1]}</span>";
}
function ago($d){if(!$d)return'-';$df=time()-strtotime($d);if($df<60)return'এখন';if($df<3600)return floor($df/60).'মি';if($df<86400)return floor($df/3600).'ঘ';return floor($df/86400).'দিন';}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ড্যাশবোর্ড • ScriptBD</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#08080f;--card:#141428;--accent:#ff6b35;--ac2:#ff3366;--txt:#e8e6f0;--dim:#8a88a0;--border:#22223a;--green:#10b981;--blue:#3b82f6;--red:#ef4444;--gold:#f59e0b;--purple:#8b5cf6;--r:14px;--rs:10px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Noto Sans Bengali',system-ui,sans-serif;background:var(--bg);color:var(--txt);min-height:100vh;background-image:radial-gradient(ellipse at 20% 0%,rgba(255,107,53,.05) 0%,transparent 60%),radial-gradient(ellipse at 80% 100%,rgba(139,92,246,.05) 0%,transparent 60%)}
.app{max-width:1500px;margin:0 auto;padding:20px}
/* Nav */
.nav{background:rgba(20,20,40,.9);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.nav-l{font-size:18px;font-weight:900;background:linear-gradient(135deg,var(--accent),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-r{display:flex;align-items:center;gap:14px;font-size:13px}
.bt-out{background:transparent;border:1px solid var(--border);color:var(--dim);padding:6px 14px;border-radius:8px;cursor:pointer;font:inherit;font-size:12px;text-decoration:none;transition:.3s}
.bt-out:hover{border-color:var(--red);color:var(--red)}
/* Quick bar */
.qb{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.qbtn{background:var(--card);border:1px solid var(--border);color:var(--dim);padding:7px 14px;border-radius:20px;cursor:pointer;font:inherit;font-size:11px;text-decoration:none;transition:.3s}
.qbtn:hover,.qbtn.on{background:var(--accent);color:#fff;border-color:var(--accent)}
/* Stats */
.sg{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:20px}
.sc{background:var(--card);border:1px solid var(--border);border-radius:var(--rs);padding:14px;cursor:pointer;text-decoration:none;color:inherit;transition:.3s}
.sc:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.3);border-color:var(--accent)}
.sc.on{border-color:var(--accent);box-shadow:0 0 0 1px var(--accent)30}
.sn{font-size:22px;font-weight:800}.sl{font-size:10px;color:var(--dim);margin-top:2px}
.sbar{height:2px;margin-top:6px;background:var(--border);border-radius:2px;overflow:hidden}
.sbarfill{height:100%;border-radius:2px;transition:.5s}
/* Pay */
.pg{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:8px;margin-bottom:18px}
.pc{background:#0f0f1a;border:1px solid var(--border);border-radius:var(--rs);padding:12px;text-align:center;cursor:pointer;text-decoration:none;color:inherit;transition:.3s}
.pc:hover,.pc.on{border-color:var(--accent);background:rgba(255,107,53,.1)}
.pn{font-size:20px;font-weight:800}.pl{font-size:10px;color:var(--dim)}
/* Toolbar */
.tb{display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;align-items:center;background:var(--card);border:1px solid var(--border);border-radius:var(--rs);padding:10px}
.ts{flex:1;min-width:150px;background:#0f0f1a;border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--txt);font:inherit;font-size:12px;outline:none}
.ts:focus{border-color:var(--accent)}
.bta{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font:inherit;font-size:11px;font-weight:600;transition:.2s}
.bta:hover{filter:brightness(1.1)}
.cb{font-size:11px;color:var(--dim);white-space:nowrap}
/* Table */
.tw{background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden}
.tbl{width:100%;border-collapse:collapse;font-size:12px}
.tbl th{background:#0f0f1a;padding:10px;text-align:left;font-size:10px;text-transform:uppercase;color:var(--dim);border-bottom:1px solid var(--border);white-space:nowrap;font-weight:600}
.tbl td{padding:10px;border-bottom:1px solid var(--border)}
.tbl tbody tr:hover{background:rgba(255,107,53,.02)}
.cid{color:var(--accent);font-weight:700;cursor:pointer}.cid:hover{text-decoration:underline}
.acts{display:flex;gap:4px;flex-wrap:wrap}
.bs{padding:4px 8px;border:none;border-radius:5px;cursor:pointer;font:inherit;font-size:10px;font-weight:600;transition:.2s}
.bv{background:rgba(16,185,129,.15);color:var(--green)}.bv:hover{background:rgba(16,185,129,.25)}
.bd{background:rgba(139,92,246,.15);color:var(--purple)}.bd:hover{background:rgba(139,92,246,.25)}
.bw{background:rgba(59,130,246,.15);color:var(--blue)}.bw:hover{background:rgba(59,130,246,.25)}
.br{background:rgba(239,68,68,.15);color:var(--red)}.br:hover{background:rgba(239,68,68,.25)}
.empty{text-align:center;padding:50px;color:var(--dim)}
/* Modal */
.mo{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center}
.mo.on{display:flex}
.md{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;width:90%;max-width:600px;max-height:85vh;overflow-y:auto}
.mx{float:right;background:none;border:none;color:var(--dim);font-size:20px;cursor:pointer}
.md h2{font-size:18px;margin-bottom:16px;background:linear-gradient(135deg,var(--accent),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.dr{margin-bottom:10px}.dl{font-size:10px;color:var(--dim)}.dv{font-size:13px}
.mbox{background:#0f0f1a;border:1px solid var(--border);border-radius:8px;padding:12px;font-size:12px;line-height:1.6;margin-top:4px}
/* Toast */
.tc{position:fixed;bottom:20px;right:20px;z-index:300}
.tst{padding:10px 18px;border-radius:8px;font-size:12px;font-weight:600;margin-top:6px;animation:fi .3s}
.to{background:var(--green);color:#000}.te{background:var(--red);color:#fff}
@keyframes fi{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
/* Pagination */
.pag{display:flex;gap:6px;justify-content:center;margin-top:16px}
.plk{padding:6px 12px;background:var(--card);border:1px solid var(--border);border-radius:6px;color:var(--dim);text-decoration:none;font-size:11px}
.plk:hover,.plk.on{background:var(--accent);color:#fff}
@media(max-width:768px){.app{padding:12px 8px}.tbl{font-size:10px}.tbl td,.tbl th{padding:6px 4px}}
</style>
</head>
<body>
<nav class="nav">
  <div class="nav-l">📜 ScriptBD ADMIN</div>
  <div class="nav-r">
    <span><?=htmlspecialchars($_SESSION['admin_username']??'Admin')?></span>
    <a href="logout.php" class="bt-out">🚪 লগআউট</a>
  </div>
</nav>
<div class="app">

<div class="qb">
  <a href="dashboard.php" class="qbtn <?=!$f?'on':''?>">📋 সব অর্ডার</a>
  <a href="?f=pending" class="qbtn <?=$f=='pending'?'on':''?>">⏳ পেন্ডিং</a>
  <a href="?f=processing" class="qbtn <?=$f=='processing'?'on':''?>">⚡ প্রসেসিং</a>
  <a href="?f=submitted" class="qbtn <?=$f=='submitted'?'on':''?>">💳 ভেরিফাই করুন</a>
  <a href="?f=completed" class="qbtn <?=$f=='completed'?'on':''?>">✅ সম্পন্ন</a>
</div>

<div class="sg">
<?php
$si=[['all','📋 মোট',$ta,'var(--txt)'],['pending','⏳ পেন্ডিং',$sc['pending']??0,'var(--gold)'],['processing','⚡ প্রসেসিং',$sc['processing']??0,'var(--blue)'],['completed','✅ সম্পন্ন',$sc['completed']??0,'var(--green)'],['cancelled','❌ বাতিল',$sc['cancelled']??0,'var(--red)']];
foreach($si as [$k,$l,$c,$clr]):
  $href=$k=='all'?'dashboard.php':"?f=$k";
  $pct=$ta>0?round($c/$ta*100):0;
  $on=($k=='all'?!$f:$f==$k);
?>
<a href="<?=$href?>" class="sc <?=$on?'on':''?>">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <span class="sn" style="color:<?=$clr?>"><?=$c?></span>
    <span style="font-size:16px"><?=explode(' ',$l)[0]?></span>
  </div>
  <div class="sl"><?=$l?></div>
  <div class="sbar"><div class="sbarfill" style="width:<?=$pct?>%;background:<?=$clr?>"></div></div>
</a>
<?php endforeach;?>
</div>

<div class="pg">
<?php foreach([['submitted','📩 জমা পড়েছে',$pc['submitted']??0,'var(--gold)'],['unpaid','💤 অনাদায়ী',$pc['unpaid']??0,'var(--dim)'],['verified','💎 ভেরিফাইড',$pc['verified']??0,'var(--green)']] as [$k,$l,$c,$clr]):?>
<a href="?f=<?=$k?>" class="pc <?=$f==$k?'on':''?>">
  <div class="pn" style="color:<?=$clr?>"><?=$c?></div>
  <div class="pl"><?=$l?></div>
</a>
<?php endforeach;?>
</div>

<div class="tb">
  <input class="ts" id="s" placeholder="🔍 নাম, ইমেইল, ফোন, টপিক..." value="<?=htmlspecialchars($q)?>">
  <button class="bta" onclick="srch()">🔍 খুঁজুন</button>
  <?php if($f||$q):?><a href="dashboard.php" class="bt-out">✕ রিসেট</a><?php endif;?>
  <span class="cb"><?=$total?> টি</span>
</div>

<div class="tw">
<?php if(empty($orders)):?>
<div class="empty">📭 কোনো অর্ডার নেই</div>
<?php else:?>
<table class="tbl">
<thead><tr><th>#</th><th>তারিখ</th><th>নাম</th><th>প্ল্যান</th><th>টপিক</th><th>টাকা</th><th>পেমেন্ট</th><th>ডেলিভারি</th><th>স্ট্যাটাস</th><th></th></tr></thead>
<tbody>
<?php foreach($orders as $o):?>
<tr>
  <td class="cid" onclick="view(<?=$o['id']?>)">#<?=$o['id']?></td>
  <td style="font-size:11px;color:var(--dim)" title="<?=$o['created_at']?>"><?=ago($o['created_at'])?></td>
  <td><?=htmlspecialchars(mb_strlen($o['name'])>15?mb_substr($o['name'],0,12).'..':$o['name'])?></td>
  <td style="text-transform:capitalize;font-size:11px"><?=str_replace('-',' ',$o['plan'])?></td>
  <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($o['topic'])?>"><?=htmlspecialchars($o['topic'])?></td>
  <td style="font-weight:700">৳<?=number_format($o['amount']??0)?></td>
  <td><?=badge('payment',$o['payment_status']??'unpaid')?><br><small style="color:var(--dim);font-size:9px"><?=htmlspecialchars($o['transaction_id']??'')?></small></td>
  <td><?=badge('delivery',($o['delivery_status']??'')?:($o['status']=='completed'?'delivered':'not_delivered'))?></td>
  <td><?=badge('status',$o['status'])?></td>
  <td><div class="acts">
    <button class="bs bw" onclick="view(<?=$o['id']?>)">👁️</button>
    <?php if(($o['payment_status']??'')=='submitted'):?><button class="bs bv" onclick="act('v',<?=$o['id']?>)">✅</button><?php endif;?>
    <?php if(($o['payment_status']??'')=='verified'&&($o['delivery_status']??'')!='delivered'):?><button class="bs bd" onclick="act('d',<?=$o['id']?>)">📦</button><?php endif;?>
    <?php if($o['status']=='pending'):?><button class="bs bv" onclick="act('s',<?=$o['id']?>,'processing')">⚡</button><?php endif;?>
    <button class="bs br" onclick="act('x',<?=$o['id']?>)">🗑️</button>
  </div></td>
</tr>
<?php endforeach;?>
</tbody>
</table>
<?php endif;?>
</div>

<?php if($pages>1):?>
<div class="pag">
<?php for($i=1;$i<=min(10,$pages);$i++):
  $pq=['pg'=>$i];if($f)$pq['f']=$f;if($q)$pq['q']=$q;?>
  <a href="?<?=http_build_query($pq)?>" class="plk <?=$i==$pg?'on':''?>"><?=$i?></a>
<?php endfor;?>
</div>
<?php endif;?>

</div>

<div class="mo" id="mo"><div class="md" id="mc"></div></div>
<div class="tc" id="tc"></div>

<script>
function srch(){const v=document.getElementById('s').value.trim();const p=new URLSearchParams(location.search);v?p.set('q',v):p.delete('q');p.delete('pg');location.search=p.toString()}
document.getElementById('s').addEventListener('keypress',e=>{if(e.key=='Enter')srch()})

function act(a,id,extra){
  if(a==='x'&&!confirm('#'+id+' ডিলিট করবেন?'))return;
  const fd=new URLSearchParams();fd.set('a',a);fd.set('id',id);
  if(extra)fd.set('status',extra);
  fetch('dashboard.php',{method:'POST',body:fd}).then(r=>r.json()).then(r=>{t(r.ok);if(r.ok)setTimeout(()=>location.reload(),400)})
}

function view(id){
  document.getElementById('mo').classList.add('on');
  document.getElementById('mc').innerHTML='<p style=padding:30px;color:var(--dim)>⏳ লোড হচ্ছে...</p>';
  fetch('dashboard.php',{method:'POST',body:new URLSearchParams({a:'g',id:id})}).then(r=>r.json()).then(r=>{
    if(!r.data){document.getElementById('mc').innerHTML='<p>❌ পাওয়া যায়নি</p>';return}
    const o=r.data;
    const pls={'youtube-shorts':'YouTube Shorts (৫টি)','facebook-reels':'FB Reels (৫টি)','youtube-full':'YouTube Full'};
    // Build status flow
    let flow=['<span style="background:var(--green)15;color:var(--green);padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">📥 অর্ডার</span>'];
    if(o.payment_status=='submitted')flow.push('<span style="background:var(--accent)15;color:var(--accent);padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">💳 পেমেন্ট জমা</span>');
    else if(o.payment_status=='verified'){flow.push('<span style="background:var(--green)15;color:var(--green);padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">💳 পেমেন্ট জমা</span>');flow.push('<span style="background:var(--green)15;color:var(--green);padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">✅ ভেরিফাই</span>');}
    if(o.delivery_status=='delivered'||o.status=='completed')flow.push('<span style="background:var(--purple)15;color:var(--purple);padding:4px 10px;border-radius:20px;font-size:10px;font-weight:600">📦 ডেলিভার্ড</span>');
    
    document.getElementById('mc').innerHTML=
      '<button class="mx" onclick="cm()">✕</button>'+
      '<h2>📋 অর্ডার #'+o.id+'</h2>'+
      '<div style="margin-bottom:14px;display:flex;gap:4px;flex-wrap:wrap">'+flow.join(' → ')+'</div>'+
      '<div class="dr"><div class="dl">👤 নাম</div><div class="dv">'+esc(o.name)+'</div></div>'+
      '<div class="dr"><div class="dl">📧 ইমেইল</div><div class="dv">'+esc(o.email||'—')+'</div></div>'+
      '<div class="dr"><div class="dl">📱 ফোন</div><div class="dv">'+esc(o.phone)+'</div></div>'+
      '<div class="dr"><div class="dl">💼 প্ল্যান</div><div class="dv" style="text-transform:capitalize">'+esc(pls[o.plan]||o.plan)+' • ৳'+(o.amount||0)+'</div></div>'+
      '<div class="dr"><div class="dl">📝 টপিক</div><div class="dv">'+esc(o.topic||'—')+'</div></div>'+
      '<div class="dr"><div class="dl">💬 মেসেজ</div><div class="mbox">'+esc(o.message||'কোনো মেসেজ নেই')+'</div></div>'+
      '<div class="dr"><div class="dl">💳 পেমেন্ট</div><div class="dv">'+esc(o.transaction_id||'অনাদায়ী')+' ('+esc(o.payment_method||'')+')</div></div>'+
      (o.delivered_by?'<div style="font-size:11px;color:var(--dim);margin-top:8px">📦 ডেলিভার করেছেন: '+esc(o.delivered_by)+'</div>':'')+
      '<div style="margin-top:14px;display:flex;gap:6px;flex-wrap:wrap">'+
        (o.payment_status=='submitted'?'<button class="bta" onclick="act(\'v\','+o.id+')">✅ পেমেন্ট ভেরিফাই</button>':'')+
        ((o.payment_status=='verified')&&(o.delivery_status!='delivered'&&o.status!='completed')?'<button class="bta" style="background:var(--purple)" onclick="act('d','+o.id+')">📦 ডেলিভারি সম্পন্ন</button>':'')+
        '<button class="bta" style="background:var(--red)" onclick="act('x','+o.id+')">🗑️ ডিলিট</button>'+
      '</div>';
  })
}
function cm(){document.getElementById('mo').classList.remove('on')}
document.getElementById('mo').addEventListener('click',e=>{if(e.target===document.getElementById('mo'))cm()})
document.addEventListener('keydown',e=>{if(e.key=='Escape')cm()})

function t(ok){const e=document.createElement('div');e.className='tst '+(ok?'to':'te');e.textContent=ok?'✅ সফল!':'❌ ইরর!';document.getElementById('tc').appendChild(e);setTimeout(()=>e.remove(),2500)}
function esc(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML}
</script>
</body>
</html>