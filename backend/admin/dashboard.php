<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit;
}
require_once __DIR__ . '/../config/database.php';

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = getDBConnection();
        $action = $_POST['action'];
        $id = (int)($_POST['id'] ?? 0);
        
        if ($action === 'verify_payment') {
            $pdo->prepare("UPDATE orders SET payment_status='verified', verified_at=NOW(), updated_at=NOW() WHERE id=:id")->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'✅ পেমেন্ট ভেরিফাই হয়েছে!']); exit;
        }
        if ($action === 'mark_delivered') {
            $admin = $_SESSION['admin_username'] ?? 'admin';
            $pdo->prepare("UPDATE orders SET delivery_status='delivered', delivery_date=NOW(), status='completed', delivered_by=:a, updated_at=NOW() WHERE id=:id")->execute([':id'=>$id,':a'=>$admin]);
            echo json_encode(['success'=>true,'message'=>'📦 ডেলিভারি সম্পন্ন!']); exit;
        }
        if ($action === 'save_notes') {
            $notes = trim($_POST['notes'] ?? '');
            $pdo->prepare("UPDATE orders SET admin_notes=:n, updated_at=NOW() WHERE id=:id")->execute([':id'=>$id,':n'=>$notes]);
            echo json_encode(['success'=>true,'message'=>'💾 সেভ হয়েছে']); exit;
        }
        if ($action === 'update_status') {
            $status = trim($_POST['status'] ?? '');
            if (!in_array($status,['pending','processing','completed','cancelled'])){echo json_encode(['success'=>false]);exit;}
            $pdo->prepare("UPDATE orders SET status=:s, updated_at=NOW() WHERE id=:id")->execute([':s'=>$status,':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'স্ট্যাটাস আপডেট']); exit;
        }
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM orders WHERE id=:id")->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'ডিলিট হয়েছে']); exit;
        }
        if ($action === 'get_order') {
            $o = $pdo->prepare("SELECT * FROM orders WHERE id=:id");
            $o->execute([':id'=>$id]);
            $order = $o->fetch();
            echo json_encode(['success'=>(bool)$order, 'data'=>$order]); exit;
        }
        if ($action === 'bulk_verify') {
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $stmt = $pdo->prepare("UPDATE orders SET payment_status='verified', verified_at=NOW(), updated_at=NOW() WHERE id=:id AND payment_status='submitted'");
            $c = 0; foreach ($ids as $i) { $stmt->execute([':id'=>(int)$i]); $c += $stmt->rowCount(); }
            echo json_encode(['success'=>true,'message'=>"$c টি ভেরিফাই হয়েছে"]); exit;
        }
        if ($action === 'bulk_delete') {
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id=:id");
            $c = 0; foreach ($ids as $i) { $stmt->execute([':id'=>(int)$i]); $c++; }
            echo json_encode(['success'=>true,'message'=>"$c টি ডিলিট হয়েছে"]); exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
    }
}

// Fetch Data
$statusFilter = $_GET['status'] ?? '';
$pFilter = $_GET['payment'] ?? '';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page-1)*$limit;

try {
    $pdo = getDBConnection();
    $where = []; $params = [];
    if ($statusFilter && in_array($statusFilter,['pending','processing','completed','cancelled'])) {
        $where[]='o.status=:s'; $params[':s']=$statusFilter;
    }
    if ($pFilter && in_array($pFilter,['unpaid','submitted','verified','rejected'])) {
        $where[]='o.payment_status=:p'; $params[':p']=$pFilter;
    }
    if ($search) {
        $where[]='(o.name LIKE :q OR o.email LIKE :q2 OR o.phone LIKE :q3 OR o.topic LIKE :q4 OR o.id=:q5 OR o.transaction_id LIKE :q6)';
        $s='%'.$search.'%';
        $params[':q']=$s; $params[':q2']=$s; $params[':q3']=$s; $params[':q4']=$s; $params[':q6']=$s;
        $params[':q5']=is_numeric($search)?(int)$search:0;
    }
    $whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';
    
    // Use simpler queries that work with any column set
    $total = $pdo->query("SELECT COUNT(*) FROM orders o $whereSQL")->fetchColumn();
    $totalPages = ceil($total/$limit);
    
    $sortCols = ['id'=>'o.id','created_at'=>'o.created_at','name'=>'o.name','amount'=>'o.amount','status'=>'o.status'];
    $sortCol = $sortCols[$sort] ?? 'o.created_at';
    $orderDir = $order==='ASC'?'ASC':'DESC';
    
    $stmt = $pdo->prepare("SELECT * FROM orders o $whereSQL ORDER BY $sortCol $orderDir LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    $sc = $pdo->query("SELECT status, COUNT(*) FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $pc = $pdo->query("SELECT payment_status, COUNT(*) FROM orders GROUP BY payment_status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalAll = array_sum($sc);
    
    // Safe revenue (try, use 0 if column missing)
    try { $revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status='verified'")->fetchColumn(); } catch(Exception $e) { $revenue = 0; }
    try { $todayRev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status='verified' AND DATE(created_at)=CURDATE()")->fetchColumn(); } catch(Exception $e) { $todayRev = 0; }
    try { $todayOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn(); } catch(Exception $e) { $todayOrders = 0; }
    
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    $orders = []; $sc = []; $pc = []; $totalAll = 0; $totalPages = 0; $revenue = 0; $todayRev = 0; $todayOrders = 0;
}

function badge($type,$value){
    $map=[
        'pending'=>['#fbbf24','⏳ পেন্ডিং'],
        'processing'=>['#3b82f6','⚡ প্রসেসিং'],
        'completed'=>['#10b981','✅ সম্পন্ন'],
        'cancelled'=>['#ef4444','❌ বাতিল'],
        'unpaid'=>['#6b7280','💤 অনাদায়ী'],
        'submitted'=>['#f59e0b','📩 জমা পড়েছে'],
        'verified'=>['#10b981','💎 ভেরিফাইড'],
        'rejected'=>['#ef4444','🚫 রিজেক্ট'],
        'delivered'=>['#8b5cf6','📦 ডেলিভার্ড'],
        'not_delivered'=>['#6b7280','⏳ বাকি'],
    ];
    $s=$map[$value]??$map['pending'];
    return "<span style='display:inline-flex;align-items:center;gap:4px;background:{$s[0]}15;color:{$s[0]};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid {$s[0]}30'>{$s[1]}</span>";
}
function timeAgo($dt){
    if(!$dt)return '—';
    $diff=time()-strtotime($dt);
    if($diff<60)return 'এইমাত্র';
    if($diff<3600)return floor($diff/60).'মি';
    if($diff<86400)return floor($diff/3600).'ঘ';
    if($diff<604800)return floor($diff/86400).'দিন';
    return date('d/m/y',strtotime($dt));
}
function sortLink($col,$label,$current,$order){
    $dir=($current===$col&&$order==='ASC')?'DESC':'ASC';
    $ico=$current===$col?($order==='ASC'?' ▲':' ▼'):'';
    $q=$_GET;$q['sort']=$col;$q['order']=$dir;
    return '<a href="?'.http_build_query($q).'" style="color:inherit;text-decoration:none">'.$label.$ico.'</a>';
}
$adminName=htmlspecialchars($_SESSION['admin_username']??'Admin');
$adminInitial=mb_substr($_SESSION['admin_username']??'A',0,1);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ড্যাশবোর্ড • স্ক্রিপ্টবিডি</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#08080f;--sbg:#0f0f1a;--card:#141428;--card-hover:#1a1a35;--accent:#ff6b35;--accent2:#ff3366;--accent-glow:rgba(255,107,53,.15);--txt:#e8e6f0;--dim:#8a88a0;--border:#22223a;--green:#10b981;--blue:#3b82f6;--red:#ef4444;--gold:#f59e0b;--purple:#8b5cf6;--radius:14px;--radius-sm:10px}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Noto Sans Bengali',system-ui,sans-serif;background:var(--bg);color:var(--txt);min-height:100vh;background-image:radial-gradient(ellipse at 20% 0%,rgba(255,107,53,.04) 0%,transparent 50%),radial-gradient(ellipse at 80% 100%,rgba(139,92,246,.04) 0%,transparent 50%)}
.bg-orb{position:fixed;border-radius:50%;filter:blur(100px);opacity:.03;z-index:0;pointer-events:none}
.bg-orb-1{width:600px;height:600px;background:var(--accent);top:-200px;right:-200px;animation:drift1 20s infinite}
.bg-orb-2{width:400px;height:400px;background:var(--purple);bottom:-100px;left:-100px;animation:drift2 25s infinite}
@keyframes drift1{0%,100%{transform:translate(0,0)}33%{transform:translate(-100px,50px)}66%{transform:translate(50px,-30px)}}
@keyframes drift2{0%,100%{transform:translate(0,0)}50%{transform:translate(80px,-40px)}}
.app{position:relative;z-index:1}

/* Navbar */
.nav{background:rgba(20,20,40,.85);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.nav-l{display:flex;align-items:center;gap:12px}
.nav-logo{font-size:20px;font-weight:900;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-badge{background:var(--accent);color:#fff;font-size:10px;padding:2px 8px;border-radius:20px;font-weight:600}
.nav-r{display:flex;align-items:center;gap:16px}
.nav-user{display:flex;align-items:center;gap:10px;font-size:13px}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:#fff;box-shadow:0 0 20px var(--accent-glow)}
.nav-time{font-size:11px;color:var(--dim)}
.btn-out{background:transparent;border:1px solid var(--border);color:var(--dim);padding:7px 16px;border-radius:8px;cursor:pointer;font:inherit;font-size:12px;text-decoration:none;transition:.3s}
.btn-out:hover{border-color:var(--red);color:var(--red)}
.btn-out.gh{border-color:var(--accent)50;color:var(--accent)}
.btn-out.gh:hover{background:var(--accent)10}

.main{padding:28px;max-width:1600px;margin:0 auto}

/* Quick Bar */
.qbar{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;align-items:center}
.qbtn{background:var(--card);border:1px solid var(--border);color:var(--dim);padding:8px 16px;border-radius:20px;cursor:pointer;font:inherit;font-size:12px;transition:.3s;display:flex;align-items:center;gap:6px;text-decoration:none}
.qbtn:hover{border-color:var(--accent);color:var(--txt);background:var(--card-hover)}
.qbtn.active{background:var(--accent)15;color:var(--accent);border-color:var(--accent)}
.qbtn.danger{color:var(--red);border-color:var(--red)30}
.qbtn.danger:hover{background:var(--red)15}

/* Revenue Cards */
.rcards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.rcard{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;position:relative;overflow:hidden;transition:all .3s;cursor:default}
.rcard::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--accent2));opacity:0;transition:.3s}
.rcard:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,0,0,.3);border-color:var(--accent)40}
.rcard:hover::before{opacity:1}
.ricon{font-size:28px;margin-bottom:8px}
.rval{font-size:28px;font-weight:900}
.rlabel{font-size:11px;color:var(--dim);margin-top:4px;text-transform:uppercase;letter-spacing:.5px}
.rcard.total .rval{color:var(--txt)}
.rcard.rev .rval{color:var(--green)}
.rcard.today .rval{color:var(--accent)}

/* Stats */
.sgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:20px}
.scard{background:var(--card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;cursor:pointer;transition:.3s;text-decoration:none;color:inherit;position:relative}
.scard:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.2)}
.scard.active{border-color:var(--accent);box-shadow:0 0 0 1px var(--accent)30}
.snum{font-size:24px;font-weight:800}
.slabel{font-size:10px;color:var(--dim);margin-top:2px;text-transform:uppercase;letter-spacing:.5px}
.sbar{height:2px;margin-top:8px;border-radius:2px;background:var(--border);overflow:hidden}
.sbarfill{height:100%;border-radius:2px;transition:width .6s}

/* Payment Grid */
.pgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;margin-bottom:20px}
.pcard{background:var(--sbg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;text-align:center;cursor:pointer;transition:.3s;text-decoration:none;color:inherit}
.pcard:hover{border-color:var(--accent)40;transform:translateY(-1px)}
.pcard.active{border-color:var(--accent);background:var(--accent)10}
.pn{font-size:22px;font-weight:800}
.pl{font-size:10px;color:var(--dim);margin-top:2px}

/* Toolbar */
.tbar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center;background:var(--card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px}
.ts{flex:1;min-width:180px;background:var(--sbg);border:1px solid var(--border);border-radius:8px;padding:9px 14px;color:var(--txt);font:inherit;font-size:13px;outline:none}
.ts:focus{border-color:var(--accent);box-shadow:0 0 0 2px var(--accent)15}
.ts::placeholder{color:var(--dim);font-size:12px}
.bta{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;padding:9px 18px;border-radius:8px;cursor:pointer;font:inherit;font-size:12px;font-weight:700;transition:.3s;white-space:nowrap;box-shadow:0 4px 14px var(--accent-glow)}
.bta:hover{transform:translateY(-1px);box-shadow:0 6px 20px var(--accent-glow)}
.count{font-size:11px;color:var(--dim);white-space:nowrap}

/* Selection Bar */
.selbar{display:none;align-items:center;gap:10px;padding:10px 14px;background:var(--accent)08;border:1px solid var(--accent)30;border-radius:var(--radius-sm);margin-bottom:14px}
.selbar.active{display:flex}
.selc{font-size:13px;font-weight:600;color:var(--accent)}

/* Table */
.tw{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{background:var(--sbg);padding:13px 12px;text-align:left;font-size:10px;text-transform:uppercase;color:var(--dim);letter-spacing:.8px;border-bottom:1px solid var(--border);white-space:nowrap;font-weight:600}
.tbl td{padding:12px;border-bottom:1px solid var(--border);vertical-align:middle}
.tbl tbody tr{transition:.2s}
.tbl tbody tr:hover{background:rgba(255,107,53,.02)}
.tbl tbody tr.selected{background:var(--accent)08}
.tbl tbody tr:last-child td{border-bottom:none}
.cid{color:var(--accent);font-weight:700;cursor:pointer}
.cid:hover{text-decoration:underline}
.ctopic{max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.camt{font-weight:700}
.ctime{font-size:11px;color:var(--dim);white-space:nowrap}
.cb{width:16px;height:16px;accent-color:var(--accent);cursor:pointer}

/* Actions */
.acts{display:flex;gap:5px;flex-wrap:wrap}
.bxs{padding:5px 10px;border:none;border-radius:6px;cursor:pointer;font:inherit;font-size:11px;font-weight:600;transition:.2s;white-space:nowrap}
.bxs:active{transform:scale(.94)}
.bv{background:rgba(16,185,129,.12);color:var(--green);border:1px solid rgba(16,185,129,.2)}
.bv:hover{background:rgba(16,185,129,.2)}
.bd{background:rgba(139,92,246,.12);color:var(--purple);border:1px solid rgba(139,92,246,.2)}
.bd:hover{background:rgba(139,92,246,.2)}
.bw{background:rgba(59,130,246,.12);color:var(--blue);border:1px solid rgba(59,130,246,.2)}
.bw:hover{background:rgba(59,130,246,.2)}
.br{background:rgba(239,68,68,.12);color:var(--red);border:1px solid rgba(239,68,68,.2)}
.br:hover{background:rgba(239,68,68,.2)}
.bq{background:var(--sbg);border:1px solid var(--border);color:var(--dim);font-size:10px;padding:4px 8px;border-radius:6px;cursor:pointer}
.bq:hover{border-color:var(--accent);color:var(--txt)}

.empty{text-align:center;padding:60px 24px}
.eicon{font-size:48px;margin-bottom:12px;opacity:.5}
.etitle{font-size:16px;font-weight:600;margin-bottom:6px}
.esub{font-size:12px;color:var(--dim)}

/* Modal */
.mo{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);z-index:200;align-items:center;justify-content:center}
.mo.active{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:28px;width:92%;max-width:680px;max-height:85vh;overflow-y:auto;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.5);animation:mi .3s}
@keyframes mi{from{opacity:0;transform:scale(.95) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
.mclose{position:absolute;top:12px;right:12px;width:32px;height:32px;border-radius:50%;border:1px solid var(--border);background:var(--sbg);color:var(--dim);cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:.2s}
.mclose:hover{border-color:var(--red);color:var(--red)}
.modal h2{font-size:20px;margin-bottom:20px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.mgrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.dr{margin-bottom:12px}
.dl{font-size:10px;text-transform:uppercase;color:var(--dim);letter-spacing:.5px;margin-bottom:3px}
.dv{font-size:14px;font-weight:500}
.mbox{background:var(--sbg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-top:4px;font-size:13px;line-height:1.7;white-space:pre-wrap;max-height:150px;overflow-y:auto}
.sflow{display:flex;gap:6px;margin:14px 0;flex-wrap:wrap}
.sstep{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;background:var(--sbg);border:1px solid var(--border);color:var(--dim)}
.sstep.done{background:var(--green)15;color:var(--green);border-color:var(--green)30}
.sstep.now{background:var(--accent)15;color:var(--accent);border-color:var(--accent)}
.macts{display:flex;gap:8px;margin-top:18px;padding-top:16px;border-top:1px solid var(--border);flex-wrap:wrap}
.btn{color:#fff;border:none;padding:9px 18px;border-radius:8px;cursor:pointer;font:inherit;font-size:13px;font-weight:600;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.bp{background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 4px 14px var(--accent-glow)}
.bp:hover{transform:translateY(-1px)}
.bg{background:var(--green)}.bpp{background:var(--purple)}.bred{background:var(--red)}.bb{background:var(--blue)}
.na{width:100%;background:var(--sbg);border:1px solid var(--border);border-radius:8px;color:var(--txt);padding:10px;font:inherit;font-size:12px;resize:vertical;min-height:60px;outline:none}
.na:focus{border-color:var(--accent)}
.ss{background:var(--sbg);border:1px solid var(--border);color:var(--txt);padding:6px 10px;border-radius:6px;font:inherit;font-size:11px;cursor:pointer}

/* Pagination */
.pag{display:flex;gap:6px;justify-content:center;margin-top:22px;flex-wrap:wrap;align-items:center}
.plk{padding:8px 14px;background:var(--card);border:1px solid var(--border);border-radius:8px;color:var(--dim);text-decoration:none;font-size:12px;transition:.2s}
.plk:hover{border-color:var(--accent);color:var(--txt)}
.plk.active{background:var(--accent);color:#fff;border-color:var(--accent);font-weight:700}
.pinfo{font-size:11px;color:var(--dim);padding:0 8px}

/* Toast */
.tc{position:fixed;bottom:24px;right:24px;z-index:300;display:flex;flex-direction:column;gap:8px}
.toast{padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.5);animation:ti .3s;display:flex;align-items:center;gap:8px}
@keyframes ti{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}
.tsuc{background:var(--green);color:#000}
.terr{background:var(--red);color:#fff}

@media(max-width:768px){
.nav{padding:0 14px}.main{padding:14px 10px}
.rcards{grid-template-columns:repeat(2,1fr)}
.sgrid{grid-template-columns:repeat(3,1fr);gap:6px}
.pgrid{grid-template-columns:repeat(3,1fr);gap:6px}
.scard{padding:10px}.snum{font-size:18px}.slabel{font-size:9px}
.tbar{padding:8px 10px;flex-direction:column}.ts{width:100%}
.tbl{font-size:11px}.tbl th,.tbl td{padding:8px 6px}
.modal{padding:18px;width:96%}.mgrid{grid-template-columns:1fr}
.qbar{gap:6px}.qbtn{padding:6px 12px;font-size:11px}
}
</style>
</head>
<body>
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>

<main class="app">

<nav class="nav">
  <div class="nav-l">
    <span class="nav-logo">📜 ScriptBD</span>
    <span class="nav-badge">ADMIN</span>
  </div>
  <div class="nav-r">
    <a href="https://scriptbd.com" target="_blank" class="btn-out gh">🏠 সাইট</a>
    <div class="nav-user">
      <div class="nav-avatar"><?=$adminInitial?></div>
      <div>
        <div style="font-weight:600"><?=$adminName?></div>
        <div class="nav-time">🟢 অনলাইন</div>
      </div>
    </div>
    <a href="logout.php" class="btn-out">🚪 লগআউট</a>
  </div>
</nav>

<div class="main">

  <?php if(isset($error_msg)): ?>
    <div style="background:var(--red)15;border:1px solid var(--red)30;padding:14px;border-radius:10px;margin-bottom:20px;font-size:13px">
      <strong>⚠️ ডেটাবেজ ইরর:</strong> <?=htmlspecialchars($error_msg)?>
      <br><small style="color:var(--dim)">ডেটাবেজ টেবিল চেক করুন (phpMyAdmin)</small>
    </div>
  <?php endif; ?>

  <!-- Quick Actions -->
  <div class="qbar">
    <a href="dashboard.php" class="qbtn <?=!$statusFilter&&!$pFilter&&!$search?'active':''?>">📋 সব অর্ডার</a>
    <a href="?payment=submitted" class="qbtn <?=$pFilter=='submitted'?'active':''?>">💳 ভেরিফাই বাকি</a>
    <a href="?status=pending" class="qbtn <?=$statusFilter=='pending'?'active':''?>">⏳ নতুন</a>
    <a href="?status=processing" class="qbtn <?=$statusFilter=='processing'?'active':''?>">⚡ প্রসেসিং</a>
    <button class="qbtn danger" onclick="bulkDelete()" id="bulkBtn" style="display:none">🗑️ সিলেক্টেড ডিলিট</button>
  </div>

  <!-- Revenue -->
  <div class="rcards">
    <div class="rcard total"><div class="ricon">📊</div><div class="rval"><?=number_format($revenue)?> ৳</div><div class="rlabel">মোট আয় (ভেরিফাইড)</div></div>
    <div class="rcard today"><div class="ricon">📅</div><div class="rval"><?=number_format($todayRev)?> ৳</div><div class="rlabel">আজকের আয় • <?=$todayOrders?> অর্ডার</div></div>
    <div class="rcard rev"><div class="ricon">🎯</div><div class="rval"><?=$totalAll?></div><div class="rlabel">মোট অর্ডার</div></div>
  </div>

  <!-- Status Stats -->
  <div class="sgrid">
    <?php
    $items=[
      'all'=>['📋','মোট',$totalAll,'var(--txt)'],
      'pending'=>['⏳','পেন্ডিং',$sc['pending']??0,'var(--gold)'],
      'processing'=>['⚡','প্রসেসিং',$sc['processing']??0,'var(--blue)'],
      'completed'=>['✅','সম্পন্ন',$sc['completed']??0,'var(--green)'],
      'cancelled'=>['❌','বাতিল',$sc['cancelled']??0,'var(--red)'],
    ];
    foreach($items as $k=>[$icon,$label,$count,$color]):
      $active=($k==='all'?!$statusFilter:$statusFilter===$k);
      $href=$k==='all'?'dashboard.php':"?status=$k";
      $pct=$totalAll>0?round($count/$totalAll*100):0;
    ?>
    <a href="<?=$href?>" class="scard <?=$active?'active':''?>">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="snum" style="color:<?=$color?>"><?=$count?></span>
        <span style="font-size:18px"><?=$icon?></span>
      </div>
      <div class="slabel"><?=$label?></div>
      <div class="sbar"><div class="sbarfill" style="width:<?=$pct?>%;background:<?=$color?>"></div></div>
    </a>
    <?php endforeach;?>
  </div>

  <!-- Payment Stats -->
  <div class="pgrid">
    <?php
    $pitems=[
      'submitted'=>['📩','জমা পড়েছে',$pc['submitted']??0,'var(--gold)'],
      'unpaid'=>['💤','অনাদায়ী',$pc['unpaid']??0,'var(--dim)'],
      'verified'=>['💎','ভেরিফাইড',$pc['verified']??0,'var(--green)'],
      'rejected'=>['🚫','রিজেক্ট',$pc['rejected']??0,'var(--red)'],
    ];
    foreach($pitems as $k=>[$icon,$label,$count,$color]):
    ?>
    <a href="?payment=<?=$k?>" class="pcard <?=$pFilter==$k?'active':''?>">
      <div class="pn" style="color:<?=$color?>"><?=$count?></div>
      <div class="pl"><?=$icon?> <?=$label?></div>
    </a>
    <?php endforeach;?>
  </div>

  <!-- Toolbar -->
  <div class="tbar">
    <input class="ts" id="search" placeholder="🔍 নাম, ইমেইল, ফোন, টপিক, TrxID দিয়ে খুঁজুন..." value="<?=htmlspecialchars($search)?>">
    <button class="bta" onclick="doSearch()">🔍 খুঁজুন</button>
    <?php if($statusFilter||$pFilter||$search):?><a href="dashboard.php" class="btn-out">✕ রিসেট</a><?php endif;?>
    <span class="count"><?=$total?> টি • পৃষ্ঠা <?=$page?>/<?=max(1,$totalPages)?></span>
  </div>

  <!-- Selection Bar -->
  <div class="selbar" id="selBar">
    <span class="selc" id="selCount">0 টি সিলেক্ট</span>
    <button class="bxs bv" onclick="bulkVerify()">✅ ভেরিফাই</button>
    <button class="bxs br" onclick="bulkDelete()">🗑️ ডিলিট</button>
    <button class="bxs bw" onclick="clearSel()">✕ ক্লিয়ার</button>
  </div>

  <!-- Orders Table -->
  <div class="tw">
    <?php if(empty($orders)):?>
      <div class="empty"><div class="eicon">📭</div><div class="etitle">কোনো অর্ডার নেই</div><div class="esub">ফিল্টার বদলান অথবা নতুন অর্ডারের জন্য অপেক্ষা করুন</div></div>
    <?php else:?>
    <table class="tbl" id="otable">
      <thead><tr>
        <th style="width:30px"><input type="checkbox" class="cb" id="sa" onclick="toggleAll(this)"></th>
        <th><?=sortLink('id','#',$sort,$order)?></th>
        <th><?=sortLink('name','নাম',$sort,$order)?></th>
        <th>প্ল্যান</th>
        <th>টপিক</th>
        <th><?=sortLink('amount','টাকা',$sort,$order)?></th>
        <th>পেমেন্ট</th>
        <th>ডেলিভারি</th>
        <th><?=sortLink('status','স্ট্যাটাস',$sort,$order)?></th>
        <th><?=sortLink('created_at','তারিখ',$sort,$order)?></th>
        <th>অ্যাকশন</th>
      </tr></thead>
      <tbody>
      <?php foreach($orders as $o):?>
      <tr id="r-<?=$o['id']?>">
        <td><input type="checkbox" class="cb rcb" value="<?=$o['id']?>" onchange="upSel()"></td>
        <td class="cid" onclick="viewOrder(<?=$o['id']?>)">#<?=$o['id']?></td>
        <td title="<?=htmlspecialchars($o['name'])?>"><?=htmlspecialchars(mb_strlen($o['name'])>18?mb_substr($o['name'],0,15).'...':$o['name'])?></td>
        <td><span style="text-transform:capitalize;font-size:11px"><?=str_replace('-',' ',htmlspecialchars($o['plan']))?></span></td>
        <td class="ctopic" title="<?=htmlspecialchars($o['topic'])?>"><?=htmlspecialchars($o['topic'])?></td>
        <td class="camt">৳<?=number_format($o['amount']??0)?></td>
        <td>
          <?=badge('payment',$o['payment_status']??'unpaid')?>
          <?php if($o['transaction_id']):?><br><small style="color:var(--dim);font-size:10px"><?=htmlspecialchars($o['transaction_id'])?></small><?php endif;?>
        </td>
        <td><?=badge('delivery',($o['delivery_status']??'')?:($o['status']=='completed'?'delivered':'not_delivered'))?></td>
        <td><?=badge('status',$o['status'])?></td>
        <td class="ctime" title="<?=$o['created_at']?>"><?=timeAgo($o['created_at'])?></td>
        <td>
          <div class="acts">
            <button class="bxs bw" onclick="viewOrder(<?=$o['id']?>)" title="বিস্তারিত">👁️</button>
            <?php if(($o['payment_status']??'')=='submitted'):?><button class="bxs bv" onclick="verify(<?=$o['id']?>)" title="ভেরিফাই">✅</button><?php endif;?>
            <?php if(($o['payment_status']??'')=='verified'&&($o['delivery_status']??'')!='delivered'):?><button class="bxs bd" onclick="deliver(<?=$o['id']?>)" title="ডেলিভার">📦</button><?php endif;?>
            <?php if($o['status']=='pending'):?><button class="bxs bq" onclick="quickS(<?=$o['id']?>,'processing')">⚡</button><?php endif;?>
            <?php if($o['status']=='processing'):?><button class="bxs bq" onclick="quickS(<?=$o['id']?>,'completed')">✅</button><?php endif;?>
            <button class="bxs br" onclick="del(<?=$o['id']?>)">🗑️</button>
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
    <?php if($page>1):$pq=$_GET;$pq['page']=$page-1;?><a href="?<?=http_build_query($pq)?>" class="plk">← আগে</a><?php endif;?>
    <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++):$pq2=$_GET;$pq2['page']=$i;?>
      <a href="?<?=http_build_query($pq2)?>" class="plk <?=$i==$page?'active':''?>"><?=$i?></a>
    <?php endfor;?>
    <?php if($page<$totalPages):$pq3=$_GET;$pq3['page']=$page+1;?><a href="?<?=http_build_query($pq3)?>" class="plk">পরে →</a><?php endif;?>
    <span class="pinfo"><?=$total?> টি অর্ডার</span>
  </div>
  <?php endif;?>

</div>
</main>

<div class="mo" id="om"><div class="modal" id="mi"></div></div>
<div class="tc" id="tc"></div>

<script>
function doSearch(){const q=document.getElementById('search').value.trim();const p=new URLSearchParams(location.search);q?p.set('search',q):p.delete('search');p.set('page','1');location.search=p.toString()}
document.getElementById('search').addEventListener('keypress',e=>{if(e.key=='Enter')doSearch()});

let selected=new Set();
function toggleAll(el){document.querySelectorAll('.rcb').forEach(c=>{c.checked=el.checked;if(el.checked)selected.add(c.value);else selected.delete(c.value)});upSel()}
function upSel(){selected.clear();document.querySelectorAll('.rcb:checked').forEach(c=>selected.add(c.value));const n=selected.size;const b=document.getElementById('selBar');const qb=document.getElementById('bulkBtn');document.getElementById('selCount').textContent=n+' টি সিলেক্ট';if(n>0){b.classList.add('active');qb.style.display=''}else{b.classList.remove('active');qb.style.display='none'};document.getElementById('sa').checked=n>0&&n===document.querySelectorAll('.rcb').length}
function clearSel(){document.querySelectorAll('.rcb').forEach(c=>c.checked=false);selected.clear();upSel()}
function bulkVerify(){if(!selected.size)return;if(!confirm(selected.size+' টি ভেরিফাই করবেন?'))return;api('bulk_verify',{ids:JSON.stringify([...selected])}).then(r=>{t(r)});}
function bulkDelete(){if(!selected.size){alert('অর্ডার সিলেক্ট করুন');return;}if(!confirm(selected.size+' টি ডিলিট করবেন? এটি UNDO হবে না!'))return;api('bulk_delete',{ids:JSON.stringify([...selected])}).then(r=>{t(r)})}
function quickS(id,st){api('update_status',{id:id,status:st}).then(r=>{t(r)})}
function verify(id){if(!confirm('#'+id+' ভেরিফাই করবেন?'))return;api('verify_payment',{id:id}).then(r=>{t(r)})}
function deliver(id){if(!confirm('#'+id+' ডেলিভারি সম্পন্ন করবেন?'))return;api('mark_delivered',{id:id}).then(r=>{t(r)})}
function del(id){if(!confirm('#'+id+' ডিলিট করবেন?'))return;api('delete',{id:id}).then(r=>{t(r)})}
function saveNotes(id){const n=document.getElementById('n_'+id).value;api('save_notes',{id:id,notes:n}).then(r=>{t(r)})}

function viewOrder(id){
  document.getElementById('om').classList.add('active');
  document.getElementById('mi').innerHTML='<p style=text-align:center;color:var(--dim);padding:40px>⏳ লোড হচ্ছে...</p>';
  api('get_order',{id:id}).then(res=>{
    if(!res.success){document.getElementById('mi').innerHTML='<p style=text-align:center;color:var(--red)'>❌ পাওয়া যায়নি</p>';return}
    const o=res.data;
    const plans={'youtube-shorts':'YouTube Shorts (৫টি)','facebook-reels':'Facebook Reels (৫টি)','youtube-full':'YouTube Full (১টি)'};
    // Status flow
    let flow=[{l:'📥 অর্ডার',d:true}];
    if(o.payment_status=='submitted')flow.push({l:'💳 পেমেন্ট জমা',n:true});
    else if(o.payment_status=='verified'){flow.push({l:'💳 পেমেন্ট জমা',d:true});flow.push({l:'✅ ভেরিফাই',d:true});}
    else flow.push({l:'💳 পেমেন্ট জমা',d:false});
    if(o.payment_status=='verified'&&(o.delivery_status=='delivered'||o.status=='completed'))flow.push({l:'📦 ডেলিভারি',d:true});
    else if(o.payment_status=='verified')flow.push({l:'📦 ডেলিভারি',n:true});
    
    const fhtml=flow.map(s=>'<span class="sstep '+(s.n?'now':s.d?'done':'')+'">'+s.l+'</span>').join(' → ');
    
    document.getElementById('mi').innerHTML=
      '<button class="mclose" onclick="closeM()">✕</button>'+
      '<h2>📋 অর্ডার #'+o.id+' <span style="font-size:11px;color:var(--dim);font-weight:400">'+(o.created_at||'')+'</span></h2>'+
      '<div style="margin-bottom:18px"><div class="sflow">'+fhtml+'</div></div>'+
      '<div class="mgrid">'+
        '<div class="dr"><div class="dl">👤 নাম</div><div class="dv">'+esc(o.name)+'</div></div>'+
        '<div class="dr"><div class="dl">📧 ইমেইল</div><div class="dv">'+esc(o.email||'—')+'</div></div>'+
        '<div class="dr"><div class="dl">📱 ফোন</div><div class="dv">'+esc(o.phone)+'</div></div>'+
        '<div class="dr"><div class="dl">💼 প্ল্যান</div><div class="dv" style="text-transform:capitalize">'+esc(plans[o.plan]||o.plan)+' • ৳'+(o.amount||0)+'</div></div>'+
        '<div class="dr"><div class="dl">📝 টপিক</div><div class="dv">'+esc(o.topic||'—')+'</div></div>'+
        '<div class="dr"><div class="dl">💳 পেমেন্ট</div><div class="dv" style="text-transform:uppercase">'+esc(o.payment_method||'—')+'</div></div>'+
        '<div class="dr"><div class="dl">🔢 TrxID</div><div class="dv" style="font-family:monospace">'+esc(o.transaction_id||'—')+'</div></div>'+
        '<div class="dr"><div class="dl">📊 স্ট্যাটাস</div><div class="dv"><select class="ss" onchange="upStatus('+o.id+',this.value)">'+
          ['pending','processing','completed','cancelled'].map(s=>'<option value="'+s+'"'+(o.status==s?' selected':'')+'>'+s+'</option>').join('')+'</select></div></div>'+
      '</div>'+
      '<div class="dr" style="margin-top:12px"><div class="dl">💬 মেসেজ</div><div class="mbox">'+esc(o.message||'(কোনো মেসেজ নেই)')+'</div></div>'+
      '<div class="dr"><div class="dl">📝 নোট</div><textarea class="na" id="n_'+o.id+'" rows="2" placeholder="নোট...">'+esc(o.admin_notes||'')+'</textarea>'+
      '<button class="bxs bw" style="margin-top:6px" onclick="saveNotes('+o.id+')">💾 সেভ</button></div>'+
      (o.delivered_by?'<div style="margin-top:8px;font-size:11px;color:var(--dim)">📦 ডেলিভার করেছেন: '+esc(o.delivered_by)+' • '+(o.delivery_date||'')+'</div>':'')+
      (o.verified_at?'<div style="font-size:11px;color:var(--dim)">✅ ভেরিফাই: '+o.verified_at+'</div>':'')+
      '<div class="macts">'+
        (o.payment_status=='submitted'?'<button class="btn bg" onclick="verify('+o.id+')">✅ পেমেন্ট ভেরিফাই</button>':'')+
        ((o.payment_status=='verified'||o.payment_status=='submitted')&&(o.delivery_status!='delivered'&&o.status!='completed')?'<button class="btn bpp" onclick="deliver('+o.id+')">📦 ডেলিভারি সম্পন্ন</button>':'')+
        (o.status=='pending'?'<button class="btn bb" onclick="quickS('+o.id+','processing')">⚡ প্রসেসিং এ নিন</button>':'')+
        '<button class="btn bred" onclick="closeM();del('+o.id+')">🗑️ ডিলিট</button>'+
      '</div>';
  });
}
function upStatus(id,st){api('update_status',{id:id,status:st}).then(r=>{t(r)})}
function closeM(){document.getElementById('om').classList.remove('active')}
document.getElementById('om').addEventListener('click',e=>{if(e.target===document.getElementById('om'))closeM()})
document.addEventListener('keydown',e=>{if(e.key=='Escape')closeM()})

function api(action,data){const fd=new URLSearchParams({action:action,...data});return fetch('dashboard.php',{method:'POST',body:fd}).then(r=>r.json())}
function t(res){const el=document.createElement('div');el.className='toast '+(res.success?'tsuc':'terr');el.textContent=res.message;document.getElementById('tc').appendChild(el);setTimeout(()=>{el.style.opacity='0';el.style.transition='.3s';setTimeout(()=>el.remove(),300)},3000);if(res.success)setTimeout(()=>location.reload(),500)}
function esc(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML}
</script>
</body>
</html>