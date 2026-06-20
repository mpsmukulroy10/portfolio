<?php
require_once '../config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? null;
$error  = '';

// Delete
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: testimonials.php?success=deleted&lang=' . $lang);
    exit;
}

// Save (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $role     = trim($_POST['role'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $photo    = $_POST['old_photo'] ?? '';
    $edit_id  = $_POST['edit_id'] ?? null;

    // Image upload
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) {
            $photo = uniqid('testi_') . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], "../assets/images/" . $photo);
        } else {
            $error = $lang === 'bn' ? 'ভুল ফাইল টাইপ' : 'Invalid file type';
        }
    }

    if (empty($name) || empty($message)) {
        $error = $lang === 'bn' ? 'নাম ও মেসেজ দিতে হবে' : 'Name and message are required';
    }

    if (empty($error)) {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE testimonials SET name=?, role=?, message=?, photo=? WHERE id=?");
            $stmt->execute([$name, $role, $message, $photo, $edit_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO testimonials (name, role, message, photo) VALUES (?,?,?,?)");
            $stmt->execute([$name, $role, $message, $photo]);
        }
        header('Location: testimonials.php?success=saved&lang=' . $lang);
        exit;
    }
}

// Edit data fetch
$edit_data = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch();
}

// List all testimonials
$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'bn' ? 'মতামত - অ্যাডমিন' : 'Testimonials - Admin' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background: #0f172a; 
            color: #e2e8f0; 
            display: flex; 
            min-height: 100vh; 
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            background: #1e293b;
            border-right: 1px solid #334155;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform 0.3s ease;
        }
        .sidebar-logo {
            padding: 28px 24px;
            border-bottom: 1px solid #334155;
        }
        .sidebar-logo h2 {
            color: #6366f1;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-logo p {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 2px;
        }
        .sidebar-menu {
            padding: 16px 0;
            flex: 1;
            overflow-y: auto;
        }
        .menu-label {
            padding: 8px 24px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            margin-top: 8px;
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.95rem;
            position: relative;
        }
        .menu-item:hover, .menu-item.active {
            color: #fff;
            background: rgba(99,102,241,0.15);
        }
        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #6366f1;
            border-radius: 0 3px 3px 0;
        }
        .menu-item i {
            width: 20px;
            text-align: center;
            color: #6366f1;
        }
        .sidebar-footer {
            padding: 16px 24px;
            border-top: 1px solid #334155;
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .sidebar-footer a:hover {
            color: #f87171;
        }

        /* ===== MAIN CONTENT ===== */
        .main {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            flex-wrap: wrap;
            gap: 12px;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .topbar-left h1 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .topbar-left h1 i {
            color: #6366f1;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* ===== BACK BUTTON ===== */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99,102,241,0.15);
            color: #6366f1;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid rgba(99,102,241,0.2);
        }
        .back-btn:hover {
            background: #6366f1;
            color: #fff;
            border-color: #6366f1;
            transform: translateX(-3px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }
        .back-btn i {
            font-size: 0.9rem;
        }

        /* ===== LANGUAGE TOGGLE ===== */
        .lang-toggle {
            background: transparent;
            border: 1px solid #334155;
            color: #94a3b8;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        .lang-toggle:hover {
            border-color: #6366f1;
            color: #6366f1;
            background: rgba(99,102,241,0.05);
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: #6366f1;
            color: #fff;
        }
        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(99,102,241,0.3);
        }
        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .btn-danger {
            background: rgba(239,68,68,0.15);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.3);
        }
        .btn-danger:hover {
            background: #ef4444;
            color: #fff;
        }
        .btn-edit {
            background: rgba(99,102,241,0.15);
            color: #6366f1;
            border: 1px solid rgba(99,102,241,0.3);
        }
        .btn-edit:hover {
            background: #6366f1;
            color: #fff;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .btn-success {
            background: rgba(34,197,94,0.15);
            color: #22c55e;
            border: 1px solid rgba(34,197,94,0.3);
        }
        .btn-success:hover {
            background: #22c55e;
            color: #fff;
        }

        /* ===== CONTENT ===== */
        .content {
            padding: 32px;
            flex: 1;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.25);
            color: #22c55e;
        }
        .alert-error {
            background: rgba(248,113,113,0.12);
            border: 1px solid rgba(248,113,113,0.25);
            color: #f87171;
        }
        .alert i {
            font-size: 1.2rem;
        }

        /* ===== CARDS ===== */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .card-header h3 {
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header h3 i {
            color: #6366f1;
        }
        .card-body {
            padding: 24px;
        }

        /* ===== FORM ===== */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full {
            grid-column: 1 / -1;
        }
        .form-group label {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .form-group label .required {
            color: #ef4444;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 11px 14px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group .help-text {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 4px;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        /* ===== IMAGE PREVIEW ===== */
        .photo-preview {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .photo-preview img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #6366f1;
        }
        .photo-preview .no-photo {
            width: 64px;
            height: 64px;
            background: #0f172a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #334155;
            color: #475569;
            font-size: 1.5rem;
        }

        /* ===== TESTIMONIALS LIST ===== */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .testimonial-item {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
        }
        .testimonial-item:hover {
            border-color: #6366f1;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        .testimonial-item .quote-icon {
            color: #6366f1;
            opacity: 0.2;
            font-size: 2.5rem;
            position: absolute;
            top: 12px;
            right: 16px;
            font-family: Georgia, serif;
            line-height: 1;
        }
        .testimonial-item .testimonial-message {
            color: #cbd5e1;
            font-style: italic;
            line-height: 1.7;
            margin-bottom: 16px;
            padding-right: 30px;
            font-size: 0.95rem;
        }
        .testimonial-item .testimonial-author {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .testimonial-item .testimonial-author img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #6366f1;
        }
        .testimonial-item .testimonial-author .avatar-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #334155;
            color: #6366f1;
            font-size: 1.2rem;
        }
        .testimonial-item .testimonial-author .author-info {
            flex: 1;
        }
        .testimonial-item .testimonial-author .author-info strong {
            display: block;
            font-size: 0.95rem;
            color: #e2e8f0;
        }
        .testimonial-item .testimonial-author .author-info span {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .testimonial-item .testimonial-actions {
            display: flex;
            gap: 8px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #1e293b;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 3rem;
            color: #334155;
            display: block;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            color: #e2e8f0;
            margin-bottom: 8px;
        }
        .empty-state p {
            margin-bottom: 16px;
        }

        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main {
                margin-left: 0;
            }
            .topbar {
                padding: 12px 16px;
            }
            .topbar-left h1 {
                font-size: 1rem;
            }
            .content {
                padding: 16px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .testimonials-grid {
                grid-template-columns: 1fr;
                padding: 12px;
            }
            .card-header {
                padding: 14px 16px;
            }
            .card-body {
                padding: 16px;
            }
            .back-btn span {
                display: none;
            }
            .back-btn {
                padding: 8px 12px;
            }
            .hamburger {
                display: flex !important;
            }
        }
        @media (max-width: 576px) {
            .topbar-right .lang-toggle {
                padding: 6px 10px;
                font-size: 0.75rem;
            }
            .testimonial-item {
                padding: 16px;
            }
            .form-actions {
                flex-direction: column;
            }
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* ===== HAMBURGER MENU (Mobile) ===== */
        .hamburger {
            display: none;
            background: transparent;
            border: none;
            color: #e2e8f0;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .hamburger:hover {
            background: rgba(99,102,241,0.1);
        }
        .mobile-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }
        .mobile-overlay.active {
            display: block;
        }
    </style>
</head>
<body>

<!-- MOBILE OVERLAY -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h2>⚡ Admin Panel</h2>
        <p><?= $lang === 'bn' ? 'পোর্টফোলিও ব্যবস্থাপনা' : 'Portfolio Management' ?></p>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-label"><?= $lang === 'bn' ? 'মেইন' : 'Main' ?></div>
        <a href="dashboard.php" class="menu-item">
            <i class="fas fa-home"></i> <?= $lang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' ?>
        </a>
        <a href="../index.php" target="_blank" class="menu-item">
            <i class="fas fa-external-link-alt"></i> <?= $lang === 'bn' ? 'পোর্টফোলিও দেখো' : 'View Portfolio' ?>
        </a>
        <div class="menu-label"><?= $lang === 'bn' ? 'ব্যবস্থাপনা' : 'Manage' ?></div>
        <a href="projects.php" class="menu-item"><i class="fas fa-code"></i> <?= $lang === 'bn' ? 'প্রজেক্ট' : 'Projects' ?></a>
        <a href="skills.php" class="menu-item"><i class="fas fa-star"></i> <?= $lang === 'bn' ? 'দক্ষতা' : 'Skills' ?></a>
        <a href="blogs.php" class="menu-item"><i class="fas fa-pen"></i> <?= $lang === 'bn' ? 'ব্লগ' : 'Blogs' ?></a>
        <a href="testimonials.php" class="menu-item active"><i class="fas fa-quote-right"></i> <?= $lang === 'bn' ? 'মতামত' : 'Testimonials' ?></a>
        <a href="about.php" class="menu-item"><i class="fas fa-user"></i> <?= $lang === 'bn' ? 'আমার সম্পর্কে' : 'About Me' ?></a>
        <div class="menu-label"><?= $lang === 'bn' ? 'ইনবক্স' : 'Inbox' ?></div>
        <a href="messages.php" class="menu-item"><i class="fas fa-envelope"></i> <?= $lang === 'bn' ? 'বার্তা' : 'Messages' ?></a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= $lang === 'bn' ? 'লগআউট' : 'Logout' ?></a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <!-- HAMBURGER (Mobile) -->
            <button class="hamburger" id="hamburgerBtn" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- BACK BUTTON -->
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> 
                <span><?= $lang === 'bn' ? 'পেছনে' : 'Back' ?></span>
            </a>
            
            <h1>
                <i class="fas fa-quote-right"></i>
                <?= $lang === 'bn' ? 'মতামত' : 'Testimonials' ?>
            </h1>
        </div>
        <div class="topbar-right">
            <a href="?lang=bn<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?><?= isset($_GET['action']) ? '&action=' . $_GET['action'] : '' ?><?= isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" class="lang-toggle">🇧🇩 বাংলা</a>
            <a href="?lang=en<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?><?= isset($_GET['action']) ? '&action=' . $_GET['action'] : '' ?><?= isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" class="lang-toggle">🌐 English</a>
            
            <?php if ($action === 'list'): ?>
                <a href="testimonials.php?action=add&lang=<?= $lang ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?= $lang === 'bn' ? 'নতুন মতামত' : 'Add Testimonial' ?>
                </a>
            <?php else: ?>
                <a href="testimonials.php?lang=<?= $lang ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?= $lang === 'bn' ? 'তালিকায় ফিরে' : 'Back to List' ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="content">

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php if ($_GET['success'] === 'saved'): ?>
                    <?= $lang === 'bn' ? '✅ মতামত সফলভাবে সংরক্ষণ করা হয়েছে!' : '✅ Testimonial saved successfully!' ?>
                <?php elseif ($_GET['success'] === 'deleted'): ?>
                    <?= $lang === 'bn' ? '✅ মতামত সফলভাবে মুছে ফেলা হয়েছে!' : '✅ Testimonial deleted successfully!' ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- ===== ADD / EDIT FORM ===== -->
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-<?= $action === 'edit' ? 'edit' : 'plus-circle' ?>"></i>
                    <?= $action === 'edit' 
                        ? ($lang === 'bn' ? 'মতামত সম্পাদনা' : 'Edit Testimonial') 
                        : ($lang === 'bn' ? 'নতুন মতামত যোগ করুন' : 'Add New Testimonial') 
                    ?>
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                        <input type="hidden" name="old_photo" value="<?= $edit_data['photo'] ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i> 
                                <?= $lang === 'bn' ? 'নাম *' : 'Name *' ?>
                                <span class="required">*</span>
                            </label>
                            <input type="text" name="name" required 
                                   value="<?= htmlspecialchars($edit_data['name'] ?? '') ?>"
                                   placeholder="<?= $lang === 'bn' ? 'আপনার নাম' : 'Your name' ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-briefcase"></i> 
                                <?= $lang === 'bn' ? 'পদবি (Role)' : 'Role' ?>
                            </label>
                            <input type="text" name="role" 
                                   value="<?= htmlspecialchars($edit_data['role'] ?? '') ?>"
                                   placeholder="<?= $lang === 'bn' ? 'CEO, Developer, ইত্যাদি' : 'CEO, Developer, etc.' ?>">
                        </div>

                        <div class="form-group full">
                            <label>
                                <i class="fas fa-comment-dots"></i> 
                                <?= $lang === 'bn' ? 'মতামত / বার্তা *' : 'Testimonial Message *' ?>
                                <span class="required">*</span>
                            </label>
                            <textarea name="message" rows="4" required 
                                      placeholder="<?= $lang === 'bn' ? 'তোমার মতামত লিখুন...' : 'Write your testimonial...' ?>"><?= htmlspecialchars($edit_data['message'] ?? '') ?></textarea>
                            <div class="help-text">
                                <?= $lang === 'bn' ? 'সংক্ষিপ্ত এবং হৃদয়গ্রাহী মতামত লিখুন' : 'Write a short and heartfelt testimonial' ?>
                            </div>
                        </div>

                        <div class="form-group full">
                            <label>
                                <i class="fas fa-image"></i> 
                                <?= $lang === 'bn' ? 'ছবি (Photo)' : 'Photo' ?>
                            </label>
                            <input type="file" name="photo" accept="image/*" 
                                   onchange="previewPhoto(this)">
                            <div class="photo-preview">
                                <?php if (!empty($edit_data['photo'])): ?>
                                    <img src="../assets/images/<?= htmlspecialchars($edit_data['photo']) ?>" 
                                         id="previewImg" alt="Current photo">
                                <?php else: ?>
                                    <div class="no-photo" id="previewPlaceholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <img id="previewImg" style="display:none;">
                                <?php endif; ?>
                                <span style="color:#94a3b8;font-size:0.85rem;">
                                    <?= $lang === 'bn' ? '(JPG, PNG, GIF, WEBP)' : '(JPG, PNG, GIF, WEBP)' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?= $action === 'edit' 
                                ? ($lang === 'bn' ? 'আপডেট করো' : 'Update') 
                                : ($lang === 'bn' ? 'সংরক্ষণ করো' : 'Save') 
                            ?>
                        </button>
                        <a href="testimonials.php?lang=<?= $lang ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            <?= $lang === 'bn' ? 'বাতিল করো' : 'Cancel' ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== LIST ===== -->
        <?php if ($action === 'list'): ?>
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list"></i>
                    <?= $lang === 'bn' ? 'সব মতামত' : 'All Testimonials' ?>
                    <span style="color:#94a3b8;font-weight:400;font-size:0.85rem;margin-left:4px;">
                        (<?= count($testimonials) ?>)
                    </span>
                </h3>
            </div>
            <div class="testimonials-grid">
                <?php if (!empty($testimonials)): ?>
                    <?php foreach ($testimonials as $t): ?>
                        <div class="testimonial-item">
                            <div class="quote-icon">"</div>
                            <div class="testimonial-message">
                                <?= htmlspecialchars($t['message']) ?>
                            </div>
                            <div class="testimonial-author">
                                <?php if (!empty($t['photo'])): ?>
                                    <img src="../assets/images/<?= htmlspecialchars($t['photo']) ?>" 
                                         alt="<?= htmlspecialchars($t['name']) ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="author-info">
                                    <strong><?= htmlspecialchars($t['name']) ?></strong>
                                    <span><?= htmlspecialchars($t['role']) ?></span>
                                </div>
                            </div>
                            <div class="testimonial-actions">
                                <a href="testimonials.php?action=edit&id=<?= $t['id'] ?>&lang=<?= $lang ?>" 
                                   class="btn btn-edit btn-sm">
                                    <i class="fas fa-edit"></i> <?= $lang === 'bn' ? 'সম্পাদনা' : 'Edit' ?>
                                </a>
                                <a href="testimonials.php?action=delete&id=<?= $t['id'] ?>&lang=<?= $lang ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('<?= $lang === 'bn' ? 'আপনি কি এই মতামতটি মুছে ফেলতে চান?' : 'Are you sure you want to delete this testimonial?' ?>')">
                                    <i class="fas fa-trash"></i> <?= $lang === 'bn' ? 'মুছে ফেলো' : 'Delete' ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column:1/-1;">
                        <i class="fas fa-quote-right"></i>
                        <h3><?= $lang === 'bn' ? 'কোন মতামত নেই' : 'No Testimonials Yet' ?></h3>
                        <p><?= $lang === 'bn' ? 'এখনো কোনো মতামত যোগ করা হয়নি। প্রথম মতামত যোগ করুন!' : 'No testimonials have been added yet. Add your first testimonial!' ?></p>
                        <a href="testimonials.php?action=add&lang=<?= $lang ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> <?= $lang === 'bn' ? 'মতামত যোগ করুন' : 'Add Testimonial' ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// ===== PHOTO PREVIEW =====
function previewPhoto(input) {
    const preview = document.getElementById('previewImg');
    const placeholder = document.getElementById('previewPlaceholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            if (placeholder) placeholder.style.display = 'none';
            preview.style.display = 'block';
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ===== MOBILE SIDEBAR =====
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            this.innerHTML = sidebar.classList.contains('open') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            if (hamburger) {
                hamburger.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    }
    
    // Close sidebar on window resize (going back to desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            if (hamburger) {
                hamburger.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    });
});
</script>

</body>
</html>