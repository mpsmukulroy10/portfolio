<?php
require_once '../config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$action  = $_GET['action'] ?? 'list';
$id      = $_GET['id'] ?? null;
$error   = '';

// ── DELETE ──
if ($action === 'delete' && $id) {
    $proj = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
    $proj->execute([$id]);
    $row = $proj->fetch();
    if ($row && $row['image'] && file_exists("../assets/images/" . $row['image'])) {
        unlink("../assets/images/" . $row['image']);
    }
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    header('Location: projects.php?success=deleted&lang=' . $lang);
    exit;
}

// ── SAVE (Add/Edit) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tech_stack  = trim($_POST['tech_stack'] ?? '');
    $live_url    = trim($_POST['live_url'] ?? '');
    $github_url  = trim($_POST['github_url'] ?? '');
    $edit_id     = $_POST['edit_id'] ?? null;
    $image_name  = $_POST['old_image'] ?? '';

    if (empty($title) || empty($description)) {
        $error = $lang === 'bn' ? 'শিরোনাম ও বিবরণ দিতে হবে' : 'Title and description are required';
    }

    // Image Upload
    if (!empty($_FILES['image']['name']) && empty($error)) {
        $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed   = ['jpg','jpeg','png','gif','webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $image_name = uniqid('proj_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/" . $image_name);
        } else {
            $error = $lang === 'bn' ? 'শুধু JPG, PNG, GIF, WEBP ফাইল আপলোড করুন' : 'Only JPG, PNG, GIF, WEBP files allowed';
        }
    }

    if (empty($error)) {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, tech_stack=?, image=?, live_url=?, github_url=? WHERE id=?");
            $stmt->execute([$title, $description, $tech_stack, $image_name, $live_url, $github_url, $edit_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, tech_stack, image, live_url, github_url) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$title, $description, $tech_stack, $image_name, $live_url, $github_url]);
        }
        header('Location: projects.php?success=saved&lang=' . $lang);
        exit;
    }
}

// ── EDIT DATA ──
$edit_data = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch();
}

// ── LIST ──
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'bn' ? 'প্রজেক্ট - অ্যাডমিন' : 'Projects - Admin' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; min-height: 100vh; }

        /* ===== SIDEBAR ===== */
        .sidebar { width: 260px; background: #1e293b; border-right: 1px solid #334155; position: fixed; top: 0; left: 0; bottom: 0; display: flex; flex-direction: column; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-logo { padding: 28px 24px; border-bottom: 1px solid #334155; }
        .sidebar-logo h2 { color: #6366f1; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; }
        .sidebar-logo p { color: #94a3b8; font-size: 0.8rem; margin-top: 2px; }
        .sidebar-menu { padding: 16px 0; flex: 1; overflow-y: auto; }
        .menu-label { padding: 8px 24px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #475569; margin-top: 8px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #94a3b8; text-decoration: none; transition: all 0.3s; font-size: 0.95rem; position: relative; }
        .menu-item:hover, .menu-item.active { color: #fff; background: rgba(99,102,241,0.15); }
        .menu-item.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #6366f1; border-radius: 0 3px 3px 0; }
        .menu-item i { width: 20px; text-align: center; color: #6366f1; }
        .sidebar-footer { padding: 16px 24px; border-top: 1px solid #334155; }
        .sidebar-footer a { display: flex; align-items: center; gap: 10px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; transition: color 0.3s; }
        .sidebar-footer a:hover { color: #f87171; }

        /* ===== MAIN ===== */
        .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ===== TOPBAR ===== */
        .topbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; flex-wrap: wrap; gap: 12px; }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .topbar-left h1 { font-size: 1.3rem; display: flex; align-items: center; gap: 10px; }
        .topbar-left h1 i { color: #6366f1; }
        .topbar-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

        /* ===== BACK BUTTON ===== */
        .back-btn { display: inline-flex; align-items: center; gap: 8px; background: rgba(99,102,241,0.15); color: #6366f1; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.3s; border: 1px solid rgba(99,102,241,0.2); }
        .back-btn:hover { background: #6366f1; color: #fff; border-color: #6366f1; transform: translateX(-3px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }

        /* ===== LANGUAGE TOGGLE ===== */
        .lang-toggle { background: transparent; border: 1px solid #334155; color: #94a3b8; padding: 8px 16px; border-radius: 8px; cursor: pointer; text-decoration: none; transition: all 0.3s; font-size: 0.85rem; }
        .lang-toggle:hover { border-color: #6366f1; color: #6366f1; background: rgba(99,102,241,0.05); }

        /* ===== BUTTONS ===== */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(99,102,241,0.3); }
        .btn-secondary { background: #334155; color: #e2e8f0; }
        .btn-secondary:hover { background: #475569; }
        .btn-danger { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .btn-danger:hover { background: #ef4444; color: #fff; }
        .btn-edit { background: rgba(99,102,241,0.15); color: #6366f1; border: 1px solid rgba(99,102,241,0.3); }
        .btn-edit:hover { background: #6366f1; color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
        .btn-success { background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
        .btn-success:hover { background: #22c55e; color: #fff; }

        /* ===== CONTENT ===== */
        .content { padding: 32px; flex: 1; }

        /* ===== ALERTS ===== */
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 0.95rem; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.25); color: #22c55e; }
        .alert-error { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.25); color: #f87171; }
        .alert i { font-size: 1.2rem; }

        /* ===== CARDS ===== */
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; margin-bottom: 28px; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #334155; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .card-header h3 { font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .card-header h3 i { color: #6366f1; }
        .card-body { padding: 24px; }

        /* ===== FORM ===== */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; color: #94a3b8; font-weight: 500; display: flex; align-items: center; gap: 6px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group textarea { padding: 11px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 0.9rem; font-family: inherit; outline: none; transition: all 0.3s; }
        .form-group input:focus, .form-group textarea:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group .help-text { font-size: 0.78rem; color: #64748b; margin-top: 4px; }
        .form-actions { display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap; }

        /* ===== IMAGE PREVIEW ===== */
        .photo-preview { display: flex; align-items: center; gap: 16px; margin-top: 8px; flex-wrap: wrap; }
        .photo-preview img { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #6366f1; }
        .photo-preview .no-photo { width: 80px; height: 60px; background: #0f172a; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 2px dashed #334155; color: #475569; font-size: 1.5rem; }

        /* ===== PROJECTS GRID ===== */
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; padding: 20px; }
        .project-item { background: #0f172a; border: 1px solid #334155; border-radius: 12px; overflow: hidden; transition: all 0.3s; }
        .project-item:hover { border-color: #6366f1; transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
        .project-item .project-image { width: 100%; height: 180px; object-fit: cover; background: #1e293b; }
        .project-item .project-image-placeholder { width: 100%; height: 180px; background: #1e293b; display: flex; align-items: center; justify-content: center; color: #6366f1; font-size: 3rem; }
        .project-item .project-body { padding: 18px; }
        .project-item .project-body h3 { font-size: 1.05rem; margin-bottom: 6px; color: #e2e8f0; }
        .project-item .project-body p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 12px; line-height: 1.6; }
        .project-item .tech-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .project-item .tech-tags span { background: rgba(99,102,241,0.12); color: #6366f1; padding: 3px 10px; border-radius: 99px; font-size: 0.75rem; border: 1px solid rgba(99,102,241,0.15); }
        .project-item .project-links { display: flex; gap: 12px; margin-bottom: 12px; }
        .project-item .project-links a { color: #94a3b8; font-size: 0.85rem; transition: color 0.3s; display: flex; align-items: center; gap: 4px; }
        .project-item .project-links a:hover { color: #6366f1; }
        .project-item .project-actions { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid #1e293b; }

        /* ===== EMPTY STATE ===== */
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state i { font-size: 3rem; color: #334155; display: block; margin-bottom: 16px; }
        .empty-state h3 { color: #e2e8f0; margin-bottom: 8px; }
        .empty-state p { margin-bottom: 16px; }

        /* ===== MOBILE ===== */
        .hamburger { display: none; background: transparent; border: none; color: #e2e8f0; font-size: 1.5rem; cursor: pointer; padding: 4px 8px; border-radius: 8px; transition: all 0.3s; }
        .hamburger:hover { background: rgba(99,102,241,0.1); }
        .mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; }
        .mobile-overlay.active { display: block; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .topbar { padding: 12px 16px; }
            .topbar-left h1 { font-size: 1rem; }
            .content { padding: 16px; }
            .form-grid { grid-template-columns: 1fr; }
            .projects-grid { grid-template-columns: 1fr; padding: 12px; }
            .card-header { padding: 14px 16px; }
            .card-body { padding: 16px; }
            .back-btn span { display: none; }
            .back-btn { padding: 8px 12px; }
            .hamburger { display: flex !important; }
        }
        @media (max-width: 576px) {
            .topbar-right .lang-toggle { padding: 6px 10px; font-size: 0.75rem; }
            .form-actions { flex-direction: column; }
            .form-actions .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="mobile-overlay" id="mobileOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h2>⚡ Admin Panel</h2>
        <p><?= $lang === 'bn' ? 'পোর্টফোলিও ব্যবস্থাপনা' : 'Portfolio Management' ?></p>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-label"><?= $lang === 'bn' ? 'মেইন' : 'Main' ?></div>
        <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> <?= $lang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' ?></a>
        <a href="../index.php" target="_blank" class="menu-item"><i class="fas fa-external-link-alt"></i> <?= $lang === 'bn' ? 'পোর্টফোলিও দেখো' : 'View Portfolio' ?></a>
        <div class="menu-label"><?= $lang === 'bn' ? 'ব্যবস্থাপনা' : 'Manage' ?></div>
        <a href="projects.php" class="menu-item active"><i class="fas fa-code"></i> <?= $lang === 'bn' ? 'প্রজেক্ট' : 'Projects' ?></a>
        <a href="skills.php" class="menu-item"><i class="fas fa-star"></i> <?= $lang === 'bn' ? 'দক্ষতা' : 'Skills' ?></a>
        <a href="blogs.php" class="menu-item"><i class="fas fa-pen"></i> <?= $lang === 'bn' ? 'ব্লগ' : 'Blogs' ?></a>
        <a href="testimonials.php" class="menu-item"><i class="fas fa-quote-right"></i> <?= $lang === 'bn' ? 'মতামত' : 'Testimonials' ?></a>
        <a href="about.php" class="menu-item"><i class="fas fa-user"></i> <?= $lang === 'bn' ? 'আমার সম্পর্কে' : 'About Me' ?></a>
        <div class="menu-label"><?= $lang === 'bn' ? 'ইনবক্স' : 'Inbox' ?></div>
        <a href="messages.php" class="menu-item"><i class="fas fa-envelope"></i> <?= $lang === 'bn' ? 'বার্তা' : 'Messages' ?></a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= $lang === 'bn' ? 'লগআউট' : 'Logout' ?></a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> <span><?= $lang === 'bn' ? 'পেছনে' : 'Back' ?></span></a>
            <h1><i class="fas fa-code"></i> <?= $lang === 'bn' ? 'প্রজেক্ট' : 'Projects' ?></h1>
        </div>
        <div class="topbar-right">
            <a href="?lang=bn<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?><?= isset($_GET['action']) ? '&action=' . $_GET['action'] : '' ?><?= isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" class="lang-toggle">🇧🇩 বাংলা</a>
            <a href="?lang=en<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?><?= isset($_GET['action']) ? '&action=' . $_GET['action'] : '' ?><?= isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" class="lang-toggle">🌐 English</a>
            <?php if ($action === 'list'): ?>
                <a href="projects.php?action=add&lang=<?= $lang ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= $lang === 'bn' ? 'নতুন প্রজেক্ট' : 'Add Project' ?></a>
            <?php else: ?>
                <a href="projects.php?lang=<?= $lang ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?= $lang === 'bn' ? 'তালিকায় ফিরে' : 'Back to List' ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php if ($_GET['success'] === 'saved'): ?>
                    <?= $lang === 'bn' ? '✅ প্রজেক্ট সফলভাবে সংরক্ষণ করা হয়েছে!' : '✅ Project saved successfully!' ?>
                <?php elseif ($_GET['success'] === 'deleted'): ?>
                    <?= $lang === 'bn' ? '✅ প্রজেক্ট সফলভাবে মুছে ফেলা হয়েছে!' : '✅ Project deleted successfully!' ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <!-- FORM -->
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-<?= $action === 'edit' ? 'edit' : 'plus-circle' ?>"></i> <?= $action === 'edit' ? ($lang === 'bn' ? 'প্রজেক্ট সম্পাদনা' : 'Edit Project') : ($lang === 'bn' ? 'নতুন প্রজেক্ট যোগ করুন' : 'Add New Project') ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                        <input type="hidden" name="old_image" value="<?= $edit_data['image'] ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label><i class="fas fa-tag"></i> <?= $lang === 'bn' ? 'প্রজেক্টের শিরোনাম *' : 'Project Title *' ?> <span class="required">*</span></label>
                            <input type="text" name="title" required value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" placeholder="<?= $lang === 'bn' ? 'ই-কমার্স ওয়েবসাইট' : 'E-Commerce Website' ?>">
                        </div>
                        <div class="form-group full">
                            <label><i class="fas fa-align-left"></i> <?= $lang === 'bn' ? 'বিবরণ *' : 'Description *' ?> <span class="required">*</span></label>
                            <textarea name="description" rows="4" required placeholder="<?= $lang === 'bn' ? 'প্রজেক্ট সম্পর্কে বিস্তারিত লিখুন...' : 'Write about your project in detail...' ?>"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group full">
                            <label><i class="fas fa-cogs"></i> <?= $lang === 'bn' ? 'টেক স্ট্যাক (কমা দিয়ে আলাদা করুন)' : 'Tech Stack (comma separated)' ?></label>
                            <input type="text" name="tech_stack" value="<?= htmlspecialchars($edit_data['tech_stack'] ?? '') ?>" placeholder="<?= $lang === 'bn' ? 'PHP, MySQL, JavaScript' : 'PHP, MySQL, JavaScript' ?>">
                            <div class="help-text"><?= $lang === 'bn' ? 'প্রযুক্তিগুলো কমা দিয়ে আলাদা করুন' : 'Separate technologies with commas' ?></div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i> <?= $lang === 'bn' ? 'লাইভ ইউআরএল' : 'Live URL' ?></label>
                            <input type="url" name="live_url" value="<?= htmlspecialchars($edit_data['live_url'] ?? '') ?>" placeholder="https://example.com">
                        </div>
                        <div class="form-group">
                            <label><i class="fab fa-github"></i> GitHub URL</label>
                            <input type="url" name="github_url" value="<?= htmlspecialchars($edit_data['github_url'] ?? '') ?>" placeholder="https://github.com/...">
                        </div>
                        <div class="form-group full">
                            <label><i class="fas fa-image"></i> <?= $lang === 'bn' ? 'প্রজেক্টের ছবি' : 'Project Image' ?></label>
                            <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="photo-preview">
                                <?php if (!empty($edit_data['image'])): ?>
                                    <img src="../assets/images/<?= htmlspecialchars($edit_data['image']) ?>" id="previewImg" alt="Current image">
                                <?php else: ?>
                                    <div class="no-photo" id="previewPlaceholder"><i class="fas fa-image"></i></div>
                                    <img id="previewImg" style="display:none;">
                                <?php endif; ?>
                                <span style="color:#94a3b8;font-size:0.85rem;"><?= $lang === 'bn' ? '(JPG, PNG, GIF, WEBP)' : '(JPG, PNG, GIF, WEBP)' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $action === 'edit' ? ($lang === 'bn' ? 'আপডেট করো' : 'Update') : ($lang === 'bn' ? 'সংরক্ষণ করো' : 'Save') ?></button>
                        <a href="projects.php?lang=<?= $lang ?>" class="btn btn-secondary"><i class="fas fa-times"></i> <?= $lang === 'bn' ? 'বাতিল করো' : 'Cancel' ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- LIST -->
        <?php if ($action === 'list'): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> <?= $lang === 'bn' ? 'সব প্রজেক্ট' : 'All Projects' ?> <span style="color:#94a3b8;font-weight:400;font-size:0.85rem;">(<?= count($projects) ?>)</span></h3>
            </div>
            <div class="projects-grid">
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $p): ?>
                        <div class="project-item">
                            <?php if (!empty($p['image'])): ?>
                                <img src="../assets/images/<?= htmlspecialchars($p['image']) ?>" class="project-image" alt="<?= htmlspecialchars($p['title']) ?>">
                            <?php else: ?>
                                <div class="project-image-placeholder"><i class="fas fa-code"></i></div>
                            <?php endif; ?>
                            <div class="project-body">
                                <h3><?= htmlspecialchars($p['title']) ?></h3>
                                <p><?= htmlspecialchars(substr($p['description'], 0, 100)) ?>...</p>
                                <?php if (!empty($p['tech_stack'])): ?>
                                    <div class="tech-tags">
                                        <?php foreach (explode(',', $p['tech_stack']) as $tech): ?>
                                            <span><?= htmlspecialchars(trim($tech)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="project-links">
                                    <?php if (!empty($p['live_url'])): ?>
                                        <a href="<?= $p['live_url'] ?>" target="_blank"><i class="fas fa-external-link-alt"></i> Live</a>
                                    <?php endif; ?>
                                    <?php if (!empty($p['github_url'])): ?>
                                        <a href="<?= $p['github_url'] ?>" target="_blank"><i class="fab fa-github"></i> GitHub</a>
                                    <?php endif; ?>
                                </div>
                                <div class="project-actions">
                                    <a href="projects.php?action=edit&id=<?= $p['id'] ?>&lang=<?= $lang ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> <?= $lang === 'bn' ? 'সম্পাদনা' : 'Edit' ?></a>
                                    <a href="projects.php?action=delete&id=<?= $p['id'] ?>&lang=<?= $lang ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= $lang === 'bn' ? 'আপনি কি এই প্রজেক্টটি মুছে ফেলতে চান?' : 'Are you sure you want to delete this project?' ?>')"><i class="fas fa-trash"></i> <?= $lang === 'bn' ? 'মুছে ফেলো' : 'Delete' ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column:1/-1;">
                        <i class="fas fa-code"></i>
                        <h3><?= $lang === 'bn' ? 'কোন প্রজেক্ট নেই' : 'No Projects Yet' ?></h3>
                        <p><?= $lang === 'bn' ? 'এখনো কোনো প্রজেক্ট যোগ করা হয়নি। প্রথম প্রজেক্ট যোগ করুন!' : 'No projects have been added yet. Add your first project!' ?></p>
                        <a href="projects.php?action=add&lang=<?= $lang ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= $lang === 'bn' ? 'প্রজেক্ট যোগ করুন' : 'Add Project' ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function previewImage(input) {
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

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            this.innerHTML = sidebar.classList.contains('open') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            if (hamburger) hamburger.innerHTML = '<i class="fas fa-bars"></i>';
        });
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            if (hamburger) hamburger.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
});
</script>
</body>
</html>