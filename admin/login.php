<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin']      = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username বা Password ভুল!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#0f172a; color:#e2e8f0; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .login-box { background:#1e293b; border:1px solid #334155; border-radius:16px; padding:48px 40px; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,0.4); }
        .login-logo { text-align:center; margin-bottom:32px; }
        .login-logo i { font-size:2.5rem; color:#6366f1; margin-bottom:12px; display:block; }
        .login-logo h1 { font-size:1.5rem; color:#fff; }
        .login-logo p { color:#94a3b8; font-size:0.9rem; margin-top:4px; }
        .error-msg { background:rgba(248,113,113,0.15); border:1px solid rgba(248,113,113,0.3); color:#f87171; padding:12px 16px; border-radius:8px; font-size:0.9rem; margin-bottom:20px; text-align:center; }
        .form-group { margin-bottom:18px; position:relative; }
        .form-group i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; }
        .form-group input { width:100%; padding:13px 16px 13px 42px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#e2e8f0; font-size:0.95rem; outline:none; transition:all 0.3s; }
        .form-group input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,0.15); }
        .btn-login { width:100%; padding:13px; background:#6366f1; color:white; border:none; border-radius:8px; font-size:1rem; font-weight:600; cursor:pointer; transition:all 0.3s; margin-top:8px; }
        .btn-login:hover { background:#4f46e5; }
        .back-link { text-align:center; margin-top:20px; }
        .back-link a { color:#94a3b8; font-size:0.9rem; text-decoration:none; }
        .back-link a:hover { color:#6366f1; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-logo">
        <i class="fas fa-shield-alt"></i>
        <h1>Admin Panel</h1>
        <p>Portfolio Management System</p>
    </div>

    <?php if ($error): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Username" required
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>
    <div class="back-link">
        <a href="../index.php"><i class="fas fa-arrow-left"></i> Portfolio-তে ফিরে যাও</a>
    </div>
</div>
</body>
</html>