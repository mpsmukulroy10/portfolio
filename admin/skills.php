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

// ── DELETE ──
if ($action === 'delete' && $id) {
    $pdo->prepare("DELETE FROM skills WHERE id = ?")->execute([$id]);
    header('Location: skills.php?success=deleted&lang=' . $lang);
    exit;
}

// ── SAVE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $level    = intval($_POST['level'] ?? 50);
    $icon     = trim($_POST['icon'] ?? '');
    $edit_id  = $_POST['edit_id'] ?? null;

    if (empty($name) || empty($category)) {
        $error = $lang === 'bn' ? 'নাম ও ক্যাটাগরি দিতে হবে' : 'Name and category are required';
    } else {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE skills SET name=?, category=?, level=?, icon=? WHERE id=?");
            $stmt->execute([$name, $category, $level, $icon, $edit_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO skills (name, category, level, icon) VALUES (?,?,?,?)");
            $stmt->execute([$name, $category, $level, $icon]);
        }
        header('Location: skills.php?success=saved&lang=' . $lang);
        exit;
    }
}

// ── EDIT DATA ──
$edit_data = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM skills WHERE id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch();
}

// ── LIST ──
$skills = $pdo->query("SELECT * FROM skills ORDER BY category, level DESC")->fetchAll();
$grouped = [];
foreach ($skills as $s) {
    $grouped[$s['category']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'bn' ? 'দক্ষতা - অ্যাডমিন' : 'Skills - Admin' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Same base styles as projects.php - keeping it consistent */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; min-height: 100vh; }
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
        .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; flex-wrap: wrap; gap: 12px; }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .topbar-left h1 { font-size: 1.3rem; display: flex; align-items: center; gap: 10px; }
        .topbar-left h1 i { color: #6366f1; }
        .topbar-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; background: rgba(99,102,241,0.15); color: #6366f1; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.3s; border: 1px solid rgba(99,102,241,0.2); }
        .back-btn:hover { background: #6366f1; color: #fff; border-color: #6366f1; transform: translateX(-3px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .lang-toggle { background: transparent; border: 1px solid #334155; color: #94a3b8; padding: 8px 16px; border-radius: 8px; cursor: pointer; text-decoration: none; transition: all 0.3s; font-size: 0.85rem; }
        .lang-toggle:hover { border-color: #6366f1; color: #6366f1; background: rgba(99,102,241,0.05); }
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
        .content { padding: 32px; flex: 1; }
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 0.95rem; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.25); color: #22c55e; }
        .alert-error { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.25); color: #f87171; }
        .alert i { font-size: 1.2rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; margin-bottom: 28px; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #334155; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .card-header h3 { font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .card-header h3 i { color: #6366f1; }
        .card-body { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; color: #94a3b8; font-weight: 500; display: flex; align-items: center; gap: 6px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select { padding: 11px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; font-size: 0.9rem; font-family: inherit; outline: none; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
        .form-group select option { background: #1e293b; }
        .form-actions { display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap; }

        /* RANGE SLIDER */
        .range-wrapper { position: relative; }
        .range-value { position: absolute; right: 0; top: -22px; background: #6366f1; color: white; padding: 2px 10px; border-radius: 99px; font-size: 0.8rem; font-weight: 700; }
        input[type="range"] { width: 100%; height: 6px; appearance: none; background: linear-gradient(90deg, #6366f1 var(--val), #334155 var(--val)); border-radius: 99px; cursor: pointer; border: none; padding: 0; }
        input[type="range"]::-webkit-slider-thumb { appearance: none; width: 18px; height: 18px; background: #6366f1; border-radius: 50%; border: 2px solid white; cursor: pointer; }

        /* ICON SUGGESTIONS */
        .icon-suggestions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .icon-pill { background: #162032; border: 1px solid #334155; padding: 4px 12px; border-radius: 99px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
        .icon-pill:hover { border-color: #6366f1; background: rgba(99,102,241,0.1); }

        /* SKILLS LIST */
        .skills-list { padding: 0; }
        .category-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #22d3ee; padding: 12px 24px; border-bottom: 1px solid #334155; background: #162032; }
        .skill-row { display: flex; align-items: center; gap: 16px; padding: 14px 24px; border-bottom: 1px solid #162032; transition: background 0.2s; }
        .skill-row:hover { background: rgba(99,102,241,0.05); }
        .skill-row:last-child { border-bottom: none; }
        .skill-icon-box { font-size: 1.4rem; width: 36px; text-align: center; }
        .skill-info { flex: 1; }
        .skill-info strong { font-size: 0.95rem; display: block; }
        .skill-bar-wrap { margin-top: 6px; background: #334155; border-radius: 99px; height: 5px; }
        .skill-bar-fill { height: 5px; border-radius: 99px; background: linear-gradient(90deg, #6366f1, #22d3ee); }
        .skill-percent { color: #6366f1; font-weight: 700; font-size: 0.85rem; min-width: 40px; text-align: right; }
        .action-btns { display: flex; gap: 8px; }

        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state i { font-size: 3rem; color: #334155; display: block; margin-bottom: 16px; }
        .empty-state h3 { color: #e2e8f0; margin-bottom: 8px; }
        .empty-state p { margin-bottom: 16px; }

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
            .skill-row { flex-wrap: wrap; padding: 12px 16px; }
            .action-btns { margin-left: auto; }
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
        <a href="projects.php" class="menu-item"><i class="fas fa-code"></i> <?= $lang === 'bn' ? 'প্রজেক্ট' : 'Projects' ?></a>
        <a href="skills.php" class="menu-item active"><i class="fas fa-star"></i> <?= $lang === 'bn' ? 'দক্ষতা' : 'Skills' ?></a>
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
            <h1><i class="fas fa-star"></i> <?= $lang === 'bn' ? 'দক্ষতা' : 'Skills' ?></h1>
        </div>
        <div class="topbar-right">
            <a href="?lang=bn<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?><?= isset($_GET['action']) ? '&action=' . $_GET['action'] : '' ?><?= isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" class="lang-toggle">🇧🇩 বাংলা</a>
            <a href="?lang=en<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?><?= isset($_GET['action']) ? '&action=' . $_GET['action'] : '' ?><?= isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" class="lang-toggle">🌐 English</a>
            <?php if ($action === 'list'): ?>
                <a href="skills.php?action=add&lang=<?= $lang ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= $lang === 'bn' ? 'নতুন দক্ষতা' : 'Add Skill' ?></a>
            <?php else: ?>
                <a href="skills.php?lang=<?= $lang ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?= $lang === 'bn' ? 'তালিকায় ফিরে' : 'Back to List' ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php if ($_GET['success'] === 'saved'): ?>
                    <?= $lang === 'bn' ? '✅ দক্ষতা সফলভাবে সংরক্ষণ করা হয়েছে!' : '✅ Skill saved successfully!' ?>
                <?php elseif ($_GET['success'] === 'deleted'): ?>
                    <?= $lang === 'bn' ? '✅ দক্ষতা সফলভাবে মুছে ফেলা হয়েছে!' : '✅ Skill deleted successfully!' ?>
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
                <h3><i class="fas fa-<?= $action === 'edit' ? 'edit' : 'plus-circle' ?>"></i> <?= $action === 'edit' ? ($lang === 'bn' ? 'দক্ষতা সম্পাদনা' : 'Edit Skill') : ($lang === 'bn' ? 'নতুন দক্ষতা যোগ করুন' : 'Add New Skill') ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> <?= $lang === 'bn' ? 'দক্ষতার নাম *' : 'Skill Name *' ?> <span class="required">*</span></label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($edit_data['name'] ?? '') ?>" placeholder="<?= $lang === 'bn' ? 'PHP, MySQL, React' : 'PHP, MySQL, React' ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-folder"></i> <?= $lang === 'bn' ? 'ক্যাটাগরি *' : 'Category *' ?> <span class="required">*</span></label>
                            <select name="category" required>
                                <option value="">-- <?= $lang === 'bn' ? 'নির্বাচন করুন' : 'Select' ?> --</option>
                                <?php foreach (['Frontend','Backend','Database','Tool','Other'] as $cat): ?>
                                    <option value="<?= $cat ?>" <?= ($edit_data['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-icons"></i> <?= $lang === 'bn' ? 'আইকন (ইমোজি বা টেক্সট)' : 'Icon (Emoji or Text)' ?></label>
                            <input type="text" name="icon" id="iconInput" value="<?= htmlspecialchars($edit_data['icon'] ?? '') ?>" placeholder="<?= $lang === 'bn' ? '🐘 বা ⚡' : '🐘 or ⚡' ?>">
                            <div class="icon-suggestions">
                                <?php foreach (['🐘','🐍','⚛️','🎨','🗄️','⚡','🔧','🐱','🐳','☁️','📱','🔥'] as $ic): ?>
                                    <span class="icon-pill" onclick="document.getElementById('iconInput').value='<?= $ic ?>'"><?= $ic ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-chart-bar"></i> <?= $lang === 'bn' ? 'লেভেল' : 'Level' ?> <span id="levelVal"><?= $edit_data['level'] ?? 50 ?></span>%</label>
                            <div class="range-wrapper" style="margin-top:12px">
                                <input type="range" name="level" min="1" max="100" value="<?= $edit_data['level'] ?? 50 ?>" oninput="updateRange(this)">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $action === 'edit' ? ($lang === 'bn' ? 'আপডেট করো' : 'Update') : ($lang === 'bn' ? 'সংরক্ষণ করো' : 'Save') ?></button>
                        <a href="skills.php?lang=<?= $lang ?>" class="btn btn-secondary"><i class="fas fa-times"></i> <?= $lang === 'bn' ? 'বাতিল করো' : 'Cancel' ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- LIST -->
        <?php if ($action === 'list'): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> <?= $lang === 'bn' ? 'সব দক্ষতা' : 'All Skills' ?> <span style="color:#94a3b8;font-weight:400;font-size:0.85rem;">(<?= count($skills) ?>)</span></h3>
            </div>
            <div class="skills-list">
                <?php if (!empty($grouped)): ?>
                    <?php foreach ($grouped as $category => $cat_skills): ?>
                        <div class="category-title"><i class="fas fa-folder-open"></i> <?= htmlspecialchars($category) ?> (<?= count($cat_skills) ?>)</div>
                        <?php foreach ($cat_skills as $s): ?>
                            <div class="skill-row">
                                <div class="skill-icon-box"><?= htmlspecialchars($s['icon'] ?? '⚡') ?></div>
                                <div class="skill-info">
                                    <strong><?= htmlspecialchars($s['name']) ?></strong>
                                    <div class="skill-bar-wrap">
                                        <div class="skill-bar-fill" style="width:<?= $s['level'] ?>%"></div>
                                    </div>
                                </div>
                                <span class="skill-percent"><?= $s['level'] ?>%</span>
                                <div class="action-btns">
                                    <a href="skills.php?action=edit&id=<?= $s['id'] ?>&lang=<?= $lang ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i></a>
                                    <a href="skills.php?action=delete&id=<?= $s['id'] ?>&lang=<?= $lang ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= $lang === 'bn' ? 'আপনি কি এই দক্ষতাটি মুছে ফেলতে চান?' : 'Are you sure you want to delete this skill?' ?>')"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-star"></i>
                        <h3><?= $lang === 'bn' ? 'কোন দক্ষতা নেই' : 'No Skills Yet' ?></h3>
                        <p><?= $lang === 'bn' ? 'এখনো কোনো দক্ষতা যোগ করা হয়নি। প্রথম দক্ষতা যোগ করুন!' : 'No skills have been added yet. Add your first skill!' ?></p>
                        <a href="skills.php?action=add&lang=<?= $lang ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= $lang === 'bn' ? 'দক্ষতা যোগ করুন' : 'Add Skill' ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateRange(input) {
    document.getElementById('levelVal').textContent = input.value;
    input.style.setProperty('--val', input.value + '%');
    input.style.background = `linear-gradient(90deg, #6366f1 ${input.value}%, #334155 ${input.value}%)`;
}
document.querySelectorAll('input[type="range"]').forEach(r => updateRange(r));

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