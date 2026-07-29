<?php
/**
 * ScriptBD — Professional Admin Dashboard v4
 * Smart & Creative Management Panel
 */
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit;
}
require_once __DIR__ . '/../config/database.php';

// ─── AJAX Actions ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = getDBConnection();
        $action = $_POST['action'];

        if ($action === 'verify_payment') {
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE orders SET payment_status='verified', verified_at=NOW(), updated_at=NOW() WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => '✅ পেমেন্ট ভেরিফাই হয়েছে!']); exit;
        }
        if ($action === 'mark_delivered') {
            $id = (int)$_POST['id'];
            $admin = $_SESSION['admin_username'] ?? 'admin';
            $pdo->prepare("UPDATE orders SET delivery_status='delivered', delivery_date=NOW(), status='completed', delivered_by=:a, updated_at=NOW() WHERE id=:id")->execute([':id' => $id, ':a' => $admin]);
            echo json_encode(['success' => true, 'message' => '📦 ডেলিভারি সম্পন্ন!']); exit;
        }
        if ($action === 'save_notes') {
            $id = (int)$_POST['id'];
            $notes = trim($_POST['notes'] ?? '');
            $pdo->prepare("UPDATE orders SET admin_notes=:n, updated_at=NOW() WHERE id=:id")->execute([':id' => $id, ':n' => $notes]);
            echo json_encode(['success' => true, 'message' => '💾 নোট সেভ হয়েছে']); exit;
        }
        if ($action === 'update_status') {
            $id = (int)$_POST['id'];
            $status = trim($_POST['status'] ?? '');
            if (!in_array($status, ['pending','processing','completed','cancelled'])) { echo json_encode(['success' => false]); exit; }
            $pdo->prepare("UPDATE orders SET status=:s, updated_at=NOW() WHERE id=:id")->execute([':s' => $status, ':id' => $id]);
            echo json_encode(['success' => true, 'message' => '📝 স্ট্যাটাস আপডেট হয়েছে']); exit;
        }
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM orders WHERE id=:id")->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => '🗑️ অর্ডার ডিলিট হয়েছে']); exit;
        }
        if ($action === 'get_order') {
            $o = $pdo->prepare("SELECT * FROM orders WHERE id=:id");
            $o->execute([':id' => (int)$_POST['id']]);
            $order = $o->fetch();
            echo json_encode(['success' => (bool)$order, 'data' => $order]); exit;
        }
        if ($action === 'bulk_verify') {
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $stmt = $pdo->prepare("UPDATE orders SET payment_status='verified', verified_at=NOW(), updated_at=NOW() WHERE id=:id AND payment_status='submitted'");
            $c = 0; foreach ($ids as $id) { $stmt->execute([':id' => (int)$id]); $c += $stmt->rowCount(); }
            echo json_encode(['success' => true, 'message' => "✅ $c টি পেমেন্ট ভেরিফাই হয়েছে"]); exit;
        }
        if ($action === 'bulk_delete') {
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id=:id");
            $c = 0; foreach ($ids as $id) { $stmt->execute([':id' => (int)$id]); $c += $stmt->rowCount(); }
            echo json_encode(['success' => true, 'message' => "🗑️ $c টি ডিলিট হয়েছে"]); exit;
        }
    } catch (PDOException $e) {
        error_log('Dashboard Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'ডেটাবেজ ইরর']); exit;
    }
}

// ─── Fetch Data ───
$statusFilter = $_GET['status'] ?? '';
$pFilter = $_GET['payment'] ?? '';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$validSorts = ['id' => 'o.id', 'created_at' => 'o.created_at', 'name' => 'o.name', 'amount' => 'o.amount', 'status' => 'o.status'];
$sortCol = $validSorts[$sort] ?? 'o.created_at';
$orderDir = $order === 'ASC' ? 'ASC' : 'DESC';

try {
    $pdo = getDBConnection();
    $where = []; $params = [];

    if ($statusFilter && in_array($statusFilter, ['pending','processing','completed','cancelled'])) {
        $where[] = 'o.status = :status'; $params[':status'] = $statusFilter;
    }
    if ($pFilter && in_array($pFilter, ['unpaid','submitted','verified','rejected'])) {
        $where[] = 'o.payment_status = :pstat'; $params[':pstat'] = $pFilter;
    }
    if ($search) {
        $where[] = '(o.name LIKE :s1 OR o.email LIKE :s2 OR o.phone LIKE :s3 OR o.topic LIKE :s4 OR o.id = :s5 OR o.transaction_id LIKE :s6)';
        $s = '%'.$search.'%';
        foreach ([':s1',':s2',':s3',':s4',':s6'] as $k) $params[$k] = $s;
        $params[':s5'] = is_numeric($search) ? (int)$search : 0;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Counts
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereSQL");
    foreach ($params as $k => $v) $cnt->bindValue($k, $v);
    $cnt->execute(); $total = (int)$cnt->fetchColumn();
    $totalPages = ceil($total / $limit);

    // Orders
    $stmt = $pdo->prepare("SELECT o.* FROM orders o $whereSQL ORDER BY $sortCol $orderDir LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();

    // Summary
    $sc = $pdo->query("SELECT status, COUNT(*) FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $pc = $pdo->query("SELECT payment_status, COUNT(*) FROM orders GROUP BY payment_status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalAll = array_sum($sc);

    // Revenue
    $revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status='verified'")->fetchColumn();
    $todayRev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status='verified' AND DATE(verified_at)=CURDATE()")->fetchColumn();
    $todayOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $monthRev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status='verified' AND MONTH(verified_at)=MONTH(CURDATE()) AND YEAR(verified_at)=YEAR(CURDATE())")->fetchColumn();

    // Recent verified
    $recentVerified = $pdo->query("SELECT * FROM orders WHERE payment_status='verified' ORDER BY verified_at DESC LIMIT 5")->fetchAll();

} catch (Exception $e) {
    $orders = []; $sc = []; $pc = []; $totalAll = 0; $totalPages = 0; $revenue = 0;
    $todayRev = 0; $todayOrders = 0; $monthRev = 0; $recentVerified = [];
}

// ─── Helpers ───
function badge($type, $value) {
    $map = [
        'pending' => ['#fbbf24','পেন্ডিং','⏳'],
        'processing' => ['#3b82f6','প্রসেসিং','⚡'],
        'completed' => ['#10b981','সম্পন্ন','✅'],
        'cancelled' => ['#ef4444','বাতিল','❌'],
        'unpaid' => ['#6b7280','অনাদায়ী','💤'],
        'submitted' => ['#f59e0b','জমা পড়েছে','📩'],
        'verified' => ['#10b981','ভেরিফাইড','💎'],
        'rejected' => ['#ef4444','রিজেক্টেড','🚫'],
        'delivered' => ['#8b5cf6','ডেলিভার্ড','📦'],
        'not_delivered' => ['#6b7280','বাকি আছে','⏳'],
    ];
    $s = $map[$value] ?? $map['pending'];
    $icon = $s[2]; $label = $s[1]; $clr = $s[0];
    return "<span style='display:inline-flex;align-items:center;gap:4px;background:{$clr}15;color:{$clr};padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid {$clr}30'>{$icon} {$label}</span>";
}

function timeAgo($dt) {
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'এইমাত্র';
    if ($diff < 3600) return floor($diff/60).' মিনিট';
    if ($diff < 86400) return floor($diff/3600).' ঘণ্টা';
    if ($diff < 604800) return floor($diff/86400).' দিন';
    return date('d/m/y', strtotime($dt));
}

function sortLink($col, $label, $current, $order) {
    $dir = ($current === $col && $order === 'ASC') ? 'DESC' : 'ASC';
    $ico = $current === $col ? ($order === 'ASC' ? ' ▲' : ' ▼') : '';
    $q = $_GET; $q['sort'] = $col; $q['order'] = $dir;
    return '<a href="?'.http_build_query($q).'" style="color:inherit;text-decoration:none">'.$label.$ico.'</a>';
}

$adminName = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$adminInitial = mb_substr($_SESSION['admin_username'] ?? 'A', 0, 1);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ড্যাশবোর্ড • স্ক্রিপ্টবিডি</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #08080f; --sbg: #0f0f1a; --card: #141428; --card-hover: #1a1a35;
  --accent: #ff6b35; --accent2: #ff3366; --accent-glow: rgba(255,107,53,.15);
  --txt: #e8e6f0; --dim: #8a88a0; --border: #22223a;
  --green: #10b981; --blue: #3b82f6; --red: #ef4444; --gold: #f59e0b; --purple: #8b5cf6;
  --radius: 14px; --radius-sm: 10px;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  font-family: 'Noto Sans Bengali', system-ui, -apple-system, sans-serif;
  background: var(--bg); color: var(--txt); min-height: 100vh;
  background-image: radial-gradient(ellipse at 20% 0%, rgba(255,107,53,.04) 0%, transparent 50%),
                    radial-gradient(ellipse at 80% 100%, rgba(139,92,246,.04) 0%, transparent 50%);
}

/* ─── Animated BG Orbs ─── */
.bg-orb { position: fixed; border-radius: 50%; filter: blur(100px); opacity: .03; z-index: 0; pointer-events: none; }
.bg-orb-1 { width: 600px; height: 600px; background: var(--accent); top: -200px; right: -200px; animation: drift1 20s infinite; }
.bg-orb-2 { width: 400px; height: 400px; background: var(--purple); bottom: -100px; left: -100px; animation: drift2 25s infinite; }
.bg-orb-3 { width: 300px; height: 300px; background: var(--accent2); top: 50%; left: 50%; animation: drift3 18s infinite; }
@keyframes drift1 { 0%,100%{transform:translate(0,0)}33%{transform:translate(-100px,50px)}66%{transform:translate(50px,-30px)} }
@keyframes drift2 { 0%,100%{transform:translate(0,0)}50%{transform:translate(80px,-40px)} }
@keyframes drift3 { 0%,100%{transform:translate(0,0)}50%{transform:translate(-60px,60px)} }
.app { position: relative; z-index: 1; }

/* ─── Navbar ─── */
.nav {
  background: rgba(20,20,40,.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border); padding: 0 28px; height: 64px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
}
.nav-l { display: flex; align-items: center; gap: 12px; }
.nav-logo {
  font-size: 20px; font-weight: 900;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.nav-badge {
  background: var(--accent); color: #fff; font-size: 10px; padding: 2px 8px;
  border-radius: 20px; font-weight: 600; letter-spacing: .5px;
}
.nav-r { display: flex; align-items: center; gap: 16px; }
.nav-user { display: flex; align-items: center; gap: 10px; font-size: 13px; }
.nav-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 14px; color: #fff;
  box-shadow: 0 0 20px var(--accent-glow);
}
.nav-time { font-size: 11px; color: var(--dim); }
.btn-out {
  background: transparent; border: 1px solid var(--border); color: var(--dim);
  padding: 7px 16px; border-radius: 8px; cursor: pointer; font: inherit;
  font-size: 12px; text-decoration: none; transition: .3s;
}
.btn-out:hover { border-color: var(--red); color: var(--red); }
.btn-out.go-home { border-color: var(--accent)50; color: var(--accent); }
.btn-out.go-home:hover { background: var(--accent)10; }

/* ─── Main Layout ─── */
.main { padding: 28px; max-width: 1600px; margin: 0 auto; }

/* ─── Quick Actions Bar ─── */
.quick-bar {
  display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; align-items: center;
}
.quick-btn {
  background: var(--card); border: 1px solid var(--border); color: var(--dim);
  padding: 8px 16px; border-radius: 20px; cursor: pointer; font: inherit;
  font-size: 12px; transition: .3s; display: flex; align-items: center; gap: 6px;
}
.quick-btn:hover { border-color: var(--accent); color: var(--txt); background: var(--card-hover); }
.quick-btn.active { background: var(--accent)15; color: var(--accent); border-color: var(--accent); }
.quick-btn.danger { color: var(--red); border-color: var(--red)30; }
.quick-btn.danger:hover { background: var(--red)15; }

/* ─── Revenue Cards ─── */
.rev-cards {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 14px; margin-bottom: 24px;
}
.rev-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 20px; position: relative; overflow: hidden;
  transition: all .3s; cursor: default;
}
.rev-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--accent), var(--accent2));
  opacity: 0; transition: .3s;
}
.rev-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,.3); border-color: var(--accent)40; }
.rev-card:hover::before { opacity: 1; }
.rev-icon { font-size: 28px; margin-bottom: 8px; }
.rev-value { font-size: 28px; font-weight: 900; letter-spacing: -.5px; }
.rev-label { font-size: 11px; color: var(--dim); margin-top: 4px; text-transform: uppercase; letter-spacing: .5px; }
.rev-card.total .rev-value { color: var(--txt); }
.rev-card.revenue .rev-value { color: var(--green); }
.rev-card.today .rev-value { color: var(--accent); }
.rev-card.month .rev-value { color: var(--purple); }

/* ─── Status Grid ─── */
.stats-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 10px; margin-bottom: 20px;
}
.stat-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 14px;
  cursor: pointer; transition: .3s; text-decoration: none; color: inherit;
  position: relative; overflow: hidden;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.2); }
.stat-card.active { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent)30; }
.stat-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.stat-num { font-size: 24px; font-weight: 800; }
.stat-label { font-size: 10px; color: var(--dim); margin-top: 2px; text-transform: uppercase; letter-spacing: .5px; }
.stat-bar { height: 2px; margin-top: 8px; border-radius: 2px; background: var(--border); overflow: hidden; }
.stat-bar-fill { height: 100%; border-radius: 2px; transition: width .6s; }

/* ─── Payment Grid ─── */
.pay-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
  gap: 10px; margin-bottom: 20px;
}
.pay-card {
  background: var(--sbg); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 12px; text-align: center;
  cursor: pointer; transition: .3s; text-decoration: none; color: inherit;
}
.pay-card:hover { border-color: var(--accent)40; transform: translateY(-1px); }
.pay-card.active { border-color: var(--accent); background: var(--accent)10; }
.pay-card .pn { font-size: 22px; font-weight: 800; }
.pay-card .pl { font-size: 10px; color: var(--dim); margin-top: 2px; }

/* ─── Toolbar ─── */
.toolbar {
  display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; align-items: center;
  background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 10px 14px;
}
.toolbar-search {
  flex: 1; min-width: 180px; background: var(--sbg); border: 1px solid var(--border);
  border-radius: 8px; padding: 9px 14px; color: var(--txt); font: inherit; font-size: 13px; outline: none;
}
.toolbar-search:focus { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent)15; }
.toolbar-search::placeholder { color: var(--dim); font-size: 12px; }
.btn-accent {
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  color: #fff; border: none; padding: 9px 18px; border-radius: 8px;
  cursor: pointer; font: inherit; font-size: 12px; font-weight: 700;
  transition: .3s; white-space: nowrap; box-shadow: 0 4px 14px var(--accent-glow);
}
.btn-accent:hover { transform: translateY(-1px); box-shadow: 0 6px 20px var(--accent-glow); }
.btn-accent:active { transform: scale(.97); }
.count-badge { font-size: 11px; color: var(--dim); white-space: nowrap; }

/* ─── Selection Bar ─── */
.selection-bar {
  display: none; align-items: center; gap: 10px; padding: 10px 14px;
  background: var(--accent)08; border: 1px solid var(--accent)30;
  border-radius: var(--radius-sm); margin-bottom: 14px;
}
.selection-bar.active { display: flex; }
.sel-count { font-size: 13px; font-weight: 600; color: var(--accent); }

/* ─── Table ─── */
.table-wrap {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); overflow: hidden;
}
.tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.tbl thead { position: sticky; top: 0; }
.tbl th {
  background: var(--sbg); padding: 13px 12px; text-align: left;
  font-size: 10px; text-transform: uppercase; color: var(--dim); letter-spacing: .8px;
  border-bottom: 1px solid var(--border); white-space: nowrap; font-weight: 600;
}
.tbl td { padding: 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.tbl tbody tr { transition: .2s; }
.tbl tbody tr:hover { background: rgba(255,107,53,.02); }
.tbl tbody tr.selected { background: var(--accent)08; }
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl .col-id { color: var(--accent); font-weight: 700; cursor: pointer; min-width: 50px; }
.tbl .col-id:hover { text-decoration: underline; }
.tbl .col-name { font-weight: 500; min-width: 100px; }
.tbl .col-topic { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tbl .col-amount { font-weight: 700; }
.tbl .col-time { font-size: 11px; color: var(--dim); white-space: nowrap; }

.cb { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }

/* ─── Action Buttons ─── */
.actions { display: flex; gap: 5px; flex-wrap: wrap; }
.btn-xs {
  padding: 5px 10px; border: none; border-radius: 6px; cursor: pointer;
  font: inherit; font-size: 11px; font-weight: 600; transition: .2s; white-space: nowrap;
}
.btn-xs:active { transform: scale(.94); }
.btn-verify { background: rgba(16,185,129,.12); color: var(--green); border: 1px solid rgba(16,185,129,.2); }
.btn-verify:hover { background: rgba(16,185,129,.2); }
.btn-deliver { background: rgba(139,92,246,.12); color: var(--purple); border: 1px solid rgba(139,92,246,.2); }
.btn-deliver:hover { background: rgba(139,92,246,.2); }
.btn-view { background: rgba(59,130,246,.12); color: var(--blue); border: 1px solid rgba(59,130,246,.2); }
.btn-view:hover { background: rgba(59,130,246,.2); }
.btn-delete { background: rgba(239,68,68,.12); color: var(--red); border: 1px solid rgba(239,68,68,.2); }
.btn-delete:hover { background: rgba(239,68,68,.2); }
.btn-status {
  background: var(--sbg); border: 1px solid var(--border); color: var(--dim);
  font-size: 10px; padding: 4px 8px; border-radius: 6px; cursor: pointer;
}
.btn-status:hover { border-color: var(--accent); color: var(--txt); }

/* ─── Empty State ─── */
.empty {
  text-align: center; padding: 60px 24px;
}
.empty-icon { font-size: 48px; margin-bottom: 12px; opacity: .5; }
.empty-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.empty-sub { font-size: 12px; color: var(--dim); }

/* ─── Modal ─── */
.modal-overlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.75);
  backdrop-filter: blur(6px); z-index: 200; align-items: center; justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 18px; padding: 28px; width: 92%; max-width: 680px;
  max-height: 85vh; overflow-y: auto; position: relative;
  box-shadow: 0 20px 60px rgba(0,0,0,.5);
  animation: modalIn .3s;
}
@keyframes modalIn { from { opacity: 0; transform: scale(.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.modal-close {
  position: absolute; top: 12px; right: 12px; width: 32px; height: 32px;
  border-radius: 50%; border: 1px solid var(--border); background: var(--sbg);
  color: var(--dim); cursor: pointer; font-size: 16px;
  display: flex; align-items: center; justify-content: center; transition: .2s;
}
.modal-close:hover { border-color: var(--red); color: var(--red); }
.modal h2 {
  font-size: 20px; margin-bottom: 20px; font-weight: 800;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.detail-row { margin-bottom: 12px; }
.detail-label { font-size: 10px; text-transform: uppercase; color: var(--dim); letter-spacing: .5px; margin-bottom: 3px; }
.detail-val { font-size: 14px; font-weight: 500; }
.msg-box {
  background: var(--sbg); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 14px; margin-top: 4px; font-size: 13px; line-height: 1.7; white-space: pre-wrap;
  max-height: 150px; overflow-y: auto;
}
.status-flow {
  display: flex; gap: 6px; margin: 14px 0; flex-wrap: wrap;
}
.status-step {
  padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
  background: var(--sbg); border: 1px solid var(--border); color: var(--dim);
}
.status-step.done { background: var(--green)15; color: var(--green); border-color: var(--green)30; }
.status-step.current { background: var(--accent)15; color: var(--accent); border-color: var(--accent); }
.modal-actions { display: flex; gap: 8px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); flex-wrap: wrap; }
.btn {
  color: #fff; border: none; padding: 9px 18px; border-radius: 8px;
  cursor: pointer; font: inherit; font-size: 13px; font-weight: 600; transition: .2s;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent2)); box-shadow: 0 4px 14px var(--accent-glow); }
.btn-primary:hover { transform: translateY(-1px); }
.btn-green { background: var(--green); }
.btn-purple { background: var(--purple); }
.btn-red { background: var(--red); }
.btn-blue { background: var(--blue); }
.notes-area {
  width: 100%; background: var(--sbg); border: 1px solid var(--border);
  border-radius: 8px; color: var(--txt); padding: 10px; font: inherit; font-size: 12px;
  resize: vertical; min-height: 60px; outline: none;
}
.notes-area:focus { border-color: var(--accent); }
.status-select {
  background: var(--sbg); border: 1px solid var(--border);
  color: var(--txt); padding: 6px 10px; border-radius: 6px;
  font: inherit; font-size: 11px; cursor: pointer;
}

/* ─── Pagination ─── */
.pagination { display: flex; gap: 6px; justify-content: center; margin-top: 22px; flex-wrap: wrap; align-items: center; }
.page-link {
  padding: 8px 14px; background: var(--card); border: 1px solid var(--border);
  border-radius: 8px; color: var(--dim); text-decoration: none; font-size: 12px; transition: .2s;
}
.page-link:hover { border-color: var(--accent); color: var(--txt); }
.page-link.active { background: var(--accent); color: #fff; border-color: var(--accent); font-weight: 700; }
.page-info { font-size: 11px; color: var(--dim); padding: 0 8px; }

/* ─── Toast ─── */
.toast-ctr { position: fixed; bottom: 24px; right: 24px; z-index: 300; display: flex; flex-direction: column; gap: 8px; }
.toast {
  padding: 12px 20px; border-radius: 10px; font-size: 13px; font-weight: 600;
  box-shadow: 0 10px 30px rgba(0,0,0,.5); animation: toastIn .3s;
  display: flex; align-items: center; gap: 8px;
}
@keyframes toastIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
.toast-success { background: var(--green); color: #000; }
.toast-error { background: var(--red); color: #fff; }

/* ─── Mobile ─── */
@media (max-width: 768px) {
  .nav { padding: 0 14px; }
  .main { padding: 14px 10px; }
  .rev-cards { grid-template-columns: repeat(2, 1fr); }
  .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 6px; }
  .pay-grid { grid-template-columns: repeat(3, 1fr); gap: 6px; }
  .stat-card { padding: 10px; }
  .stat-num { font-size: 18px; }
  .stat-label { font-size: 9px; }
  .toolbar { padding: 8px 10px; flex-direction: column; }
  .toolbar-search { width: 100%; }
  .tbl { font-size: 11px; }
  .tbl th, .tbl td { padding: 8px 6px; }
  .tbl .col-topic { max-width: 80px; }
  .modal { padding: 18px; width: 96%; }
  .modal-grid { grid-template-columns: 1fr; }
  .quick-bar { gap: 6px; }
  .quick-btn { padding: 6px 12px; font-size: 11px; }
}
</style>
</head>
<body>

<!-- Background Orbs -->
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>
<div class="bg-orb bg-orb-3"></div>

<main class="app">

<!-- Nav -->
<nav class="nav">
  <div class="nav-l">
    <span class="nav-logo">📜 ScriptBD</span>
    <span class="nav-badge">ADMIN</span>
  </div>
  <div class="nav-r">
    <a href="https://scriptbd.com" target="_blank" class="btn-out go-home">🏠 সাইট দেখুন</a>
    <div class="nav-user">
      <div class="nav-avatar"><?= $adminInitial ?></div>
      <div>
        <div style="font-weight:600"><?= $adminName ?></div>
        <div class="nav-time">🟢 অনলাইন</div>
      </div>
    </div>
    <a href="logout.php" class="btn-out">🚪 লগআউট</a>
  </div>
</nav>

<div class="main">

  <!-- Quick Actions -->
  <div class="quick-bar">
    <a href="dashboard.php" class="quick-btn <?= !$statusFilter && !$pFilter && !$search ? 'active' : '' ?>">📋 সব অর্ডার</a>
    <a href="?payment=submitted" class="quick-btn <?= $pFilter=='submitted'?'active':'' ?>">💳 ভেরিফাই করা বাকি</a>
    <a href="?status=pending" class="quick-btn <?= $statusFilter=='pending'?'active':'' ?>">⏳ নতুন অর্ডার</a>
    <a href="?status=processing" class="quick-btn <?= $statusFilter=='processing'?'active':'' ?>">⚡ প্রসেসিং</a>
    <button class="quick-btn danger" onclick="bulkDelete()" id="bulkDelBtn" style="display:none">🗑️ সিলেক্টেড ডিলিট</button>
  </div>

  <!-- Revenue Overview -->
  <div class="rev-cards">
    <div class="rev-card total">
      <div class="rev-icon">📊</div>
      <div class="rev-value"><?= number_format($revenue) ?> ৳</div>
      <div class="rev-label">মোট আয় (ভেরিফাইড)</div>
    </div>
    <div class="rev-card today">
      <div class="rev-icon">📅</div>
      <div class="rev-value"><?= number_format($todayRev) ?> ৳</div>
      <div class="rev-label">আজকের আয় • <?= $todayOrders ?> অর্ডার</div>
    </div>
    <div class="rev-card month">
      <div class="rev-icon">📈</div>
      <div class="rev-value"><?= number_format($monthRev) ?> ৳</div>
      <div class="rev-label">এই মাসের আয়</div>
    </div>
    <div class="rev-card revenue">
      <div class="rev-icon">🎯</div>
      <div class="rev-value"><?= $totalAll ?></div>
      <div class="rev-label">মোট অর্ডার</div>
    </div>
  </div>

  <!-- Order Status Stats -->
  <div class="stats-grid">
    <?php
    $statusItems = [
      'all' => ['📋', 'মোট', $totalAll, 'var(--txt)'],
      'pending' => ['⏳', 'পেন্ডিং', $sc['pending']??0, 'var(--gold)'],
      'processing' => ['⚡', 'প্রসেসিং', $sc['processing']??0, 'var(--blue)'],
      'completed' => ['✅', 'সম্পন্ন', $sc['completed']??0, 'var(--green)'],
      'cancelled' => ['❌', 'বাতিল', $sc['cancelled']??0, 'var(--red)'],
    ];
    foreach ($statusItems as $key => [$icon, $label, $count, $color]):
      $active = ($key === 'all' ? !$statusFilter : $statusFilter === $key);
      $href = $key === 'all' ? 'dashboard.php' : "?status=$key";
      $pct = $totalAll > 0 ? round($count / $totalAll * 100) : 0;
    ?>
    <a href="<?= $href ?>" class="stat-card <?= $active ? 'active' : '' ?>" style="--clr:<?= $color ?>">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="stat-num" style="color:<?= $color ?>"><?= $count ?></span>
        <span style="font-size:18px"><?= $icon ?></span>
      </div>
      <div class="stat-label"><?= $label ?></div>
      <div class="stat-bar"><div class="stat-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Payment Stats -->
  <div class="pay-grid">
    <?php
    $payItems = [
      'submitted' => ['📩', 'জমা পড়েছে', $pc['submitted']??0, 'var(--gold)'],
      'unpaid' => ['💤', 'অনাদায়ী', $pc['unpaid']??0, 'var(--dim)'],
      'verified' => ['💎', 'ভেরিফাইড', $pc['verified']??0, 'var(--green)'],
      'rejected' => ['🚫', 'রিজেক্ট', $pc['rejected']??0, 'var(--red)'],
    ];
    foreach ($payItems as $key => [$icon, $label, $count, $color]):
      $active = $pFilter === $key;
    ?>
    <a href="?payment=<?= $key ?>" class="pay-card <?= $active ? 'active' : '' ?>">
      <div class="pn" style="color:<?= $color ?>"><?= $count ?></div>
      <div class="pl"><?= $icon ?> <?= $label ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Recent Verified (when no filter) -->
  <?php if (!$statusFilter && !$pFilter && !$search && $recentVerified): ?>
  <div style="margin-bottom:20px;font-size:12px;color:var(--dim);display:flex;align-items:center;gap:10px">
    <span style="font-weight:600;color:var(--txt)">🕐 সর্বশেষ ভেরিফাইড:</span>
    <?php foreach ($recentVerified as $rv): ?>
      <span style="background:var(--card);border:1px solid var(--border);padding:3px 10px;border-radius:20px;font-size:11px">
        #<?= $rv['id'] ?> <?= htmlspecialchars($rv['name']) ?> • ৳<?= number_format($rv['amount']??0) ?>
      </span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Toolbar -->
  <div class="toolbar">
    <input class="toolbar-search" id="search" placeholder="🔍 অর্ডার খুঁজুন — নাম, ইমেইল, ফোন, টপিক, TrxID, ID..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn-accent" onclick="doSearch()">🔍 খুঁজুন</button>
    <?php if ($statusFilter || $pFilter || $search): ?>
      <a href="dashboard.php" class="btn-out">✕ ফিল্টার রিসেট</a>
    <?php endif; ?>
    <span class="count-badge"><?= $total ?> টি ফলাফল • পৃষ্ঠা <?= $page ?>/<?= max(1,$totalPages) ?></span>
  </div>

  <!-- Selection Bar -->
  <div class="selection-bar" id="selBar">
    <span class="sel-count" id="selCount">0 টি সিলেক্ট</span>
    <button class="btn-xs btn-verify" onclick="bulkVerify()">✅ সিলেক্টেড ভেরিফাই</button>
    <button class="btn-xs btn-delete" onclick="bulkDelete()">🗑️ সিলেক্টেড ডিলিট</button>
    <button class="btn-xs btn-view" onclick="clearSelection()">✕ ক্লিয়ার</button>
  </div>

  <!-- Orders Table -->
  <div class="table-wrap">
    <?php if (empty($orders)): ?>
      <div class="empty">
        <div class="empty-icon">📭</div>
        <div class="empty-title">কোনো অর্ডার পাওয়া যায়নি</div>
        <div class="empty-sub">ফিল্টার পরিবর্তন করে দেখুন অথবা নতুন অর্ডারের জন্য অপেক্ষা করুন</div>
      </div>
    <?php else: ?>
    <table class="tbl" id="ordersTable">
      <thead>
        <tr>
          <th style="width:30px"><input type="checkbox" class="cb" id="selectAll" onclick="toggleAll(this)" title="সব সিলেক্ট"></th>
          <th><?= sortLink('id', '#', $sort, $order) ?></th>
          <th><?= sortLink('name', 'নাম', $sort, $order) ?></th>
          <th>প্ল্যান</th>
          <th>টপিক</th>
          <th><?= sortLink('amount', 'টাকা', $sort, $order) ?></th>
          <th>পেমেন্ট</th>
          <th>ডেলিভারি</th>
          <th><?= sortLink('status', 'স্ট্যাটাস', $sort, $order) ?></th>
          <th><?= sortLink('created_at', 'তারিখ', $sort, $order) ?></th>
          <th>অ্যাকশন</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
      <tr id="row-<?= $o['id'] ?>">
        <td><input type="checkbox" class="cb row-cb" value="<?= $o['id'] ?>" onchange="updateSelection()"></td>
        <td class="col-id" onclick="viewOrder(<?= $o['id'] ?>)">#<?= $o['id'] ?></td>
        <td class="col-name" title="<?= htmlspecialchars($o['name']) ?>"><?= htmlspecialchars(mb_strlen($o['name'])>20 ? mb_substr($o['name'],0,17).'...' : $o['name']) ?></td>
        <td><span style="text-transform:capitalize;font-size:11px"><?= str_replace('-',' ',htmlspecialchars($o['plan'])) ?></span></td>
        <td class="col-topic" title="<?= htmlspecialchars($o['topic']) ?>"><?= htmlspecialchars($o['topic']) ?></td>
        <td class="col-amount">৳<?= number_format($o['amount']??0) ?></td>
        <td>
          <?= badge('payment', $o['payment_status']??'unpaid') ?>
          <?php if ($o['transaction_id']): ?><br><small style="color:var(--dim);font-size:10px"><?= htmlspecialchars($o['transaction_id']) ?></small><?php endif; ?>
        </td>
        <td><?= badge('delivery', ($o['delivery_status']??'') ?: ($o['status']=='completed'?'delivered':'not_delivered')) ?></td>
        <td><?= badge('status', $o['status']) ?></td>
        <td class="col-time" title="<?= $o['created_at'] ?>"><?= timeAgo($o['created_at']) ?></td>
        <td>
          <div class="actions">
            <button class="btn-xs btn-view" onclick="viewOrder(<?= $o['id'] ?>)" title="বিস্তারিত">👁️</button>
            <?php if (($o['payment_status']??'') == 'submitted'): ?>
              <button class="btn-xs btn-verify" onclick="verifyPayment(<?= $o['id'] ?>)" title="ভেরিফাই">✅</button>
            <?php endif; ?>
            <?php if (($o['payment_status']??'') == 'verified' && ($o['delivery_status']??'') != 'delivered'): ?>
              <button class="btn-xs btn-deliver" onclick="markDelivered(<?= $o['id'] ?>)" title="ডেলিভার">📦</button>
            <?php endif; ?>
            <?php if ($o['status'] == 'pending'): ?>
              <button class="btn-xs btn-status" onclick="quickStatus(<?= $o['id'] ?>,'processing')" title="প্রসেসিং">⚡</button>
            <?php endif; ?>
            <?php if ($o['status'] == 'processing'): ?>
              <button class="btn-xs btn-status" onclick="quickStatus(<?= $o['id'] ?>,'completed')" title="সম্পন্ন">✅</button>
            <?php endif; ?>
            <button class="btn-xs btn-delete" onclick="delOrder(<?= $o['id'] ?>)" title="ডিলিট">🗑️</button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <?php $pq = $_GET; $pq['page'] = $page - 1; ?>
      <a href="?<?= http_build_query($pq) ?>" class="page-link">← আগে</a>
    <?php endif; ?>
    <?php
    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++):
      $pq2 = $_GET; $pq2['page'] = $i;
    ?>
      <a href="?<?= http_build_query($pq2) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
      <?php $pq3 = $_GET; $pq3['page'] = $page + 1; ?>
      <a href="?<?= http_build_query($pq3) ?>" class="page-link">পরে →</a>
    <?php endif; ?>
    <span class="page-info"><?= $total ?> টি অর্ডার</span>
  </div>
  <?php endif; ?>

</div><!-- /main -->
</main><!-- /app -->

<!-- Modal -->
<div class="modal-overlay" id="orderModal">
  <div class="modal" id="modalInner"></div>
</div>

<!-- Toast Container -->
<div class="toast-ctr" id="toastCtr"></div>

<script>
// ─── SEARCH ───
function doSearch() {
  const q = document.getElementById('search').value.trim();
  const p = new URLSearchParams(window.location.search);
  q ? p.set('search', q) : p.delete('search');
  p.set('page', '1');
  window.location.search = p.toString();
}
document.getElementById('search').addEventListener('keypress', e => { if (e.key === 'Enter') doSearch(); });

// ─── SELECTION ───
let selected = new Set();
function toggleAll(el) {
  document.querySelectorAll('.row-cb').forEach(cb => {
    cb.checked = el.checked;
    if (el.checked) selected.add(cb.value); else selected.delete(cb.value);
  });
  updateSelection();
}
function updateSelection() {
  selected.clear();
  document.querySelectorAll('.row-cb:checked').forEach(cb => { selected.add(cb.value); });
  const bar = document.getElementById('selBar');
  const bulkBtn = document.getElementById('bulkDelBtn');
  const count = selected.size;
  document.getElementById('selCount').textContent = count + ' টি সিলেক্ট';
  if (count > 0) { bar.classList.add('active'); bulkBtn.style.display = ''; }
  else { bar.classList.remove('active'); bulkBtn.style.display = 'none'; }
  document.getElementById('selectAll').checked = count > 0 && count === document.querySelectorAll('.row-cb').length;
}
function clearSelection() {
  document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
  selected.clear();
  updateSelection();
}
function bulkVerify() {
  if (selected.size === 0) return;
  if (!confirm(selected.size + ' টি পেমেন্ট ভেরিফাই করবেন?')) return;
  apiCall('bulk_verify', { ids: JSON.stringify([...selected]) }).then(r => {
    if (r.success) { toast('success', r.message); setTimeout(() => location.reload(), 600); }
  });
}
function bulkDelete() {
  if (selected.size === 0) {
    alert('দয়া করে অর্ডার সিলেক্ট করুন। Checkbox চেক করুন।');
    return;
  }
  if (!confirm(selected.size + ' টি অর্ডার ডিলিট করবেন? এটি UNDO করা যাবে না!')) return;
  apiCall('bulk_delete', { ids: JSON.stringify([...selected]) }).then(r => {
    if (r.success) { toast('success', r.message); setTimeout(() => location.reload(), 600); }
  });
}
function quickStatus(id, status) {
  apiCall('update_status', { id: id, status: status }).then(r => {
    toast(r.success ? 'success' : 'error', r.message);
    if (r.success) setTimeout(() => location.reload(), 400);
  });
}

// ─── SINGLE ACTIONS ───
function verifyPayment(id) {
  if (!confirm('#' + id + ' পেমেন্ট ভেরিফাই করবেন?')) return;
  apiCall('verify_payment', { id: id }).then(r => {
    toast(r.success ? 'success' : 'error', r.message);
    if (r.success) setTimeout(() => location.reload(), 500);
  });
}
function markDelivered(id) {
  if (!confirm('#' + id + ' ডেলিভারি সম্পন্ন করবেন?')) return;
  apiCall('mark_delivered', { id: id }).then(r => {
    toast(r.success ? 'success' : 'error', r.message);
    if (r.success) setTimeout(() => location.reload(), 500);
  });
}
function delOrder(id) {
  if (!confirm('#' + id + ' ডিলিট করবেন? এটি স্থায়ী হবে।')) return;
  apiCall('delete', { id: id }).then(r => {
    toast(r.success ? 'success' : 'error', r.message);
    if (r.success) setTimeout(() => location.reload(), 500);
  });
}

function saveNotes(id) {
  const n = document.getElementById('n_' + id).value;
  apiCall('save_notes', { id: id, notes: n }).then(r => {
    toast(r.success ? 'success' : 'error', r.message);
  });
}

// ─── ORDER DETAIL MODAL ───
let currentOrderId = null;
function viewOrder(id) {
  currentOrderId = id;
  document.getElementById('orderModal').classList.add('active');
  document.getElementById('modalInner').innerHTML = '<p style="text-align:center;color:var(--dim);padding:40px">⏳ লোড হচ্ছে...</p>';
  apiCall('get_order', { id: id }).then(res => {
    if (!res.success) {
      document.getElementById('modalInner').innerHTML = '<p style="text-align:center;color:var(--red);padding:40px">❌ অর্ডার পাওয়া যায়নি</p>';
      return;
    }
    const o = res.data;
    const plans = { 'youtube-shorts': 'YouTube Shorts (৫টি)', 'facebook-reels': 'Facebook Reels (৫টি)', 'youtube-full': 'YouTube Full (১টি)' };

    // Status flow
    const statusFlow = [];
    statusFlow.push({ label: '📥 অর্ডার', done: true });
    if (o.payment_status === 'submitted') statusFlow.push({ label: '💳 পেমেন্ট জমা', done: false, current: true });
    else if (o.payment_status === 'unpaid') statusFlow.push({ label: '💳 পেমেন্ট জমা', done: false });
    else if (o.payment_status === 'verified') statusFlow.push({ label: '💳 পেমেন্ট জমা', done: true });
    if (o.payment_status === 'verified') {
      if (o.delivery_status === 'delivered' || o.status === 'completed') {
        statusFlow.push({ label: '✅ ভেরিফাই', done: true });
        statusFlow.push({ label: '📦 ডেলিভারি', done: true });
      } else {
        statusFlow.push({ label: '✅ ভেরিফাই', done: true });
        statusFlow.push({ label: '📦 ডেলিভারি', done: false, current: true });
      }
    } else if (o.payment_status === 'submitted') {
      statusFlow.push({ label: '✅ ভেরিফাই', done: false });
    }

    const flowHTML = statusFlow.map(s => {
      let cls = '';
      if (s.current) cls = 'current';
      else if (s.done) cls = 'done';
      return '<span class="status-step ' + cls + '">' + s.label + '</span>';
    }).join(' → ');

    const isCompleted = o.status === 'completed' || o.delivery_status === 'delivered';

    document.getElementById('modalInner').innerHTML =
      '<button class="modal-close" onclick="closeModal()">✕</button>' +
      '<h2>📋 অর্ডার #' + o.id + ' <span style="font-size:11px;color:var(--dim);font-weight:400">' + (o.created_at||'') + '</span></h2>' +

      // Status Flow
      '<div style="margin-bottom:18px"><div class="status-flow">' + flowHTML + '</div></div>' +

      // Grid
      '<div class="modal-grid">' +
        '<div class="detail-row"><div class="detail-label">👤 নাম</div><div class="detail-val">' + esc(o.name) + '</div></div>' +
        '<div class="detail-row"><div class="detail-label">📧 ইমেইল</div><div class="detail-val">' + esc(o.email || '—') + '</div></div>' +
        '<div class="detail-row"><div class="detail-label">📱 ফোন</div><div class="detail-val">' + esc(o.phone) + '</div></div>' +
        '<div class="detail-row"><div class="detail-label">💼 প্ল্যান</div><div class="detail-val" style="text-transform:capitalize">' + esc(plans[o.plan] || o.plan) + ' • ৳' + (o.amount||0) + '</div></div>' +
        '<div class="detail-row"><div class="detail-label">📝 টপিক</div><div class="detail-val">' + esc(o.topic || '—') + '</div></div>' +
        '<div class="detail-row"><div class="detail-label">💳 পেমেন্ট মেথড</div><div class="detail-val" style="text-transform:uppercase">' + esc(o.payment_method || '—') + '</div></div>' +
        '<div class="detail-row"><div class="detail-label">🔢 TrxID</div><div class="detail-val" style="font-family:monospace">' + esc(o.transaction_id || '—') + '</div></div>' +
        '<div class="detail-row"><div class="detail-label">📊 স্ট্যাটাস</div><div class="detail-val">' +
          '<select class="status-select" onchange="updateStatus(' + o.id + ', this.value)">' +
            ['pending','processing','completed','cancelled'].map(s => '<option value="' + s + '"' + (o.status === s ? ' selected' : '') + '>' + s + '</option>').join('') +
          '</select>' +
        '</div></div>' +
      '</div>' +

      // Message
      '<div class="detail-row" style="margin-top:12px"><div class="detail-label">💬 গ্রাহকের মেসেজ</div>' +
        '<div class="msg-box">' + esc(o.message || '(কোনো মেসেজ নেই)') + '</div></div>' +

      // Notes
      '<div class="detail-row"><div class="detail-label">📝 অ্যাডমিন নোট</div>' +
        '<textarea class="notes-area" id="n_' + o.id + '" rows="2" placeholder="নোট লিখুন...">' + esc(o.admin_notes || '') + '</textarea>' +
        '<button class="btn-xs btn-view" style="margin-top:6px" onclick="saveNotes(' + o.id + ')">💾 সেভ</button></div>' +

      // Infobar
      (o.delivered_by ? '<div style="margin-top:8px;font-size:11px;color:var(--dim)">📦 ডেলিভার করেছেন: ' + esc(o.delivered_by) + ' • ' + (o.delivery_date||'') + '</div>' : '') +
      (o.verified_at ? '<div style="font-size:11px;color:var(--dim)">✅ ভেরিফাই: ' + o.verified_at + '</div>' : '') +

      // Actions
      '<div class="modal-actions">' +
        (o.payment_status === 'submitted'
          ? '<button class="btn btn-green" onclick="verifyPayment(' + o.id + ')">✅ পেমেন্ট ভেরিফাই করুন</button>'
          : '') +
        ((o.payment_status === 'verified' || o.payment_status === 'submitted') && (o.delivery_status !== 'delivered' && o.status !== 'completed')
          ? '<button class="btn btn-purple" onclick="markDelivered(' + o.id + ')">📦 ডেলিভারি সম্পন্ন করুন</button>'
          : '') +
        (o.status === 'pending'
          ? '<button class="btn btn-blue" onclick="quickStatus(' + o.id + ','processing')">⚡ প্রসেসিং এ নিন</button>'
          : '') +
        '<button class="btn btn-red" onclick="closeModal();delOrder(' + o.id + ')">🗑️ অর্ডার ডিলিট করুন</button>' +
      '</div>';
  });
}

function updateStatus(id, status) {
  apiCall('update_status', { id: id, status: status }).then(r => {
    toast(r.success ? 'success' : 'error', r.message);
    if (r.success) setTimeout(() => location.reload(), 400);
  });
}

function closeModal() { document.getElementById('orderModal').classList.remove('active'); }
document.getElementById('orderModal').addEventListener('click', e => {
  if (e.target === document.getElementById('orderModal')) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ─── API Helper ───
function apiCall(action, data) {
  const fd = new URLSearchParams({ action: action, ...data });
  return fetch('dashboard.php', { method: 'POST', body: fd })
    .then(r => r.json());
}

// ─── Toast ───
function toast(type, msg) {
  const el = document.createElement('div');
  el.className = 'toast toast-' + type;
  el.textContent = msg;
  document.getElementById('toastCtr').appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = '.3s'; setTimeout(() => el.remove(), 300); }, 3000);
}

// ─── Escape ───
function esc(s) {
  if (!s) return '';
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

// ─── Refresh data every 60s ───
setInterval(() => {
  fetch('dashboard.php?ajax=stats', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .catch(() => {});
}, 60000);
</script>

</body>
</html>