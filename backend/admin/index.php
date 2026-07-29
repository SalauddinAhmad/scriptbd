<?php
error_reporting(0);
session_start();

define('DB_HOST','localhost');
define('DB_NAME','scriptbd_scriptbd_db');
define('DB_USER','scriptbd_scriptbd_user');
define('DB_PASS','Sbd@2026!Pro');

if(isset($_GET['logout'])){session_destroy();header('Location:index.php');exit;}
if(isset($_POST['user'],$_POST['pass'])&&$_POST['user']==='admin'&&$_POST['pass']==='admin123'){$_SESSION['ad']=1;$_SESSION['an']='admin';}

if(empty($_SESSION['ad'])){
?><!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Admin Login • ScriptBD Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --bg: #09090b; --card: rgba(24, 24, 27, 0.6); --border: rgba(255, 255, 255, 0.08);
            --accent: #6366f1; --accent-hover: #4f46e5; --text: #f4f4f5; --muted: #a1a1aa;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', 'Noto Sans Bengali', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
        
        /* Premium Background Effects */
        .glow-1 { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, rgba(0,0,0,0) 70%); top: -200px; left: -200px; border-radius: 50%; pointer-events: none; }
        .glow-2 { position: absolute; width: 500px; height: 500px; background: radial-gradient(circle, rgba(168,85,247,0.1) 0%, rgba(0,0,0,0) 70%); bottom: -100px; right: -100px; border-radius: 50%; pointer-events: none; }
        
        .login-box {
            background: var(--card); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border); border-radius: 24px; padding: 48px 40px; width: 100%; max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); position: relative; z-index: 10;
        }
        
        .brand { text-align: center; margin-bottom: 32px; }
        .brand-icon { width: 56px; height: 56px; background: linear-gradient(135deg, var(--accent), #a855f7); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; margin-bottom: 16px; box-shadow: 0 8px 20px rgba(99,102,241,0.3); }
        .brand h1 { font-size: 24px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 4px; }
        .brand p { font-size: 13px; color: var(--muted); }
        
        .input-group { margin-bottom: 20px; text-align: left; position: relative; }
        .input-group label { display: block; font-size: 12px; font-weight: 500; color: var(--muted); margin-bottom: 8px; }
        .input-group i { position: absolute; left: 16px; top: 38px; color: #71717a; font-size: 18px; }
        .input-group input { 
            width: 100%; padding: 14px 16px 14px 44px; background: rgba(0, 0, 0, 0.2); 
            border: 1px solid var(--border); color: var(--text); border-radius: 12px; 
            font-family: inherit; font-size: 14px; outline: none; transition: all 0.3s ease;
        }
        .input-group input:focus { border-color: var(--accent); background: rgba(99,102,241,0.05); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        
        .btn-login {
            width: 100%; padding: 14px; background: linear-gradient(135deg, var(--accent), #818cf8); 
            color: #fff; border: none; border-radius: 12px; font-family: inherit; font-size: 15px; 
            font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99,102,241,0.4); }
        .btn-login:active { transform: translateY(0); }
        
        .footer { text-align: center; font-size: 11px; color: #52525b; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="glow-1"></div><div class="glow-2"></div>
    <form method="POST" class="login-box">
        <div class="brand">
            <div class="brand-icon"><i class="ph-bold ph-shield-check"></i></div>
            <h1>ScriptBD Workspace</h1>
            <p>সিকিউর অ্যাডমিন প্যানেল</p>
        </div>
        <div class="input-group">
            <label>ইউজারনেম</label>
            <i class="ph ph-user"></i>
            <input name="user" placeholder="admin" autocomplete="off" autofocus>
        </div>
        <div class="input-group">
            <label>পাসওয়ার্ড</label>
            <i class="ph ph-lock-key"></i>
            <input name="pass" type="password" placeholder="••••••••" value="admin123">
        </div>
        <button type="submit" class="btn-login">লগইন করুন <i class="ph-bold ph-arrow-right"></i></button>
        <div class="footer">© <?=date('Y')?> scriptbd.com • Premium Edition</div>
    </form>
</body>
</html>
<?php exit;}

try {
    $db=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch(Exception $e) {
    die('<div style="background:#18181b;color:#ef4444;padding:30px;margin:40px;border-radius:16px;border:1px solid #3f3f46;font-family:sans-serif;text-align:center"><h2>Database Connection Error</h2><p>'.$e->getMessage().'</p></div>');
}

// AJAX Handling
if(!empty($_POST['a'])){
    header('Content-Type:application/json;charset=utf-8');
    $a=$_POST['a'];$id=(int)($_POST['id']??0);
    try{
        if($a==='v')$db->exec("UPDATE orders SET payment_status='verified',verified_at=NOW() WHERE id=$id");
        elseif($a==='d')$db->exec("UPDATE orders SET delivery_status='delivered',status='completed',delivery_date=NOW(),delivered_by='{$_SESSION['an']}' WHERE id=$id");
        elseif($a==='x')$db->exec("DELETE FROM orders WHERE id=$id");
        elseif($a==='s')$db->exec("UPDATE orders SET status='{$_POST['st']}' WHERE id=$id");
        elseif($a==='n')$db->prepare("UPDATE orders SET admin_notes=? WHERE id=?")->execute([$_POST['notes']??'',$id]);
        elseif($a==='g'){$r=$db->query("SELECT * FROM orders WHERE id=$id")->fetch();echo json_encode(['ok'=>1,'d'=>$r],256);exit;}
        echo json_encode(['ok'=>1],256);exit;
    }catch(Exception $e){echo json_encode(['ok'=>0],256);exit;}
}

$f=$_GET['f']??'';$q=trim($_GET['q']??'');$sort=$_GET['sort']??'id';$ord=$_GET['ord']??'DESC';
$pg=max(1,(int)($_GET['pg']??1));$lim=10;$off=($pg-1)*$lim;

$w='';$p=[];
if(in_array($f,['pending','processing','completed','cancelled'])){$w="WHERE status=?";$p=[$f];}
elseif($f==='submitted')$w="WHERE payment_status='submitted'";
elseif($f==='verified')$w="WHERE payment_status='verified'";
if($q){$s='%'.$q.'%';$n=is_numeric($q)?(int)$q:0;$x=$w?' AND':'WHERE';$w.=" $x (name LIKE ? OR email LIKE ? OR phone LIKE ? OR topic LIKE ? OR id=?)";$p=array_merge($p,[$s,$s,$s,$s,$n]);}

$t=$db->prepare("SELECT COUNT(*) FROM orders $w");$t->execute($p);$t=$t->fetchColumn();
$tp=ceil($t/$lim);
$sc=['id'=>'id','created_at'=>'created_at','name'=>'name','amount'=>'amount','status'=>'status'];
$scol=$sc[$sort]??'id';$odir=$ord==='ASC'?'ASC':'DESC';

$st=$db->prepare("SELECT * FROM orders $w ORDER BY $scol $odir LIMIT $lim OFFSET $off");$st->execute($p);$orders=$st->fetchAll();

$sts=$db->query("SELECT status,COUNT(*) FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$pay=$db->query("SELECT payment_status,COUNT(*) FROM orders GROUP BY payment_status")->fetchAll(PDO::FETCH_KEY_PAIR);
$ta=array_sum($sts);

function bdg($t,$v){
    $m = [
        'pending' => ['#f59e0b', 'ph-hourglass-high', 'পেন্ডিং'],
        'processing' => ['#3b82f6', 'ph-spinner-gap', 'প্রসেসিং'],
        'completed' => ['#10b981', 'ph-check-circle', 'সম্পন্ন'],
        'cancelled' => ['#ef4444', 'ph-x-circle', 'বাতিল'],
        'unpaid' => ['#71717a', 'ph-money', 'অনাদায়ী'],
        'submitted' => ['#8b5cf6', 'ph-envelope-simple-open', 'জমা'],
        'verified' => ['#10b981', 'ph-shield-check', 'ভেরিফাইড'],
        'delivered' => ['#14b8a6', 'ph-package', 'ডেলিভার্ড'],
        'not_delivered' => ['#71717a', 'ph-clock', 'বাকি']
    ];
    $x = $m[$v] ?? $m['pending'];
    return "<div class='badge' style='--c:{$x[0]}'><i class='ph-fill {$x[1]}'></i> <span>{$x[2]}</span></div>";
}
function ago($d){if(!$d)return'—';$df=time()-strtotime($d);if($df<60)return'এইমাত্র';if($df<3600)return floor($df/60).'মি';if($df<86400)return floor($df/3600).'ঘ';if($df<604800)return floor($df/86400).'দিন';return date('d/m/y',strtotime($d));}
$an=htmlspecialchars($_SESSION['an']??'Admin');$ai=mb_substr($an,0,1);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>ড্যাশবোর্ড • ScriptBD Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --bg: #09090b; --sidebar: #09090b; --card: #18181b; --card-hover: #27272a;
            --border: #27272a; --border-light: rgba(255, 255, 255, 0.05);
            --accent: #6366f1; --accent-soft: rgba(99, 102, 241, 0.1); --accent-hover: #4f46e5;
            --text: #f4f4f5; --muted: #a1a1aa; --dim: #71717a;
            --green: #10b981; --red: #ef4444; --gold: #f59e0b; --blue: #3b82f6; --purple: #a855f7;
            --rad-sm: 8px; --rad: 16px; --rad-lg: 24px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }
        
        body { font-family: 'Inter', 'Noto Sans Bengali', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* Layout */
        .sidebar { width: 260px; min-width: 260px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 24px 20px; position: sticky; top: 0; height: 100vh; z-index: 50; }
        .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { position: sticky; top: 0; z-index: 40; background: rgba(9, 9, 11, 0.8); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid var(--border); padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .content-area { padding: 32px; display: flex; flex-direction: column; gap: 24px; }
        
        /* Sidebar Elements */
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding-left: 8px; }
        .brand-icon { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, var(--accent), var(--purple)); display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .brand-text { font-size: 18px; font-weight: 700; letter-spacing: -0.5px; }
        
        .nav-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--dim); font-weight: 600; margin: 20px 0 10px 8px; }
        .nav-link { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; margin-bottom: 4px; }
        .nav-link-left { display: flex; align-items: center; gap: 12px; }
        .nav-link i { font-size: 20px; }
        .nav-link:hover { background: var(--card); color: var(--text); }
        .nav-link.active { background: var(--accent-soft); color: var(--accent); }
        .nav-link.active i { color: var(--accent); }
        .nav-count { background: var(--card-hover); color: var(--text); font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 600; }
        .nav-link.active .nav-count { background: var(--accent); color: #fff; }
        
        .user-profile { margin-top: auto; background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 12px; display: flex; align-items: center; gap: 12px; }
        .avatar { width: 36px; height: 36px; border-radius: 10px; background: var(--accent-soft); color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
        .user-info flex-1 { min-width: 0; }
        .user-info h4 { font-size: 13px; font-weight: 600; }
        .user-info p { font-size: 11px; color: var(--green); display: flex; align-items: center; gap: 4px; margin-top: 2px; }
        .user-info p::before { content:''; width: 6px; height: 6px; background: var(--green); border-radius: 50%; }
        .btn-logout { background: rgba(239, 68, 68, 0.1); color: var(--red); width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; transition: 0.2s; margin-left: auto; }
        .btn-logout:hover { background: var(--red); color: #fff; }

        /* Topbar & Search */
        .greeting h2 { font-size: 20px; font-weight: 700; }
        .greeting p { font-size: 12px; color: var(--muted); margin-top: 4px; display: flex; align-items: center; gap: 6px; }
        
        .search-bar { display: flex; align-items: center; background: var(--card); border: 1px solid var(--border); border-radius: 100px; padding: 6px 6px 6px 16px; min-width: 320px; transition: 0.2s; }
        .search-bar:focus-within { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
        .search-bar i { color: var(--muted); font-size: 18px; }
        .search-bar input { flex: 1; background: transparent; border: none; color: var(--text); padding: 8px 12px; font-family: inherit; font-size: 13px; outline: none; }
        .search-bar input::placeholder { color: var(--dim); }
        .btn-search { background: var(--accent); color: #fff; border: none; padding: 8px 16px; border-radius: 100px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-search:hover { background: var(--accent-hover); }

        /* Dashboard Grid */
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--rad); padding: 20px; display: flex; flex-direction: column; gap: 12px; transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px rgba(0,0,0,0.2); border-color: var(--border-light); }
        .stat-card::after { content:''; position:absolute; top:0; right:0; width: 100px; height: 100px; background: radial-gradient(circle, var(--c) 0%, transparent 70%); opacity: 0.05; border-radius: 50%; pointer-events:none; }
        .stat-header { display: flex; justify-content: space-between; align-items: center; }
        .stat-title { font-size: 13px; color: var(--muted); font-weight: 500; }
        .stat-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--c); }
        .stat-value { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }

        /* Quick Filters */
        .filter-tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
        .filter-tab { display: flex; align-items: center; gap: 8px; padding: 10px 16px; background: var(--card); border: 1px solid var(--border); border-radius: var(--rad-sm); color: var(--muted); font-size: 13px; font-weight: 500; text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .filter-tab:hover { border-color: var(--dim); color: var(--text); }
        .filter-tab.active { background: var(--accent-soft); border-color: var(--accent); color: var(--accent); }
        .filter-badge { font-size: 11px; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 6px; }
        .filter-tab.active .filter-badge { background: var(--accent); color: #fff; }

        /* Table Area */
        .table-container { background: var(--card); border: 1px solid var(--border); border-radius: var(--rad); overflow: hidden; }
        .table-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .table-title { font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(0,0,0,0.2); padding: 14px 20px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--dim); font-weight: 600; white-space: nowrap; border-bottom: 1px solid var(--border); }
        td { padding: 14px 20px; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.02); vertical-align: middle; }
        tr:hover td { background: rgba(255,255,255,0.01); }
        tr:last-child td { border-bottom: none; }
        
        .t-id { font-weight: 600; color: var(--accent); cursor: pointer; text-decoration: none; }
        .t-id:hover { text-decoration: underline; }
        .t-time { font-size: 12px; color: var(--muted); }
        .t-name { font-weight: 500; }
        .t-plan { font-size: 11px; padding: 4px 8px; background: rgba(255,255,255,0.05); border-radius: 6px; color: var(--muted); display: inline-block; text-transform: capitalize; }
        .t-trx { font-size: 10px; color: var(--dim); margin-top: 4px; display: block; font-family: monospace; }
        
        /* Badges */
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: color-mix(in srgb, var(--c) 15%, transparent); border: 1px solid color-mix(in srgb, var(--c) 30%, transparent); color: var(--c); border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .badge i { font-size: 12px; }

        /* Actions */
        .actions { display: flex; gap: 6px; }
        .btn-act { width: 28px; height: 28px; border-radius: 6px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; transition: 0.2s; }
        .btn-act:hover { transform: scale(1.05); }
        .btn-act:active { transform: scale(0.95); }
        .b-view { background: rgba(99,102,241,0.1); color: var(--accent); } .b-view:hover { background: var(--accent); color: #fff; }
        .b-verify { background: rgba(16,185,129,0.1); color: var(--green); } .b-verify:hover { background: var(--green); color: #fff; }
        .b-deliver { background: rgba(168,85,247,0.1); color: var(--purple); } .b-deliver:hover { background: var(--purple); color: #fff; }
        .b-delete { background: rgba(239,68,68,0.1); color: var(--red); } .b-delete:hover { background: var(--red); color: #fff; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 6px; padding: 20px; }
        .page-link { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--muted); text-decoration: none; font-size: 13px; font-weight: 500; transition: 0.2s; }
        .page-link:hover { border-color: var(--accent); color: var(--text); }
        .page-link.active { background: var(--accent); border-color: var(--accent); color: #fff; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state i { font-size: 48px; color: var(--border); margin-bottom: 16px; }
        .empty-state p { font-size: 14px; }

        /* Modal Premium */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.show { display: flex; opacity: 1; }
        .modal-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--rad-lg); width: 90%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal-overlay.show .modal-card { transform: translateY(0) scale(1); }
        
        .modal-header { padding: 24px 32px; border-bottom: 1px solid var(--border); display: flex; align-items: flex-start; justify-content: space-between; position: relative; }
        .modal-header-bg { position: absolute; top: 0; left: 0; right: 0; height: 100%; background: linear-gradient(90deg, var(--accent-soft), transparent); opacity: 0.5; pointer-events: none; }
        .modal-title-wrap { display: flex; gap: 16px; align-items: center; position: relative; z-index: 1; }
        .modal-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 8px 16px rgba(99,102,241,0.3); }
        .modal-title h3 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .modal-title p { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 4px; }
        .btn-close { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--muted); display: flex; align-items: center; justify-content: center; font-size: 14px; cursor: pointer; transition: 0.2s; position: relative; z-index: 1; }
        .btn-close:hover { background: var(--red); color: #fff; border-color: var(--red); transform: rotate(90deg); }

        .modal-body { padding: 32px; }
        
        /* Stepper */
        .stepper { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; position: relative; }
        .stepper::before { content:''; position:absolute; top: 14px; left: 0; right: 0; height: 2px; background: var(--border); z-index: 0; }
        .step { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; }
        .step-icon { width: 30px; height: 30px; border-radius: 50%; background: var(--card); border: 2px solid var(--border); color: var(--dim); display: flex; align-items: center; justify-content: center; font-size: 14px; transition: 0.3s; }
        .step.done .step-icon { background: var(--green); border-color: var(--green); color: #fff; }
        .step.active .step-icon { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 0 0 4px var(--accent-soft); }
        .step-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .step.done .step-label { color: var(--green); }
        .step.active .step-label { color: var(--accent); }

        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .detail-box { background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
        .detail-label { font-size: 11px; color: var(--dim); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .detail-value { font-size: 14px; font-weight: 500; color: var(--text); word-break: break-word; }
        
        .note-area { background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 24px; }
        .note-area textarea { width: 100%; background: transparent; border: 1px solid var(--border); border-radius: 8px; padding: 12px; color: var(--text); font-family: inherit; font-size: 13px; resize: vertical; min-height: 80px; outline: none; transition: 0.2s; margin-top: 10px; }
        .note-area textarea:focus { border-color: var(--accent); }
        
        .modal-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-end; padding-top: 24px; border-top: 1px solid var(--border); }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: var(--accent); color: #fff; } .btn-primary:hover { background: var(--accent-hover); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-success { background: var(--green); color: #fff; } .btn-success:hover { box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .btn-purple { background: var(--purple); color: #fff; } .btn-purple:hover { box-shadow: 0 4px 12px rgba(168,85,247,0.3); }
        .btn-danger { background: rgba(239,68,68,0.1); color: var(--red); } .btn-danger:hover { background: var(--red); color: #fff; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); } .btn-outline:hover { background: rgba(255,255,255,0.05); }

        /* Toast */
        .toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
        .toast { padding: 14px 20px; border-radius: 12px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; pointer-events: auto; }
        .toast-success { background: #064e3b; border: 1px solid #047857; color: #34d399; }
        .toast-error { background: #7f1d1d; border: 1px solid #b91c1c; color: #fca5a5; }
        @keyframes slideIn { from { transform: translateX(100%) scale(0.9); opacity: 0; } to { transform: translateX(0) scale(1); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; transform: scale(0.9); } }

        @media(max-width: 1024px) {
            .sidebar { position: fixed; transform: translateX(-100%); transition: 0.3s; }
            .sidebar.show { transform: translateX(0); }
            /* Add hamburger menu logic if needed, currently hidden for simplicity on tablet */
        }
        @media(max-width: 768px) {
            .content-area, .topbar { padding: 16px; }
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .detail-grid { grid-template-columns: 1fr; }
            .search-bar { min-width: auto; width: 100%; }
            .topbar { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon"><i class="ph-bold ph-lightning"></i></div>
        <div class="brand-text">ScriptBD</div>
    </div>
    
    <div class="nav-label">মেইন মেনু</div>
    <a href="index.php" class="nav-link <?=!$f&&!$q?'active':''?>">
        <div class="nav-link-left"><i class="ph ph-squares-four"></i> ড্যাশবোর্ড</div>
    </a>
    
    <div class="nav-label">অর্ডার স্ট্যাটাস</div>
    <a href="?f=pending" class="nav-link <?=$f==='pending'?'active':''?>">
        <div class="nav-link-left"><i class="ph ph-hourglass-high"></i> পেন্ডিং</div>
        <span class="nav-count"><?=$sts['pending']??0?></span>
    </a>
    <a href="?f=processing" class="nav-link <?=$f==='processing'?'active':''?>">
        <div class="nav-link-left"><i class="ph ph-spinner-gap"></i> প্রসেসিং</div>
        <span class="nav-count"><?=$sts['processing']??0?></span>
    </a>
    <a href="?f=completed" class="nav-link <?=$f==='completed'?'active':''?>">
        <div class="nav-link-left"><i class="ph ph-check-circle"></i> সম্পন্ন</div>
        <span class="nav-count"><?=$sts['completed']??0?></span>
    </a>
    
    <div class="nav-label">পেমেন্ট ম্যানেজমেন্ট</div>
    <a href="?f=submitted" class="nav-link <?=$f==='submitted'?'active':''?>">
        <div class="nav-link-left"><i class="ph ph-envelope-simple-open"></i> পেমেন্ট জমা</div>
        <span class="nav-count" style="background:var(--gold);color:#000"><?=$pay['submitted']??0?></span>
    </a>
    <a href="?f=verified" class="nav-link <?=$f==='verified'?'active':''?>">
        <div class="nav-link-left"><i class="ph ph-shield-check"></i> ভেরিফাইড</div>
        <span class="nav-count"><?=$pay['verified']??0?></span>
    </a>

    <div class="user-profile">
        <div class="avatar"><?=$ai?></div>
        <div class="user-info">
            <h4><?=$an?></h4>
            <p>অনলাইন</p>
        </div>
        <a href="?logout=1" class="btn-logout" title="লগআউট"><i class="ph-bold ph-sign-out"></i></a>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content">
    
    <!-- Topbar -->
    <header class="topbar">
        <div class="greeting">
            <h2>স্বাগতম, <?=$an?>! 👋</h2>
            <p><i class="ph ph-clock"></i> সর্বশেষ আপডেট: <?=date('h:i A')?> • মোট <?=$ta?> টি অর্ডার</p>
        </div>
        <div class="search-bar">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="s" placeholder="ID, নাম, ইমেইল বা ফোন দিয়ে খুঁজুন..." value="<?=htmlspecialchars($q)?>">
            <?php if($f||$q):?>
                <button class="btn-search" style="background:var(--card);color:var(--text);border:1px solid var(--border);margin-right:8px" onclick="location.href='index.php'">রিসেট</button>
            <?php endif;?>
            <button class="btn-search" onclick="srch()">খুঁজুন</button>
        </div>
    </header>

    <!-- Content Area -->
    <div class="content-area">
        
        <!-- Stats Grid -->
        <div class="grid-4">
            <div class="stat-card" style="--c:var(--accent)">
                <div class="stat-header">
                    <span class="stat-title">মোট আয়</span>
                    <div class="stat-icon"><i class="ph ph-wallet"></i></div>
                </div>
                <div class="stat-value">৳<?=number_format($pay['verified']?$db->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status='verified'")->fetchColumn():0)?></div>
            </div>
            
            <div class="stat-card" style="--c:var(--green)">
                <div class="stat-header">
                    <span class="stat-title">আজকের আয়</span>
                    <div class="stat-icon"><i class="ph ph-trend-up"></i></div>
                </div>
                <div class="stat-value">৳<?=number_format($db->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE payment_status='verified' AND DATE(created_at)=CURDATE()")->fetchColumn())?></div>
            </div>
            
            <div class="stat-card" style="--c:var(--purple)">
                <div class="stat-header">
                    <span class="stat-title">মোট অর্ডার</span>
                    <div class="stat-icon"><i class="ph ph-shopping-cart"></i></div>
                </div>
                <div class="stat-value"><?=$ta?></div>
            </div>
            
            <div class="stat-card" style="--c:var(--gold)">
                <div class="stat-header">
                    <span class="stat-title">ভেরিফাই বাকি</span>
                    <div class="stat-icon"><i class="ph ph-bell-ringing"></i></div>
                </div>
                <div class="stat-value"><?=$pay['submitted']??0?></div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="index.php" class="filter-tab <?=!$f?'active':''?>">
                <i class="ph ph-list-dashes"></i> সব অর্ডার <span class="filter-badge"><?=$ta?></span>
            </a>
            <a href="?f=pending" class="filter-tab <?=$f==='pending'?'active':''?>">
                <i class="ph ph-hourglass"></i> পেন্ডিং <span class="filter-badge"><?=$sts['pending']??0?></span>
            </a>
            <a href="?f=processing" class="filter-tab <?=$f==='processing'?'active':''?>">
                <i class="ph ph-spinner-gap"></i> প্রসেসিং <span class="filter-badge"><?=$sts['processing']??0?></span>
            </a>
            <a href="?f=completed" class="filter-tab <?=$f==='completed'?'active':''?>">
                <i class="ph ph-check-circle"></i> সম্পন্ন <span class="filter-badge"><?=$sts['completed']??0?></span>
            </a>
            <a href="?f=cancelled" class="filter-tab <?=$f==='cancelled'?'active':''?>">
                <i class="ph ph-x-circle"></i> বাতিল <span class="filter-badge"><?=$sts['cancelled']??0?></span>
            </a>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title"><i class="ph-bold ph-list-bullets" style="color:var(--accent)"></i> অর্ডার তালিকা <?=$q?"(সার্চ রেজাল্ট)":""?></div>
                <div style="font-size:12px;color:var(--dim)">মোট <?=$t?> টি রেকর্ড</div>
            </div>
            
            <div style="overflow-x:auto;">
                <?php if(empty($orders)): ?>
                    <div class="empty-state">
                        <i class="ph ph-folder-dashed"></i>
                        <p>কোনো ডেটা পাওয়া যায়নি।</p>
                    </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>অর্ডার #</th>
                            <th>কাস্টমার ইনফো</th>
                            <th>প্ল্যান ও টপিক</th>
                            <th>অ্যামাউন্ট</th>
                            <th>পেমেন্ট</th>
                            <th>ডেলিভারি</th>
                            <th>স্ট্যাটাস</th>
                            <th style="text-align:right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $o): ?>
                        <tr>
                            <td>
                                <a class="t-id" onclick="view(<?=$o['id']?>)">#<?=$o['id']?></a>
                                <div class="t-time"><?=ago($o['created_at'])?></div>
                            </td>
                            <td>
                                <div class="t-name"><?=htmlspecialchars($o['name'])?></div>
                                <div style="font-size:11px;color:var(--muted)"><?=htmlspecialchars($o['phone'])?></div>
                            </td>
                            <td>
                                <span class="t-plan"><?=str_replace('-',' ',$o['plan'])?></span>
                                <div style="font-size:12px;margin-top:4px;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?=htmlspecialchars($o['topic'])?>">
                                    <?=htmlspecialchars($o['topic'])?>
                                </div>
                            </td>
                            <td style="font-weight:600;color:var(--text)">৳<?=number_format($o['amount']??0)?></td>
                            <td>
                                <?=bdg('payment',$o['payment_status']??'unpaid')?>
                                <?=$o['transaction_id']?'<span class="t-trx" title="Transaction ID"><i class="ph ph-hash"></i> '.htmlspecialchars($o['transaction_id']).'</span>':''?>
                            </td>
                            <td>
                                <?=bdg('delivery',($o['delivery_status']??'')?:($o['status']=='completed'?'delivered':'not_delivered'))?>
                            </td>
                            <td>
                                <?=bdg('status',$o['status'])?>
                            </td>
                            <td>
                                <div class="actions" style="justify-content:flex-end">
                                    <button class="btn-act b-view" title="বিস্তারিত দেখুন" onclick="view(<?=$o['id']?>)"><i class="ph-bold ph-eye"></i></button>
                                    
                                    <?php if(($o['payment_status']??'')=='submitted'): ?>
                                    <button class="btn-act b-verify" title="পেমেন্ট ভেরিফাই করুন" onclick="aj('v',<?=$o['id']?>)"><i class="ph-bold ph-check"></i></button>
                                    <?php endif; ?>
                                    
                                    <?php if(($o['payment_status']??'')=='verified'&&($o['delivery_status']??'')!='delivered'): ?>
                                    <button class="btn-act b-deliver" title="ডেলিভারি মার্ক করুন" onclick="aj('d',<?=$o['id']?>)"><i class="ph-bold ph-package"></i></button>
                                    <?php endif; ?>
                                    
                                    <button class="btn-act b-delete" title="ডিলিট করুন" onclick="aj('x',<?=$o['id']?>)"><i class="ph-bold ph-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if($tp>1): ?>
            <div class="pagination">
                <?php for($i=1;$i<=min(10,$tp);$i++): $pq=$_GET; $pq['pg']=$i; ?>
                <a href="?<?=http_build_query($pq)?>" class="page-link <?=$i==$pg?'active':''?>"><?=$i?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Modal Premium -->
<div class="modal-overlay" id="mo">
    <div class="modal-card" id="mc" onclick="event.stopPropagation()">
        <!-- Generated by JS -->
    </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container" id="tc"></div>

<script>
// Search
function srch(){
    const v=document.getElementById('s').value.trim();
    const p=new URLSearchParams(location.search);
    v?p.set('q',v):p.delete('q');
    p.delete('pg');
    location.search=p.toString();
}
document.getElementById('s').addEventListener('keypress',e=>{if(e.key=='Enter')srch()});

// AJAX Actions
function aj(a,id,st){
    if(a==='x'&&!confirm('আপনি কি নিশ্চিত যে অর্ডার #'+id+' ডিলিট করবেন? এই একশন ফেরানো যাবে না।'))return;
    const fd=new FormData();fd.append('a',a);fd.append('id',id);if(st)fd.append('st',st);
    
    fetch('index.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(r=>{
        t(r.ok, a==='x' ? 'অর্ডার ডিলিট করা হয়েছে!' : 'সফলভাবে আপডেট করা হয়েছে!');
        if(r.ok) setTimeout(()=>location.reload(), 800);
    }).catch(e=>t(0));
}

// Save Note
function sn(id){
    const n=document.getElementById('nt_'+id).value;
    const fd=new FormData();fd.append('a','n');fd.append('id',id);fd.append('notes',n);
    const btn = document.getElementById('btn-save-note');
    const ogHtml = btn.innerHTML;
    btn.innerHTML = '<i class="ph ph-spinner-gap ph-spin"></i> সেভিং...';
    
    fetch('index.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(r=>{
        t(r.ok, 'নোট সেভ করা হয়েছে!');
        btn.innerHTML = ogHtml;
    }).catch(e=>t(0));
}

// View Modal
function view(id){
    const mo = document.getElementById('mo');
    const mc = document.getElementById('mc');
    mo.classList.add('show');
    mc.innerHTML='<div style="padding:60px;text-align:center;color:var(--dim)"><i class="ph ph-spinner-gap ph-spin" style="font-size:32px;margin-bottom:16px"></i><p>ডেটা লোড হচ্ছে...</p></div>';
    
    const fd=new FormData();fd.append('a','g');fd.append('id',id);
    fetch('index.php',{method:'POST',body:fd}).then(r=>r.json()).then(r=>{
        if(!r.d){mc.innerHTML='<div style="padding:60px;text-align:center;color:var(--red)"><i class="ph ph-warning-circle" style="font-size:48px;margin-bottom:16px"></i><p>অর্ডার পাওয়া যায়নি!</p><button class="btn btn-outline" style="margin:16px auto 0" onclick="cm()">বন্ধ করুন</button></div>';return}
        
        const o=r.d;
        const ps={'youtube-shorts':'YouTube Shorts','facebook-reels':'Facebook Reels','youtube-full':'YouTube Full'};
        
        // Stepper Logic
        let s1='', s2='', s3='';
        if(o.payment_status==='verified') { s1='done'; s2='active'; }
        else if(o.payment_status==='submitted') { s1='active'; }
        if(o.delivery_status==='delivered' || o.status==='completed') { s1='done'; s2='done'; s3='done'; }
        
        const html = `
            <div class="modal-header">
                <div class="modal-header-bg"></div>
                <div class="modal-title-wrap">
                    <div class="modal-icon"><i class="ph ph-receipt"></i></div>
                    <div class="modal-title">
                        <h3>অর্ডার #${o.id}</h3>
                        <p><i class="ph ph-calendar-blank"></i> ${o.created_at}</p>
                    </div>
                </div>
                <button class="btn-close" onclick="cm()"><i class="ph-bold ph-x"></i></button>
            </div>
            
            <div class="modal-body">
                <div class="stepper">
                    <div class="step ${s1||'done'}">
                        <div class="step-icon"><i class="ph-bold ph-shopping-cart"></i></div>
                        <div class="step-label">অর্ডার প্লেসড</div>
                    </div>
                    <div class="step ${s2}">
                        <div class="step-icon"><i class="ph-bold ph-shield-check"></i></div>
                        <div class="step-label">পেমেন্ট ভেরিফাই</div>
                    </div>
                    <div class="step ${s3}">
                        <div class="step-icon"><i class="ph-bold ph-package"></i></div>
                        <div class="step-label">ডেলিভারি</div>
                    </div>
                </div>
                
                <div class="detail-grid">
                    <div class="detail-box">
                        <div class="detail-label"><i class="ph ph-user"></i> কাস্টমার নাম</div>
                        <div class="detail-value">${esc(o.name)}</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label"><i class="ph ph-phone"></i> মোবাইল নাম্বার</div>
                        <div class="detail-value">${esc(o.phone)}</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label"><i class="ph ph-envelope"></i> ইমেইল এড্রেস</div>
                        <div class="detail-value">${esc(o.email||'প্রদান করেনি')}</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label"><i class="ph ph-money"></i> পেমেন্ট ডিটেইলস</div>
                        <div class="detail-value">
                            ৳${o.amount||0} • <span style="text-transform:uppercase">${esc(o.payment_method||'—')}</span><br>
                            <span style="font-size:11px;color:var(--dim);font-family:monospace;margin-top:4px;display:block">TrxID: ${esc(o.transaction_id||'—')}</span>
                        </div>
                    </div>
                    <div class="detail-box" style="grid-column: 1 / -1">
                        <div class="detail-label"><i class="ph ph-briefcase"></i> প্যাকেজ ও টপিক</div>
                        <div class="detail-value">
                            <span style="color:var(--accent);font-weight:600">${esc(ps[o.plan]||o.plan)}</span><br>
                            <span style="font-size:13px;color:var(--muted);margin-top:4px;display:block">${esc(o.topic||'—')}</span>
                        </div>
                    </div>
                </div>
                
                ${o.message ? `
                <div class="note-area" style="background:rgba(99,102,241,0.05);border-color:rgba(99,102,241,0.2)">
                    <div class="detail-label" style="color:var(--accent)"><i class="ph ph-chat-text"></i> কাস্টমার মেসেজ</div>
                    <div style="font-size:13px;line-height:1.6;color:var(--text);white-space:pre-wrap">${esc(o.message)}</div>
                </div>
                ` : ''}
                
                <div class="note-area">
                    <div class="detail-label"><i class="ph ph-notepad"></i> অ্যাডমিন নোট (প্রাইভেট)</div>
                    <textarea id="nt_${o.id}" placeholder="এই অর্ডারের জন্য কোন নোট থাকলে লিখুন...">${esc(o.admin_notes||'')}</textarea>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                        <span style="font-size:11px;color:var(--dim)">${o.delivered_by ? '📦 ডেলিভারি: '+esc(o.delivered_by)+' ('+(o.delivery_date||'')+')' : ''}</span>
                        <button class="btn btn-outline" id="btn-save-note" onclick="sn(${o.id})"><i class="ph ph-floppy-disk"></i> নোট সেভ করুন</button>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="btn btn-outline" onclick="cm()">বন্ধ করুন</button>
                    ${o.payment_status==='submitted' ? `<button class="btn btn-success" onclick="aj('v',${o.id})"><i class="ph-bold ph-check-circle"></i> পেমেন্ট ভেরিফাই</button>` : ''}
                    ${(o.payment_status==='verified' || o.payment_status==='submitted') && o.delivery_status!=='delivered' && o.status!=='completed' ? `<button class="btn btn-purple" onclick="aj('d',${o.id})"><i class="ph-bold ph-package"></i> ডেলিভারি সম্পন্ন</button>` : ''}
                    <button class="btn btn-danger" onclick="cm();aj('x',${o.id})"><i class="ph-bold ph-trash"></i> ডিলিট</button>
                </div>
            </div>
        `;
        mc.innerHTML = html;
    }).catch(e=>{
        mc.innerHTML='<div style="padding:60px;text-align:center;color:var(--red)"><p>নেটওয়ার্ক সমস্যা!</p></div>';
    });
}

function cm(){
    const mo = document.getElementById('mo');
    mo.classList.remove('show');
}
document.getElementById('mo').addEventListener('click', e => { if(e.target === e.currentTarget) cm(); });
document.addEventListener('keydown', e => { if(e.key === 'Escape') cm(); });

// Toast Function
function t(ok, msg){
    const tc = document.getElementById('tc');
    const el = document.createElement('div');
    el.className = 'toast ' + (ok ? 'toast-success' : 'toast-error');
    el.innerHTML = `<i class="ph-fill ${ok ? 'ph-check-circle' : 'ph-warning-circle'}" style="font-size:20px"></i> <span>${msg || (ok ? 'সফলভাবে সম্পন্ন হয়েছে!' : 'কোনো একটি সমস্যা হয়েছে!')}</span>`;
    tc.appendChild(el);
    setTimeout(() => {
        el.style.animation = 'fadeOut 0.3s forwards';
        setTimeout(() => el.remove(), 300);
    }, 3000);
}

// Escaper
function esc(s){
    if(!s)return'';
    const d=document.createElement('div');
    d.textContent=s;
    return d.innerHTML;
}
</script>
</body>
</html>