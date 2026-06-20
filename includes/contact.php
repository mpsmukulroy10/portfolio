<?php
// portfolio/includes/contact.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log for debugging
function logDebug($msg) {
    $log = date('Y-m-d H:i:s') . " - " . $msg . "\n";
    file_put_contents(__DIR__ . '/../debug.log', $log, FILE_APPEND);
}

logDebug("=== New Request ===");
logDebug("POST Data: " . print_r($_POST, true));

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("Error: Not POST method");
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use POST.']);
    exit;
}

// Get and sanitize input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'No Subject');
$message = trim($_POST['message'] ?? '');

logDebug("Received: name=$name, email=$email, subject=$subject");

// Validation
$errors = [];
if (empty($name)) $errors[] = 'Name is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($message)) $errors[] = 'Message is required';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (!empty($errors)) {
    logDebug("Validation errors: " . implode(', ', $errors));
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Save to database
$db_saved = false;
try {
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $subject, $message]);
    $db_saved = true;
    logDebug("Database saved successfully! ID: " . $pdo->lastInsertId());
} catch (PDOException $e) {
    logDebug("Database Error: " . $e->getMessage());
}

// Try to send email
$mail_sent = false;
$mail_error = '';

// Check if PHPMailer exists
$phpmailer_paths = [
    __DIR__ . '/../PHPMailer/src/PHPMailer.php',
    __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
];

$phpmailer_found = false;
foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        $phpmailer_found = true;
        $base = dirname($path);
        try {
            require_once $base . '/Exception.php';
            require_once $base . '/PHPMailer.php';
            require_once $base . '/SMTP.php';
            logDebug("PHPMailer found at: $path");
        } catch (Exception $e) {
            logDebug("PHPMailer require error: " . $e->getMessage());
        }
        break;
    }
}

if ($phpmailer_found) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_FROM;
        $mail->Password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : 'nhmi sjus rmhx vbwp';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;

        $mail->setFrom(MAIL_FROM, 'Portfolio Contact');
        $mail->addAddress(MAIL_TO);
        $mail->addReplyTo($email, $name);
        $mail->Subject = "New Message: " . $subject;
        $mail->Body = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\n\nMessage:\n{$message}\n\nTime: " . date('d M Y, h:i A');

        if ($mail->send()) {
            $mail_sent = true;
            logDebug("Email sent successfully!");
        } else {
            $mail_error = $mail->ErrorInfo;
            logDebug("PHPMailer send error: " . $mail_error);
        }
    } catch (Exception $e) {
        $mail_error = $e->getMessage();
        logDebug("PHPMailer Exception: " . $mail_error);
    }
} else {
    logDebug("PHPMailer not found, using mail()");
    // Fallback to mail()
    $headers = "From: " . MAIL_FROM . "\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\n\nMessage:\n{$message}";
    $mail_sent = @mail(MAIL_TO, "New Message: " . $subject, $body, $headers);
    if (!$mail_sent) {
        $mail_error = 'mail() function failed';
        logDebug("mail() failed");
    } else {
        logDebug("mail() sent successfully");
    }
}

// Prepare response
$response = [
    'success' => ($db_saved || $mail_sent),
    'db_saved' => $db_saved,
    'mail_sent' => $mail_sent,
    'message' => ''
];

if ($db_saved && $mail_sent) {
    $response['message'] = '✅ Message saved and email sent!';
} elseif ($db_saved && !$mail_sent) {
    $response['message'] = '✅ Message saved! (Email not sent: ' . $mail_error . ')';
} elseif (!$db_saved && $mail_sent) {
    $response['message'] = '⚠️ Email sent but not saved to database!';
} else {
    $response['success'] = false;
    $response['message'] = '❌ Failed to save message and send email!';
}

logDebug("Response: " . json_encode($response));
echo json_encode($response);
exit;
?>