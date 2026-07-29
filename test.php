<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1>PHP Works!</h1>";

echo "<h2>Session test:</h2>";
session_start();
$_SESSION['test'] = 'hello';
echo "Session var: " . $_SESSION['test'] . "<br>";

echo "<h2>DB test:</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    $count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "Orders in DB: $count<br>";
    echo "DB connection: ✅ OK<br>";
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Admin check:</h2>";
try {
    $admin = $pdo->query("SELECT username FROM admin LIMIT 1")->fetch();
    echo "Admin user: " . $admin['username'] . "<br>";
} catch (Exception $e) {
    echo "Admin error: " . $e->getMessage() . "<br>";
}

echo "<h2>Orders table columns:</h2>";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll();
    foreach ($cols as $c) {
        echo $c['Field'] . " (" . $c['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "Column error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='admin/index.php'>Login</a> | <a href='admin/dashboard.php'>Dashboard</a>";
