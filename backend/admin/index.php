<?php
/**
 * ScriptBD - Admin Login Page
 * Bengali UI, Dark Theme
 */
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'ইউজারনেম ও পাসওয়ার্ড দিন';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare('SELECT id, username, password FROM admin WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                session_regenerate_id(true);
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'ভুল ইউজারনেম অথবা পাসওয়ার্ড';
            }
        } catch (PDOException $e) {
            error_log('Admin Login Error: ' . $e->getMessage());
            $error = 'ডাটাবেজ সংযোগ ত্রুটি। পরে আবার চেষ্টা করুন।';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন লগইন - স্ক্রিপ্টবিডি</title>
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #1a1a28;
            --accent: #ff6b35;
            --accent-hover: #ff8c5a;
            --text-primary: #e8e6f0;
            --text-secondary: #9d9bb0;
            --border: #2a2a3a;
            --danger: #ff4444;
            --success: #00c853;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Bengali', 'Segoe UI', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at center, #1a1a28 0%, #0a0a0f 70%);
        }

        .login-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 107, 53, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .login-logo {
            font-size: 48px;
            margin-bottom: 8px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px;
            background: linear-gradient(135deg, var(--accent), #ffa347);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .error-message {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            color: var(--danger);
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.15);
        }

        .form-group input::placeholder {
            color: #555370;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .login-button:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
        }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            color: var(--text-secondary);
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">📜</div>
            <h1 class="login-title">স্ক্রিপ্টবিডি</h1>
            <p class="login-subtitle">অ্যাডমিন প্যানেল</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">ইউজারনেম</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="আপনার ইউজারনেম লিখুন"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    required
                    autofocus
                >
            </div>
            <div class="form-group">
                <label for="password">পাসওয়ার্ড</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="আপনার পাসওয়ার্ড লিখুন"
                    required
                >
            </div>
            <button type="submit" class="login-button">লগইন</button>
        </form>

        <p class="login-footer">© <?php echo date('Y'); ?> স্ক্রিপ্টবিডি — সর্বস্বত্ব সংরক্ষিত</p>
    </div>
</body>
</html>
