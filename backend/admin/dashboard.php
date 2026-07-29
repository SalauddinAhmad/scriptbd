<?php
/**
 * ScriptBD - Admin Dashboard v3
 * One-click Verify Payment + Mark Delivered + Tracking
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit;
}
require_once __DIR__ . '/../config/database.php';

// --- AJAX Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = getDBConnection();
        $action = $_POST['action'];

        if ($action === 'verify_payment') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE orders SET payment_status='verified', verified_at=NOW(), updated_at=NOW() WHERE id=:id")
                ->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => '✅ পেমেন্ট ভেরিফাই হয়েছে!']);
            exit;
        }
        if ($action === 'mark_delivered') {
            $id = (int)($_POST['id'] ?? 0);
            $admin = $_SESSION['admin_username'] ?? 'admin';
            $pdo->prepare("UPDATE orders SET delivery_status='delivered', delivery_date=NOW(), status='completed', delivered_by=:admin, updated_at=NOW() WHERE id=:id")
                ->execute([':id' => $id, ':admin' => $admin]);
            echo json_encode(['success' => true, 'message' => '📦 ডেলিভারি সম্পন্ন! অর্ডার Completed হয়েছে।']);
            exit;
        }
        if ($action === 'save_notes') {
            $id = (int)($_POST['id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $pdo->prepare("UPDATE orders SET admin_notes=:notes, updated_at=NOW() WHERE id=:id")
                ->execute([':id' => $id, ':notes' => $notes]);
            echo json_encode(['success' => true, 'message' => 'নোট সেভ হয়েছে']);
            exit;
        }
        if ($action === 'update_status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $valid = ['pending', 'processing', 'completed', 'cancelled'];
            if ($id <= 0 || !in_array($status, $valid)) {
                echo json_encode(['success' => false]); exit;
            }
            $pdo->prepare("UPDATE orders SET status=:s, updated_at=NOW() WHERE id=:id")
                ->execute([':s' => $status, ':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'স্ট্যাটাস আপডেট হয়েছে']);
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM orders WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'অর্ডার ডিলিট হয়েছে']);
            exit;
        }
        if ($action === 'get_order') {
            $id = (int)($_POST['id'] ?? 0);
            $o = $pdo->prepare("SELECT * FROM orders WHERE id=:id");
            $o->execute([':id' => $id]);
            $order = $o->fetch();
            echo json_encode(['success' => (bool)$order, 'data' => $order]);
            exit;
        }
    } catch (PDOException $e) {
        error_log('Dashboard Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'DB error']);
        exit;
    }
}

// --- Fetch Orders ---
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$pFilter = trim($_GET['payment'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    $pdo = getDBConnection();
    $where = []; $params = [];
    $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
    $validPayment = ['unpaid', 'submitted', 'verified', 'rejected'];

    if ($statusFilter && in_array($statusFilter, $validStatuses)) {
        $where[] = 'o.status = :status'; $params[':status'] = $statusFilter;
    }
    if ($pFilter && in_array($pFilter, $validPayment)) {
        $where[] = 'o.payment_status = :pstat'; $params[':pstat'] = $pFilter;
    }
    if ($search) {
        $where[] = '(o.name LIKE :s1 OR o.email LIKE :s2 OR o.phone LIKE :s3 OR o.topic LIKE :s4 OR o.id = :s5)';
        $s = '%'.$search.'%';
        $params[':s1'] = $s; $params[':s2'] = $s; $params[':s3'] = $s; $params[':s4'] = $s;
        $params[':s5'] = is_numeric($search) ? (int)$search : 0;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Counts
    $cnt = $pdo->query("SELECT COUNT(*) as t FROM orders o {$whereClause}");
    foreach ($params as $k => $v) $cnt->bindValue($k, $v);
    $cnt->execute(); $total = (int)$cnt->fetch()['t'];
    $totalPages = ceil($total / $limit);

    // Orders
    $stmt = $pdo->prepare("SELECT o.* FROM orders o {$whereClause} ORDER BY o.created_at DESC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();

    // Summary counts
    $sc = $pdo->query("SELECT status, COUNT(*) as c FROM orders GROUP BY status");
    $statusCounts = []; foreach ($sc as $r) $statusCounts[$r['status']] = (int)$r['c'];
    $pc = $pdo->query("SELECT payment_status, COUNT(*) as c FROM orders GROUP BY payment_status");
    $payCounts = []; foreach ($pc as $r) $payCounts[$r['payment_status']] = (int)$r['c'];
    $totalAll = array_sum($statusCounts);
} catch (Exception $e) {
    $orders = []; $statusCounts = []; $payCounts = []; $totalAll = 0; $totalPages = 0;
}

function badge($type, $value) {
    $map = [
        'pending' => ['#fbbf2422','#fbbf24','পেন্ডিং'],
        'processing' => ['#2196f322','#2196f3','প্রসেসিং'],
        'completed' => ['#10b98122','#10b981','সম্পন্ন'],
        'cancelled' => ['#ef444422','#ef4444','বাতিল'],
        'unpaid' => ['#6b728022','#9ca3af','⏳ অনাদায়ী'],
        'submitted' => ['#f59e0b22','#f59e0b','📩 জমা'],
        'verified' => ['#10b98122','#10b981','✅ ভেরিফাইড'],
        'delivered' => ['#8b5cf622','#8b5cf6','📦 ডেলিভার্ড'],
        'not_delivered' => ['#6b728022','#9ca3af','⏳ বাকি'],
    ];
    $s = $map[$value] ?? $map['pending'];
    return "<span style='background:{$s[0]};color:{$s[1]};padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;white-space:nowrap'>{$s[2]}</span>";
}

function timeAgo($dt) {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'এইমাত্র';
    if ($diff < 3600) return floor($diff/60).'মি';
    if ($diff < 86400) return floor($diff/3600).'ঘ';
    if ($diff < 2592000) return floor($diff/86400).'দিন';
    return date('d/m', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="bn">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ড্যাশবোর্ড - স্ক্রিপ্টবিডি</title>
<style>
:root{--bg:#0a0a0f;--sbg:#13131f;--card:#1a1a2e;--accent:#ff6b35;--ah:#ff8c5a;--txt:#e8e6f0;--dim:#8a88a0;--border:#27273a;--green:#10b981;--blue:#3b82f6;--red:#ef4444;--gold:#f59e0b;--purple:#8b5cf6}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Noto Sans Bengali',system-ui,sans-serif;background:var(--bg);color:var(--txt);min-height:100vh}
.nav{background:var(--card);border-bottom:1px solid var(--border);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.nav-brand{display:flex;align-items:center;gap:10px;font-size:18px;font-weight:800;background:linear-gradient(135deg,var(--accent),#ff3366);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav-right{display:flex;align-items:center;gap:14px}
.nav-user{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--dim)}
.nav-avatar{width:32px;height:32px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff}
.btn-out{background:transparent;border:1px solid var(--border);color:var(--dim);padding:6px 14px;border-radius:8px;cursor:pointer;font:inherit;font-size:12px;text-decoration:none;transition:.3s}
.btn-out:hover{border-color:var(--red);color:var(--red)}
.main{padding:24px;max-width:1500px;margin:0 auto}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;cursor:pointer;transition:.3s;text-decoration:none;color:inherit}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.3);border-color:var(--accent)}
.stat-card.active{border-color:var(--accent);box-shadow:0 0 0 1px var(--accent)}
.stat-num{font-size:26px;font-weight:800}
.stat-label{font-size:11px;color:var(--dim);margin-top:2px}
.stat-all .stat-num{color:var(--txt)} .stat-pending .stat-num{color:var(--gold)} .stat-processing .stat-num{color:var(--blue)} .stat-completed .stat-num{color:var(--green)} .stat-cancelled .stat-num{color:var(--red)}
.pay-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:20px}
.pay-card{background:var(--sbg);border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center;cursor:pointer;transition:.3s;text-decoration:none;color:inherit}
.pay-card:hover{border-color:var(--accent)} .pay-card.active{border-color:var(--accent)}
.pay-card .pn{font-size:22px;font-weight:800} .pay-card .pl{font-size:10px;color:var(--dim)}
.fbar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
.search{flex:1;min-width:200px;padding:10px 14px;background:var(--card);border:1px solid var(--border);border-radius:10px;color:var(--txt);font:inherit;font-size:13px;outline:none}
.search:focus{border-color:var(--accent)}
.btn-accent{background:var(--accent);color:#fff;border:none;padding:10px 20px;border-radius:10px;cursor:pointer;font:inherit;font-size:13px;font-weight:600;transition:.3s;white-space:nowrap}
.btn-accent:hover{background:var(--ah)}
.count{font-size:12px;color:var(--dim);white-space:nowrap}
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow-x:auto}
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{background:var(--sbg);padding:12px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--dim);letter-spacing:.5px;border-bottom:1px solid var(--border);white-space:nowrap}
.tbl td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.tbl tr:hover td{background:rgba(255,107,53,.03)}
.tbl tr:last-child td{border-bottom:none}
.order-id{color:var(--accent);font-weight:700;cursor:pointer}.order-id:hover{text-decoration:underline}
.actions{display:flex;gap:5px;flex-wrap:wrap}
.btn-xs{padding:5px 10px;border:none;border-radius:6px;cursor:pointer;font:inherit;font-size:11px;font-weight:500;transition:.3s;white-space:nowrap}
.btn-verify{background:rgba(16,185,129,.15);color:var(--green)}.btn-verify:hover{background:rgba(16,185,129,.25)}
.btn-deliver{background:rgba(139,92,246,.15);color:var(--purple)}.btn-deliver:hover{background:rgba(139,92,246,.25)}
.btn-view{background:rgba(59,130,246,.15);color:var(--blue)}.btn-view:hover{background:rgba(59,130,246,.25)}
.btn-delete{background:rgba(239,68,68,.15);color:var(--red)}.btn-delete:hover{background:rgba(239,68,68,.25)}
.btn-ready{background:rgba(245,158,11,.15);color:var(--gold)}.btn-ready:hover{background:rgba(245,158,11,.25)}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:32px;width:90%;max-width:650px;max-height:85vh;overflow-y:auto;position:relative}
.modal-close{position:absolute;top:14px;right:14px;width:30px;height:30px;border-radius:50%;border:1px solid var(--border);background:var(--sbg);color:var(--dim);cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center}
.modal-close:hover{border-color:var(--red);color:var(--red)}
.modal h2{font-size:20px;margin-bottom:18px;background:linear-gradient(135deg,var(--accent),#ff3366);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.detail-row{margin-bottom:14px}.detail-label{font-size:10px;text-transform:uppercase;color:var(--dim);letter-spacing:.5px;margin-bottom:2px}.detail-val{font-size:14px}
.msg-box{background:var(--sbg);border:1px solid var(--border);border-radius:10px;padding:14px;margin-top:4px;font-size:13px;line-height:1.6;white-space:pre-wrap}
.modal-actions{display:flex;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);flex-wrap:wrap}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font:inherit;font-size:13px;font-weight:600;transition:.3s}
.btn:hover{filter:brightness(1.2)}
.notes-area{width:100%;background:var(--sbg);border:1px solid var(--border);border-radius:10px;color:var(--txt);padding:12px;font:inherit;font-size:12px;resize:vertical;margin-top:8px}
.empty{text-align:center;padding:60px 20px;color:var(--dim)}
.toast{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;z-index:300;animation:slideUp .3s;box-shadow:0 8px 24px rgba(0,0,0,.4)}
.toast-success{background:var(--green);color:#000}.toast-error{background:var(--red);color:#fff}
@keyframes slideUp{from{transform:translateY(100px);opacity:0}to{transform:translateY(0);opacity:1}}
.pagination{display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap}
.page-link{padding:8px 14px;background:var(--card);border:1px solid var(--border);border-radius:8px;color:var(--dim);text-decoration:none;font-size:12px}.page-link:hover,.page-link.active{background:var(--accent);color:#fff;border-color:var(--accent)}
@media(max-width:768px){.nav{padding:0 14px}.main{padding:16px 10px}.stats{grid-template-columns:repeat(3,1fr)}.tbl{font-size:11px}.tbl td,.tbl th{padding:8px 6px}}
</style>
</head>
<body>
<nav class="nav">
  <div class="nav-brand">📜 স্ক্রিপ্টবিডি</div>
  <div class="nav-right">
    <div class="nav-user">
      <div class="nav-avatar"><?=mb_substr($_SESSION['admin_username']??'A',0,1)?></div>
      <span><?=htmlspecialchars($_SESSION['admin_username']??'Admin')?></span>
    </div>
    <a href="logout.php" class="btn-out">লগআউট</a>
  </div>
</nav>
<div class="main">
  <!-- Status Stats -->
  <div class="stats">
    <a href="dashboard.php" class="stat-card stat-all <?=$statusFilter&&!$pFilter?'':'active'?>"><div class="stat-num"><?=$totalAll?></div><div class="stat-label">📋 মোট অর্ডার</div></a>
    <a href="?status=pending" class="stat-card stat-pending <?=$statusFilter=='pending'?'active':''?>"><div class="stat-num"><?=$statusCounts['pending']??0?></div><div class="stat-label">⏳ পেন্ডিং</div></a>
    <a href="?status=processing" class="stat-card stat-processing <?=$statusFilter=='processing'?'active':''?>"><div class="stat-num"><?=$statusCounts['processing']??0?></div><div class="stat-label">🔄 প্রসেসিং</div></a>
    <a href="?status=completed" class="stat-card stat-completed <?=$statusFilter=='completed'?'active':''?>"><div class="stat-num"><?=$statusCounts['completed']??0?></div><div class="stat-label">✅ সম্পন্ন</div></a>
    <a href="?status=cancelled" class="stat-card stat-cancelled <?=$statusFilter=='cancelled'?'active':''?>"><div class="stat-num"><?=$statusCounts['cancelled']??0?></div><div class="stat-label">❌ বাতিল</div></a>
  </div>

  <!-- Payment Stats -->
  <div class="pay-stats">
    <a href="?payment=submitted" class="pay-card <?=$pFilter=='submitted'?'active':''?>"><div class="pn" style="color:var(--gold)"><?=$payCounts['submitted']??0?></div><div class="pl">📩 পেমেন্ট জমা</div></a>
    <a href="?payment=unpaid" class="pay-card <?=$pFilter=='unpaid'?'active':''?>"><div class="pn" style="color:var(--dim)"><?=$payCounts['unpaid']??0?></div><div class="pl">⏳ অনাদায়ী</div></a>
    <a href="?payment=verified" class="pay-card <?=$pFilter=='verified'?'active':''?>"><div class="pn" style="color:var(--green)"><?=$payCounts['verified']??0?></div><div class="pl">✅ ভেরিফাইড</div></a>
  </div>

  <!-- Filters -->
  <div class="fbar">
    <input class="search" id="search" placeholder="🔍 নাম, ইমেইল, ফোন, টপিক, ID দিয়ে খুঁজুন..." value="<?=htmlspecialchars($search)?>">
    <button class="btn-accent" onclick="search()">খুঁজুন</button>
    <?php if($statusFilter||$pFilter||$search): ?><a href="dashboard.php" class="btn-out">✕ রিসেট</a><?php endif; ?>
    <span class="count"><?=$total?> টি পাওয়া গেছে</span>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <?php if(empty($orders)): ?>
      <div class="empty"><p>📭 কোনো অর্ডার নেই</p></div>
    <?php else: ?>
    <table class="tbl">
      <thead><tr>
        <th>ID</th><th>তারিখ</th><th>নাম</th><th>প্ল্যান</th><th>টপিক</th><th>টাকা</th><th>পেমেন্ট</th><th>ডেলিভারি</th><th>স্ট্যাটাস</th><th>অ্যাকশন</th>
      </tr></thead>
      <tbody>
      <?php foreach($orders as $o): ?>
      <tr>
        <td><span class="order-id" onclick="viewOrder(<?=$o['id']?>)">#<?=$o['id']?></span></td>
        <td title="<?=$o['created_at']?>"><?=timeAgo($o['created_at'])?></td>
        <td><?=htmlspecialchars($o['name'])?></td>
        <td style="text-transform:capitalize"><?=str_replace('-',' ',htmlspecialchars($o['plan']))?></td>
        <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($o['topic'])?>"><?=htmlspecialchars($o['topic'])?></td>
        <td style="font-weight:700">৳<?=number_format($o['amount']??0)?></td>
        <td><?=badge('payment',$o['payment_status']??'unpaid')?><br><small style="color:var(--dim)"><?=htmlspecialchars($o['transaction_id']??'')?></small></td>
        <td><?=badge('delivery',$o['delivery_status']??($o['status']=='completed'?'delivered':'not_delivered'))?></td>
        <td><?=badge('status',$o['status'])?></td>
        <td>
          <div class="actions">
            <button class="btn-xs btn-view" onclick="viewOrder(<?=$o['id']?>)">👁️</button>
            <?php if(($o['payment_status']??'')=='submitted'): ?>
              <button class="btn-xs btn-verify" onclick="verifyPayment(<?=$o['id']?>)">✅ ভেরিফাই</button>
            <?php endif; ?>
            <?php if(($o['payment_status']??'')=='verified' && ($o['delivery_status']??'')!='delivered'): ?>
              <button class="btn-xs btn-deliver" onclick="markDelivered(<?=$o['id']?>)">📦 ডেলিভার</button>
            <?php endif; ?>
            <button class="btn-xs btn-delete" onclick="delOrder(<?=$o['id']?>)">🗑️</button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if($totalPages>1): ?>
  <div class="pagination">
    <?php
    $buildUrl = function($p)use($statusFilter,$pFilter,$search){
      $q=['page'=>$p];if($statusFilter)$q['status']=$statusFilter;if($pFilter)$q['payment']=$pFilter;if($search)$q['search']=$search;
      return '?'.http_build_query($q);
    };
    for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++)
      echo '<a href="'.$buildUrl($i).'" class="page-link '.($i==$page?'active':'').'">'.$i.'</a>';
    ?>
  </div>
  <?php endif; ?>
</div>

<!-- Detail Modal -->
<div class="modal-overlay" id="orderModal"><div class="modal" id="modalInner"></div></div>

<script>
function search(){const q=document.getElementById('search').value.trim();const p=new URLSearchParams(window.location.search);q?p.set('search',q):p.delete('search');p.set('page','1');window.location.search=p.toString()}
document.getElementById('search').addEventListener('keypress',e=>{if(e.key=='Enter')search()});

function viewOrder(id){
  document.getElementById('orderModal').classList.add('active');
  document.getElementById('modalInner').innerHTML='<p style=color:var(--dim)>লোড হচ্ছে...</p>';
  fetch('dashboard.php',{method:'POST',body:new URLSearchParams({action:'get_order',id:id})})
  .then(r=>r.json()).then(res=>{
    if(!res.success){document.getElementById('modalInner').innerHTML='<p style=color:var(--red)>পাওয়া যায়নি</p>';return}
    const o=res.data;
    const plans={};plans['youtube-shorts']='YouTube Shorts';plans['facebook-reels']='Facebook Reels';plans['youtube-full']='YouTube Full';
    document.getElementById('modalInner').innerHTML=
      '<button class=modal-close onclick=closeModal()>✕</button>'+
      '<h2>অর্ডার #'+o.id+'</h2>'+
      '<div class=detail-row><div class=detail-label>নাম</div><div class=detail-val>'+esc(o.name)+'</div></div>'+
      '<div class=detail-row><div class=detail-label>ইমেইল</div><div class=detail-val>'+esc(o.email||'-')+'</div></div>'+
      '<div class=detail-row><div class=detail-label>ফোন</div><div class=detail-val>'+esc(o.phone)+'</div></div>'+
      '<div class=detail-row><div class=detail-label>প্ল্যান</div><div class=detail-val style=text-transform:capitalize>'+esc(plans[o.plan]||o.plan)+' — ৳'+(o.amount||0)+'</div></div>'+
      '<div class=detail-row><div class=detail-label>টপিক</div><div class=detail-val>'+esc(o.topic||'-')+'</div></div>'+
      '<div class=detail-row><div class=detail-label>মেসেজ</div><div class=msg-box>'+esc(o.message||'(কোনো মেসেজ নেই)')+'</div></div>'+
      '<div class=detail-row><div class=detail-label>পেমেন্ট</div><div class=detail-val>'+(o.transaction_id?'TrxID: '+esc(o.transaction_id)+' ('+esc(o.payment_method||'')+')':'অনাদায়ী')+'</div></div>'+
      '<div class=detail-row><div class=detail-label>অ্যাডমিন নোট</div><textarea class=notes-area id=n_'+o.id+' rows=3 placeholder=নোট...>'+esc(o.admin_notes||'')+'</textarea><button class=btn-xs style=margin-top:6px onclick=saveNotes('+o.id+')>💾 সেভ</button></div>'+
      '<div class=modal-actions id=ma_'+o.id+'></div>';
    let a='';
    if(o.payment_status=='submitted') a+='<button class=btn onclick=verifyPayment('+o.id+')>✅ পেমেন্ট ভেরিফাই</button>';
    if((o.payment_status=='verified'||o.payment_status=='submitted')&&o.delivery_status!='delivered'&&o.status!='completed')
      a+='<button class=btn style=background:var(--purple) onclick=markDelivered('+o.id+')>📦 ডেলিভারি সম্পন্ন</button>';
    a+='<button class=btn style=background:var(--red) onclick="closeModal();delOrder('+o.id+')">🗑️ ডিলিট</button>';
    document.getElementById('ma_'+o.id).innerHTML=a;
  });
}
function closeModal(){document.getElementById('orderModal').classList.remove('active')}
document.getElementById('orderModal').addEventListener('click',e=>{if(e.target===e.target.currentTarget)closeModal()});

function verifyPayment(id){
  if(!confirm('পেমেন্ট ভেরিফাই করবেন?'))return;
  fetch('dashboard.php',{method:'POST',body:new URLSearchParams({action:'verify_payment',id:id})})
  .then(r=>r.json()).then(r=>{toast(r.success?'success':'error',r.message);if(r.success)setTimeout(()=>location.reload(),600)});
}
function markDelivered(id){
  if(!confirm('ডেলিভারি সম্পন্ন হিসেবে চিহ্নিত করবেন?'))return;
  fetch('dashboard.php',{method:'POST',body:new URLSearchParams({action:'mark_delivered',id:id})})
  .then(r=>r.json()).then(r=>{toast(r.success?'success':'error',r.message);if(r.success)setTimeout(()=>location.reload(),600)});
}
function saveNotes(id){
  const n=document.getElementById('n_'+id).value;
  fetch('dashboard.php',{method:'POST',body:new URLSearchParams({action:'save_notes',id:id,notes:n})})
  .then(r=>r.json()).then(r=>toast(r.success?'success':'error',r.message));
}
function delOrder(id){
  if(!confirm('নিশ্চিত?'))return;
  fetch('dashboard.php',{method:'POST',body:new URLSearchParams({action:'delete',id:id})})
  .then(r=>r.json()).then(r=>{toast(r.success?'success':'error',r.message);if(r.success)setTimeout(()=>location.reload(),600)});
}
function toast(type,msg){
  const e=document.createElement('div');e.className='toast toast-'+type;e.textContent=msg;document.body.appendChild(e);
  setTimeout(()=>e.remove(),3000);
}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML}
</script>
</body></html>
