<?php
// contact.php location: portfolio/includes/contact.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? 'No Subject');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'সব ফিল্ড পূরণ করুন']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'সঠিক ইমেইল দিন']);
    exit;
}

// Save to database
try {
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        htmlspecialchars($name),
        htmlspecialchars($email),
        htmlspecialchars($subject),
        htmlspecialchars($message)
    ]);
} catch (PDOException $e) {
    error_log("Contact DB Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'ডাটাবেস সংরক্ষণ ব্যর্থ হয়েছে']);
    exit;
}

// Send email using PHPMailer with Gmail App Password
$mail_sent = false;
$phpmailer_paths = [
    __DIR__ . '/../PHPMailer/src/Exception.php',
    __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php',
];

$phpmailer_found = false;
foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        $phpmailer_found = true;
        $base = dirname($path);
        require_once $base . '/Exception.php';
        require_once $base . '/PHPMailer.php';
        require_once $base . '/SMTP.php';
        break;
    }
}

if ($phpmailer_found) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD; // Gmail App Password from config
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, 'Portfolio Contact');
        $mail->addAddress(MAIL_TO);
        $mail->addReplyTo($email, $name);
        $mail->Subject = "New Message: " . $subject;
        $mail->Body    = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\n\nMessage:\n{$message}\n\nTime: " . date('d M Y, h:i A');
        $mail->send();
        $mail_sent = true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
    }
} else {
    // Fallback to mail()
    $headers  = "From: " . MAIL_FROM . "\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body     = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\n\nMessage:\n{$message}";
    $mail_sent = @mail(MAIL_TO, "New Message: " . $subject, $body, $headers);
}

echo json_encode([
    'success' => true,
    'message' => $mail_sent
        ? 'মেসেজ পাঠানো হয়েছে এবং ইমেইল গেছে!'
        : 'মেসেজ সেভ হয়েছে! (ইমেইল পাঠানো যায়নি)'
]);