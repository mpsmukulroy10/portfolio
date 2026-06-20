<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$new_hash = password_hash('1234', PASSWORD_BCRYPT);

$check = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
$check->execute(['admin']);
$exists = $check->fetch();

if ($exists) {
    $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
    $stmt->execute([$new_hash, 'admin']);
    echo "✅ Admin password updated! Login with admin / 1234<br>";
} else {
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
    $stmt->execute(['admin', $new_hash]);
    echo "✅ Admin user created! Username: admin, Password: 1234<br>";
}

$verify = $pdo->prepare("SELECT password FROM admin_users WHERE username = 'admin'");
$verify->execute();
$row = $verify->fetch();
if (password_verify('1234', $row['password'])) {
    echo "🔐 Verification successful! You can now login.";
} else {
    echo "❌ Verification failed – something is wrong.";
}
?>