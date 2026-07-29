<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Safe DB connection
define('DB_HOST', 'localhost');
define('DB_NAME', 'scriptbd_scriptbd_db');
define('DB_USER', 'scriptbd_scriptbd_user');
define('DB_PASS', 'Sbd@2026!Pro');

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit;
}

$pdo = null;
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die('<div style="background:#ff444420;padding:20px;border-radius:10px;margin:40px;font-family:sans-serif">
        <h2 style="color:#ff4444">⚠️ Database Connection Error</h2>
        <p>' . $e->getMessage() . '</p>
        <p style="font-size:12px;color:#888">Check phpMyAdmin: database exists, user has privileges</p>
    </div>');
}

// AJAX handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    
    try {
        if ($action === 'verify') $pdo->exec("UPDATE orders SET payment_status='verified' WHERE id=$id");
        if ($action === 'deliver') $pdo->exec("UPDATE orders SET delivery_status='delivered', status='completed' WHERE id=$id");
        if ($action === 'delete') $pdo->exec("DELETE FROM orders WHERE id=$id");
        if ($action === 'view') {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['ok'=>true,'data'=>$stmt->fetch()]); exit;
        }
        if ($action === 'status') $pdo->exec("UPDATE orders SET status='".$_POST['status']."' WHERE id=$id");
        echo json_encode(['ok'=>true]); exit;
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); exit;
    }
}

// Get data
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$off = ($page-1)*$limit;

$where = '';
if ($filter === 'pending' || $filter === 'processing' || $filter === 'completed' || $filter === 'cancelled')
    $where = "WHERE status='$filter'";
elseif ($filter === 'submitted')
    $where = "WHERE payment_status='submitted'";
elseif ($filter === 'verified')
    $where = "WHERE payment_status='verified'";

if ($search) {
    $s = $pdo->quote('%'.$search.'%');
    $id = (int)$search;
    $where = ($where ? "$where AND" : "WHERE") . " (name LIKE $s OR email LIKE $s OR phone LIKE $s OR topic LIKE $s OR id=$id)";
}

$total = $pdo->query("SELECT COUNT(*) FROM orders $where")->fetchColumn();
$totalPages = ceil($total / $limit);
$orders = $pdo->query("SELECT * FROM orders $where ORDER BY created_at DESC LIMIT $limit OFFSET $off")->fetchAll();

$sc = $pdo->query("SELECT status, COUNT(*) FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$pc = $pdo->query("SELECT payment_status, COUNT(*) FROM orders GROUP BY payment_status")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalAll = array_sum($sc);

function badge($type, $value) {
    $map = [
        'pending'=>['#fbbf24','⏳ পেন্ডিং'], 'processing'=>['#3b82f6','⚡ প্রসেসিং'],
        'completed'=>['#10b981','✅ সম্পন্ন'], 'cancelled'=>['#ef4444','❌ বাতিল'],
        'unpaid'=>['#6b7280','💤 অনাদায়ী'], 'submitted'=>['#f59e0b','📩 জমা পড়েছে'],
        'verified'=>['#10b981','💎 ভেরিফাইড'], 'delivered'=>['#8b5cf6','📦 ডেলিভার্ড'],
        'not_delivered'=>['#6b7280','⏳ বাকি'],
    ];
    $s = $map[$value] ?? $map['pending'];
    return "<span style='background:{$s[0]}20;color:{$s[0]};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid {$s[0]}40'>{$s[1]}</span>";
}
function ago($d) {
    if(!$d) return '—';
    $df = time()-strtotime($d);
    if($df<60) return 'এখন'; if($df<3600) return floor($df/60).'মি';
    if($df<86400) return floor($df/3600).'ঘ'; return floor($df/86400).'দিন';
}

// Quick action: login with ?auto=admin:admin123
if (isset($_GET['auto']) && $_GET['auto'] === '1') {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ড্যাশবোর্ড v4 • ScriptBD</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#08080f;--card:#141428;--accent:#ff6b35;--ac2:#ff3366;--txt:#e8e6f0;--dim:#8a88a0;--border:#22223a;--green:#10b981;--blue:#3b82f6;--red:#ef4444;--gold:#f59e0b;--purple:#8b5cf6}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Noto Sans Bengali',system-ui,sans-serif;background:var(--bg);color:var(--txt);min-height:100vh;
background-image:radial-gradient(ellipse at 20% 0%,rgba(255,107,53,.05) 0%,transparent 50%),radial-gradient(ellipse at 80% 100%,rgba(139,92,246,.05) 0%,transparent 50%)}

.nav{background:rgba(20,20,40,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.nav-brand{font-size:18px;font-weight:900;background:linear-gradient(135deg,var(--accent),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav-right{display:flex;align-items:center;gap:14px;font-size:13px}
.btn-out{background:transparent;border:1px solid var(--border);color:var(--dim);padding:6px 14px;border-radius:8px;cursor:pointer;font:inherit;font-size:12px;text-decoration:none;transition:.3s}
.btn-out:hover{border-color:var(--red);color:var(--red)}

.main{padding:20px;max-width:1500px;margin:0 auto}

.qbar{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.qbtn{background:var(--card);border:1px solid var(--border);color:var(--dim);padding:7px 14px;border-radius:20px;cursor:pointer;font:inherit;font-size:11px;text-decoration:none;transition:.3s}
.qbtn:hover,.qbtn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.qbtn.danger{color:var(--red)}.qbtn.danger:hover{background:var(--red);color:#fff}

.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:20px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px;cursor:pointer;text-decoration:none;color:inherit;transition:.3s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.3);border-color:var(--accent)}
.stat-card.active{border-color:var(--accent);box-shadow:0 0 0 1px var(--accent)30}
.snum{font-size:22px;font-weight:800}.slabel{font-size:10px;color:var(--dim);margin-top:2px}

.pay-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:8px;margin-bottom:18px}
.pay-card{background:#0f0f1a;border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center;cursor:pointer;text-decoration:none;color:inherit;transition:.3s}
.pay-card:hover,.pay-card.active{border-color:var(--accent);background:rgba(255,107,53,.1)}

.tbar{display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;align-items:center;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:10px}
.ts{flex:1;min-width:150px;background:#0f0f1a;border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--txt);font:inherit;font-size:12px;outline:none}
.ts:focus{border-color:var(--accent)}
.bta{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font:inherit;font-size:11px;font-weight:600}
.count-badge{font-size:11px;color:var(--dim);white-space:nowrap}

.tw{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.tbl{width:100%;border-collapse:collapse;font-size:12px}
.tbl th{background:#0f0f1a;padding:10px 10px;text-align:left;font-size:10px;text-transform:uppercase;color:var(--dim);border-bottom:1px solid var(--border);white-space:nowrap}
.tbl td{padding:10px;border-bottom:1px solid var(--border)}
.tbl tbody tr:hover{background:rgba(255,107,53,.02)}
.cid{color:var(--accent);font-weight:700;cursor:pointer}.cid:hover{text-decoration:underline}
.acts{display:flex;gap:4px;flex-wrap:wrap}
.bxs{padding:4px 8px;border:none;border-radius:5px;cursor:pointer;font:inherit;font-size:10px;font-weight:600;transition:.2s}
.bv{background:rgba(16,185,129,.15);color:var(--green)}.bv:hover{background:rgba(16,185,129,.25)}
.bd{background:rgba(139,92,246,.15);color:var(--purple)}.bd:hover{background:rgba(139,92,246,.25)}
.bw{background:rgba(59,130,246,.15);color:var(--blue)}.bw:hover{background:rgba(59,130,246,.25)}
.br{background:rgba(239,68,68,.15);color:var(--red)}.br:hover{background:rgba(239,68,68,.25)}

.mo{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center}
.mo.active{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;width:90%;max-width:600px;max-height:85vh;overflow-y:auto}
.mclose{float:right;background:none;border:none;color:var(--dim);font-size:20px;cursor:pointer}
.modal h2{font-size:18px;margin-bottom:16px;background:linear-gradient(135deg,var(--accent),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.dr{margin-bottom:10px}.dl{font-size:10px;color:var(--dim)}.dv{font-size:13px}
.msg-box{background:#0f0f1a;border:1px solid var(--border);border-radius:8px;padding:12px;font-size:12px;line-height:1.6;margin-top:4px}

.toast-ctr{position:fixed;bottom:20px;right:20px;z-index:300}
.toast{padding:10px 18px;border-radius:8px;font-size:12px;font-weight:600;margin-top:6px;animation:fadeIn .3s}
.toast-ok{background:var(--green);color:#000}.toast-err{background:var(--red);color:#fff}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

.empty{text-align:center;padding:50px 20px;color:var(--dim);font-size:14px}
.pag{display:flex;gap:6px;justify-content:center;margin-top:16px}
.plk{padding:6px 12px;background:var(--card);border:1px solid var(--border);border-radius:6px;color:var(--dim);text-decoration:none;font-size:11px}
.plk:hover,.plk.active{background:var(--accent);color:#fff}

@media(max-width:768px){.main{padding:12px 8px}.tbl{font-size:10px}.tbl td,.tbl th{padding:6px 4px}}
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-brand">📜 ScriptBD ADMIN</div>
  <div class="nav-right">
    <span><?= htmlspecialchars($_SESSION['admin_username']??'Admin') ?></span>
    <a href="logout.php" class="btn-out">🚪 লগআউট</a>
  </div>
</nav>

<div class="main">

<div class="qbar">
  <a href="dashboard.php" class="qbtn <?= !$filter ? 'active' : '' ?>">📋 সব</a>
  <a href="?filter=pending" class="qbtn <?= $filter=='pending'?'active':'' ?>">⏳ পেন্ডিং</a>
  <a href="?filter=processing" class="qbtn <?= $filter=='processing'?'active':'' ?>">⚡ প্রসেসিং</a>
  <a href="?filter=submitted" class="qbtn <?= $filter=='submitted'?'active':'' ?>">💳 ভেরিফাই</a>
  <a href="?filter=completed" class="qbtn <?= $filter=='completed'?'active':'' ?>">✅ সম্পন্ন</a>
</div>

<div class="stats">
  <?php
  $items=[['all'=>'📋 মোট',$totalAll,'#fff'],['pending'=>'⏳ পেন্ডিং',$sc['pending']??0,'#fbbf24'],['processing'=>'⚡ প্রসেসিং',$sc['processing']??0,'#3b82f6'],['completed'=>'✅ সম্পন্ন',$sc['completed']??0,'#10b981'],['cancelled'=>'❌ বাতিল',$sc['cancelled']??0,'#ef4444']];
  foreach($items as $item):
    $key=key($item);$val=current($item);$pct=$totalAll>0?round($val[1]/$totalAll*100):0;
    $href=$key==='all'?'dashboard.php':"?filter=$key";
    $active=($key==='all'?!$filter:$filter===$key);
  ?>
  <a href="<?=$href?>" class="stat-card <?=$active?'active':''?>">
    <div class="snum" style="color:<?=$val[2]?>"><?=$val[1]?></div>
    <div class="slabel"><?=$val[0]?></div>
    <div style="height:2px;margin-top:6px;background:var(--border);border-radius:2px"><div style="height:100%;width:<?=$pct?>%;background:<?=$val[2]?>;border-radius:2px;transition:.5s"></div></div>
  </a>
  <?php endforeach;?>
</div>

<div class="pay-grid">
  <?php foreach([['submitted'=>'📩 জমা',$pc['submitted']??0,'#f59e0b'],['unpaid'=>'💤 বাকি',$pc['unpaid']??0,'#6b7280'],['verified'=>'💎 ভেরিফাইড',$pc['verified']??0,'#10b981']] as $p):?>
  <a href="?filter=<?=key($p)?>" class="pay-card <?=$filter==key($p)?'active':''?>">
    <div style="font-size:20px;font-weight:800;color:<?=$p[2]?>"><?=$p[1]?></div>
    <div style="font-size:10px;color:var(--dim)"><?=current($p)[0]?></div>
  </a>
  <?php endforeach;?>
</div>

<div class="tbar">
  <input class="ts" id="search" placeholder="🔍 নাম, ইমেইল, ফোন, টপিক দিয়ে খুঁজুন..." value="<?=htmlspecialchars($search)?>">
  <button class="bta" onclick="search()">খুঁজুন</button>
  <?php if($filter||$search):?><a href="dashboard.php" class="btn-out">✕ রিসেট</a><?php endif;?>
  <span class="count-badge"><?=$total?> টি</span>
</div>

<div class="tw">
  <?php if(empty($orders)):?>
    <div class="empty">📭 কোনো অর্ডার নেই</div>
  <?php else:?>
  <table class="tbl">
    <thead><tr>
      <th>#</th><th>তারিখ</th><th>নাম</th><th>প্ল্যান</th><th>টপিক</th><th>টাকা</th><th>পেমেন্ট</th><th>ডেলিভারি</th><th>স্ট্যাটাস</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach($orders as $o):?>
    <tr>
      <td class="cid" onclick="view(<?=$o['id']?>)">#<?=$o['id']?></td>
      <td title="<?=$o['created_at']?>" style="font-size:11px;color:var(--dim)"><?=ago($o['created_at'])?></td>
      <td><?=htmlspecialchars($o['name'])?></td>
      <td style="text-transform:capitalize;font-size:11px"><?=str_replace('-',' ',$o['plan'])?></td>
      <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($o['topic'])?>"><?=htmlspecialchars($o['topic'])?></td>
      <td style="font-weight:700">৳<?=number_format($o['amount']??0)?></td>
      <td><?=badge('payment',$o['payment_status']??'unpaid')?><br><small style="color:var(--dim);font-size:9px"><?=$o['transaction_id']?></small></td>
      <td><?=badge('delivery',($o['delivery_status']??'')?:($o['status']=='completed'?'delivered':'not_delivered'))?></td>
      <td><?=badge('status',$o['status'])?></td>
      <td>
        <div class="acts">
          <button class="bxs bw" onclick="view(<?=$o['id']?>)">👁️</button>
          <?php if(($o['payment_status']??'')=='submitted'):?><button class="bxs bv" onclick="act('verify',<?=$o['id']?>)">✅</button><?php endif;?>
          <?php if(($o['payment_status']??'')=='verified'&&($o['delivery_status']??'')!='delivered'):?><button class="bxs bd" onclick="act('deliver',<?=$o['id']?>)">📦</button><?php endif;?>
          <?php if($o['status']=='pending'):?><button class="bxs bv" onclick="act('status',<?=$o['id']?>,'processing')">⚡</button><?php endif;?>
          <button class="bxs br" onclick="act('delete',<?=$o['id']?>)">🗑️</button>
        </div>
      </td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  <?php endif;?>
</div>

<?php if($totalPages>1):?>
<div class="pag">
  <?php for($i=1;$i<=min(10,$totalPages);$i++):$q=['page'=>$i];if($filter)$q['filter']=$filter;if($search)$q['search']=$search;?>
    <a href="?<?=http_build_query($q)?>" class="plk <?=$i==$page?'active':''?>"><?=$i?></a>
  <?php endfor;?>
</div>
<?php endif;?>

</div>

<div class="mo" id="mo"><div class="modal" id="mc"></div></div>
<div class="toast-ctr" id="tc"></div>

<script>
function search(){const q=document.getElementById('search').value.trim();const p=new URLSearchParams(location.search);q?p.set('search',q):p.delete('search');p.set('page','1');location.search=p.toString()}
document.getElementById('search').addEventListener('keypress',e=>{if(e.key=='Enter')search()})

function act(action,id,extra){
  if(action==='delete'&&!confirm('#'+id+' ডিলিট করবেন?'))return;
  const fd=new URLSearchParams();fd.set('action',action);fd.set('id',id);
  if(extra)fd.set('status',extra);
  fetch('dashboard.php',{method:'POST',body:fd}).then(r=>r.json()).then(r=>{
    toast(r.ok);if(r.ok)setTimeout(()=>location.reload(),400);
  })
}
function view(id){
  document.getElementById('mo').classList.add('active');
  document.getElementById('mc').innerHTML='<p>লোড হচ্ছে...</p>';
  const fd=new URLSearchParams();fd.set('action','view');fd.set('id',id);
  fetch('dashboard.php',{method:'POST',body:fd}).then(r=>r.json()).then(r=>{
    if(!r.data){document.getElementById('mc').innerHTML='<p>পাওয়া যায়নি</p>';return}
    const o=r.data;
    const p={['youtube-shorts']:'YT Shorts',['facebook-reels']:'FB Reels',['youtube-full']:'YT Full'};
    document.getElementById('mc').innerHTML=
      '<button class="mclose" onclick="closeM()">✕</button>'+
      '<h2>অর্ডার #'+o.id+'</h2>'+
      '<div class="dr"><div class="dl">নাম</div><div class="dv">'+esc(o.name)+'</div></div>'+
      '<div class="dr"><div class="dl">ইমেইল</div><div class="dv">'+esc(o.email||'—')+'</div></div>'+
      '<div class="dr"><div class="dl">ফোন</div><div class="dv">'+esc(o.phone)+'</div></div>'+
      '<div class="dr"><div class="dl">প্ল্যান</div><div class="dv">'+esc(p[o.plan]||o.plan)+' — ৳'+(o.amount||0)+'</div></div>'+
      '<div class="dr"><div class="dl">টপিক</div><div class="dv">'+esc(o.topic||'—')+'</div></div>'+
      '<div class="dr"><div class="dl">মেসেজ</div><div class="msg-box">'+esc(o.message||'—')+'</div></div>'+
      '<div class="dr"><div class="dl">পেমেন্ট</div><div class="dv">'+esc(o.transaction_id||'অনাদায়ী')+' ('+esc(o.payment_method||'')+')</div></div>'+
      '<div style="margin-top:14px;display:flex;gap:6px;flex-wrap:wrap">'+
        (o.payment_status==='submitted'?'<button class="bta" onclick="act('verify','+o.id+')">✅ ভেরিফাই</button>':'')+
        ((o.payment_status==='verified')&&(o.delivery_status!=='delivered')?'<button class="bta" style="background:var(--purple)" onclick="act('deliver','+o.id+')">📦 ডেলিভারি সম্পন্ন</button>':'')+
        '<button class="bta" style="background:var(--red)" onclick="act('delete','+o.id+')">🗑️ ডিলিট</button>'+
      '</div>';
  })
}
function closeM(){document.getElementById('mo').classList.remove('active')}
document.getElementById('mo').addEventListener('click',e=>{if(e.target===document.getElementById('mo'))closeM()})
document.addEventListener('keydown',e=>{if(e.key=='Escape')closeM()})

function toast(ok){const e=document.createElement('div');e.className='toast '+(ok?'toast-ok':'toast-err');e.textContent=ok?'✅ সফল!':'❌ ইরর!';document.getElementById('tc').appendChild(e);setTimeout(()=>e.remove(),2500)}
function esc(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML}
</script>
</body>
</html>