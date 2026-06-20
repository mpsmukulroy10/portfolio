<?php
// admin/common.php - এই ফাইলটি সকল অ্যাডমিন পৃষ্ঠায় include করুন
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Language detection
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

// Back button URL - goes to dashboard
$back_url = 'dashboard.php';
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    // If coming from a specific page, go back there
    if (strpos($referer, 'admin/') !== false) {
        $back_url = $referer;
    }
}
?>