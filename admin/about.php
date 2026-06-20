<?php
require_once '../config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$error = '';

// Fetch existing about data
$about = $pdo->query("SELECT * FROM about WHERE id = 1")->fetch();

// যদি ডাটা না থাকে তাহলে ডিফল্ট ইনসার্ট করুন
if (!$about) {
    // প্রথমে কলামগুলো আছে কিনা চেক করুন
    try {
        $pdo->query("SELECT github_url FROM about LIMIT 1");
    } catch (PDOException $e) {
        // কলাম না থাকলে যোগ করুন
        $pdo->exec("ALTER TABLE about ADD COLUMN github_url VARCHAR(255) DEFAULT NULL AFTER cv_url");
        $pdo->exec("ALTER TABLE about ADD COLUMN linkedin_url VARCHAR(255) DEFAULT NULL AFTER github_url");
        $pdo->exec("ALTER TABLE about ADD COLUMN facebook_url VARCHAR(255) DEFAULT NULL AFTER linkedin_url");
        $pdo->exec("ALTER TABLE about ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER facebook_url");
    }
    
    $pdo->exec("INSERT INTO about (id, name, tagline, bio, photo, cv_url, github_url, linkedin_url, facebook_url, email) 
                VALUES (1, 'Your Name', 'Developer', 'Write something about yourself...', '', '', 'https://github.com/yourusername', 'https://linkedin.com/in/yourusername', 'https://facebook.com/yourusername', 'your@email.com')");
    $about = $pdo->query("SELECT * FROM about WHERE id = 1")->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $tagline     = trim($_POST['tagline'] ?? '');
    $bio         = trim($_POST['bio'] ?? '');
    $cv_url      = trim($_POST['cv_url'] ?? '');
    $github_url  = trim($_POST['github_url'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $facebook_url = trim($_POST['facebook_url'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $photo       = $about['photo'] ?? '';

    // Photo upload
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) {
            $photo = uniqid('profile_') . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], "../assets/images/" . $photo);
        } else {
            $error = $lang === 'bn' ? 'ভুল ইমেজ ফরম্যাট' : 'Invalid image format';
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE about SET 
                name = ?, 
                tagline = ?, 
                bio = ?, 
                photo = ?, 
                cv_url = ?, 
                github_url = ?, 
                linkedin_url = ?, 
                facebook_url = ?, 
                email = ? 
                WHERE id = 1");
            $stmt->execute([$name, $tagline, $bio, $photo, $cv_url, $github_url, $linkedin_url, $facebook_url, $email]);
            header('Location: about.php?success=1&lang=' . $lang);
            exit;
        } catch (PDOException $e) {
            $error = $lang === 'bn' ? 'ডাটাবেস আপডেট করতে সমস্যা হয়েছে: ' . $e->getMessage() : 'Database update error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'bn' ? 'আমার সম্পর্কে - অ্যাডমিন' : 'About Me - Admin' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0f172a; color: #e2e8f0; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; min-height: 100vh; }

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
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(99,102,241,0.3); }
        .btn-secondary { background: #334155; color: #e2e8f0; }
        .btn-secondary:hover { background: #475569; }
        .btn-group { display: flex; gap: 12px; margin-top: 10px; flex-wrap: wrap; }

        /* ===== CONTENT ===== */
        .content { padding: 32px; flex: 1; max-width: 900px; }

        /* ===== ALERTS ===== */
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 0.95rem; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.25); color: #22c55e; }
        .alert-error { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.25); color: #f87171; }
        .alert i { font-size: 1.2rem; }

        /* ===== CARD ===== */
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #334155; }
        .card-header h3 { font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .card-header h3 i { color: #6366f1; }
        .card-body { padding: 24px; }

        /* ===== FORM ===== */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; color: #94a3b8; font-weight: 500; display: flex; align-items: center; gap: 6px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group textarea { padding: 11px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 0.9rem; font-family: inherit; outline: none; transition: all 0.3s; width: 100%; }
        .form-group input:focus, .form-group textarea:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group .help-text { font-size: 0.78rem; color: #64748b; margin-top: 4px; }

        /* ===== IMAGE PREVIEW ===== */
        .photo-preview { display: flex; align-items: center; gap: 16px; margin-top: 8px; flex-wrap: wrap; }
        .photo-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid #6366f1; }
        .photo-preview .no-photo { width: 80px; height: 80px; background: #0f172a; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px dashed #334155; color: #475569; font-size: 1.5rem; }

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
            .card-header { padding: 14px 16px; }
            .card-body { padding: 16px; }
            .back-btn span { display: none; }
            .back-btn { padding: 8px 12px; }
            .hamburger { display: flex !important; }
        }
        @media (max-width: 576px) {
            .topbar-right .lang-toggle { padding: 6px 10px; font-size: 0.75rem; }
            .btn-group { flex-direction: column; }
            .btn-group .btn { width: 100%; justify-content: center; }
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
        <a href="projects.php" class="menu-item"><i class="fas fa-code"></i> <?= $lang === 'bn' ? 'প্রজেক্ট' : 'Projects' ?></a>
        <a href="skills.php" class="menu-item"><i class="fas fa-star"></i> <?= $lang === 'bn' ? 'দক্ষতা' : 'Skills' ?></a>
        <a href="blogs.php" class="menu-item"><i class="fas fa-pen"></i> <?= $lang === 'bn' ? 'ব্লগ' : 'Blogs' ?></a>
        <a href="testimonials.php" class="menu-item"><i class="fas fa-quote-right"></i> <?= $lang === 'bn' ? 'মতামত' : 'Testimonials' ?></a>
        <a href="about.php" class="menu-item active"><i class="fas fa-user"></i> <?= $lang === 'bn' ? 'আমার সম্পর্কে' : 'About Me' ?></a>
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
            <h1><i class="fas fa-user"></i> <?= $lang === 'bn' ? 'আমার সম্পর্কে' : 'About Me' ?></h1>
        </div>
        <div class="topbar-right">
            <a href="?lang=bn<?= isset($_GET['success']) ? '&success=1' : '' ?>" class="lang-toggle">🇧🇩 বাংলা</a>
            <a href="?lang=en<?= isset($_GET['success']) ? '&success=1' : '' ?>" class="lang-toggle">🌐 English</a>
        </div>
    </div>

    <div class="content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $lang === 'bn' ? '✅ সফলভাবে সংরক্ষণ করা হয়েছে!' : '✅ Saved successfully!' ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> <?= $lang === 'bn' ? 'প্রোফাইল সম্পাদনা' : 'Edit Profile' ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label><i class="fas fa-user"></i> <?= $lang === 'bn' ? 'পুরো নাম *' : 'Full Name *' ?> <span class="required">*</span></label>
                            <input type="text" name="name" value="<?= htmlspecialchars($about['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group full">
                            <label><i class="fas fa-tag"></i> <?= $lang === 'bn' ? 'ট্যাগলাইন' : 'Tagline' ?></label>
                            <input type="text" name="tagline" value="<?= htmlspecialchars($about['tagline'] ?? '') ?>" placeholder="e.g. Full Stack Developer">
                        </div>
                        <div class="form-group full">
                            <label><i class="fas fa-align-left"></i> <?= $lang === 'bn' ? 'বায়ো / বিবরণ' : 'Bio / Description' ?></label>
                            <textarea name="bio" rows="5"><?= htmlspecialchars($about['bio'] ?? '') ?></textarea>
                            <div class="help-text"><?= $lang === 'bn' ? 'প্রতিটি লাইন আলাদা প্যারাগ্রাফ হিসেবে দেখাবে' : 'Each line will show as a separate paragraph' ?></div>
                        </div>
                    </div>

                    <!-- Photo -->
                    <div class="form-group" style="margin-top:20px;">
                        <label><i class="fas fa-image"></i> <?= $lang === 'bn' ? 'প্রোফাইল ছবি' : 'Profile Photo' ?></label>
                        <input type="file" name="photo" accept="image/*" onchange="previewImage(this)">
                        <div class="photo-preview">
                            <?php if (!empty($about['photo'])): ?>
                                <img src="../assets/images/<?= htmlspecialchars($about['photo']) ?>" id="currentPhoto" alt="Current photo">
                            <?php else: ?>
                                <div class="no-photo" id="previewPlaceholder"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <img id="previewImg" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid #22c55e;">
                            <span style="color:#94a3b8;font-size:0.85rem;"><?= $lang === 'bn' ? '(JPG, PNG, GIF, WEBP)' : '(JPG, PNG, GIF, WEBP)' ?></span>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div style="margin-top:24px;padding-top:20px;border-top:1px solid #334155;">
                        <h3 style="color:#6366f1;margin-bottom:16px;">
                            <i class="fas fa-share-alt"></i> <?= $lang === 'bn' ? 'সোশ্যাল লিংক' : 'Social Links' ?>
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fab fa-github" style="color:#94a3b8"></i> GitHub</label>
                                <input type="url" name="github_url" value="<?= htmlspecialchars($about['github_url'] ?? '') ?>" placeholder="https://github.com/username">
                            </div>
                            <div class="form-group">
                                <label><i class="fab fa-linkedin" style="color:#0a66c2"></i> LinkedIn</label>
                                <input type="url" name="linkedin_url" value="<?= htmlspecialchars($about['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                            </div>
                            <div class="form-group">
                                <label><i class="fab fa-facebook" style="color:#1877f2"></i> Facebook</label>
                                <input type="url" name="facebook_url" value="<?= htmlspecialchars($about['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/username">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope" style="color:#ea4335"></i> Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($about['email'] ?? '') ?>" placeholder="your@email.com">
                            </div>
                        </div>
                    </div>

                    <!-- CV -->
                    <div style="margin-top:24px;padding-top:20px;border-top:1px solid #334155;">
                        <h3 style="color:#6366f1;margin-bottom:16px;">
                            <i class="fas fa-file-pdf"></i> <?= $lang === 'bn' ? 'সিভি আপলোড' : 'CV Upload' ?>
                        </h3>
                        <div class="form-group">
                            <label><?= $lang === 'bn' ? 'সিভি ডাউনলোড ইউআরএল' : 'CV Download URL' ?></label>
                            <input type="url" name="cv_url" value="<?= htmlspecialchars($about['cv_url'] ?? '') ?>" placeholder="https://drive.google.com/file/...">
                            <div class="help-text"><?= $lang === 'bn' ? 'গুগল ড্রাইভ বা সরাসরি ফাইলের লিংক দিন' : 'Provide Google Drive or direct file link' ?></div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $lang === 'bn' ? 'সংরক্ষণ করো' : 'Save Changes' ?>
                        </button>
                        <a href="../index.php" target="_blank" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> <?= $lang === 'bn' ? 'পোর্টফোলিও দেখো' : 'View Portfolio' ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('previewImg');
    const placeholder = document.getElementById('previewPlaceholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (placeholder) placeholder.style.display = 'none';
            preview.style.display = 'block';
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Mobile sidebar
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