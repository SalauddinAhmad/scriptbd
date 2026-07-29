<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1 style='color:green'>✅ PHP Works!</h1>";

echo "<h2>Session test:</h2>";
session_start();
$_SESSION['test'] = 'hello';
echo "OK<br>";

echo "<h2>DB test:</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    $count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "Orders: $count ✅<br>";

    $admin = $pdo->query("SELECT username FROM admin LIMIT 1")->fetch();
    echo "Admin: " . ($admin['username']??'NONE') . "<br>";
    
    echo "<h2>Orders columns:</h2>";
    $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll();
    foreach ($cols as $c) {
        echo $c['Field'] . " (" . $c['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "<b>DB Error:</b> " . $e->getMessage() . "<br>";
}

echo "<br><a href='admin/index.php' style='padding:10px 20px;background:#ff6b35;color:white;text-decoration:none;border-radius:8px'>GO TO ADMIN LOGIN</a>";
