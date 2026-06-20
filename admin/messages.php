<?php
require_once '../config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

// Mark as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
    $stmt->execute([$_GET['mark_read']]);
    header('Location: messages.php?lang=' . $lang);
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $pdo->exec("UPDATE contacts SET is_read = 1");
    header('Location: messages.php?lang=' . $lang . '&success=all_read');
    exit;
}

// Delete single
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: messages.php?lang=' . $lang . '&success=deleted');
    exit;
}

// Delete all
if (isset($_GET['delete_all'])) {
    $pdo->exec("DELETE FROM contacts");
    header('Location: messages.php?lang=' . $lang . '&success=all_deleted');
    exit;
}

// Fetch all messages
$messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();

// Count unread
$unread_count = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'bn' ? 'বার্তা - অ্যাডমিন' : 'Messages - Admin' ?></title>
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
        .badge { margin-left: auto; background: #ef4444; color: white; font-size: 0.7rem; padding: 2px 8px; border-radius: 99px; font-weight: 600; }
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
        .btn-success { background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
        .btn-success:hover { background: #22c55e; color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
        .btn-warning { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
        .btn-warning:hover { background: #f59e0b; color: #fff; }

        /* ===== CONTENT ===== */
        .content { padding: 32px; flex: 1; }

        /* ===== ALERTS ===== */
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 0.95rem; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.25); color: #22c55e; }
        .alert-info { background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25); color: #6366f1; }
        .alert i { font-size: 1.2rem; }

        /* ===== CARD ===== */
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #334155; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .card-header h3 { font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .card-header h3 i { color: #6366f1; }
        .card-body { padding: 0; overflow-x: auto; }

        /* ===== TABLE ===== */
        table { width: 100%; border-collapse: collapse; }
        th { background: #162032; padding: 14px 18px; text-align: left; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; position: sticky; top: 0; z-index: 5; }
        td { padding: 14px 18px; font-size: 0.9rem; border-bottom: 1px solid #1e293b; color: #cbd5e1; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(99,102,241,0.05); }
        tr.unread td { background: rgba(99,102,241,0.08); }
        tr.unread td:first-child { position: relative; }
        tr.unread td:first-child::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #6366f1; }
        .unread-badge { display: inline-block; background: #6366f1; color: white; padding: 2px 8px; border-radius: 99px; font-size: 0.65rem; font-weight: 600; margin-left: 6px; }

        /* ===== MESSAGE DETAILS ===== */
        .msg-subject { font-weight: 600; color: #e2e8f0; }
        .msg-preview { color: #94a3b8; font-size: 0.85rem; }
        .msg-time { color: #64748b; font-size: 0.8rem; white-space: nowrap; }
        .msg-actions { display: flex; gap: 6px; flex-wrap: wrap; }

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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .topbar { padding: 12px 16px; }
            .topbar-left h1 { font-size: 1rem; }
            .content { padding: 16px; }
            .card-header { padding: 14px 16px; flex-direction: column; align-items: stretch; }
            .back-btn span { display: none; }
            .back-btn { padding: 8px 12px; }
            .hamburger { display: flex !important; }
            table { font-size: 0.85rem; }
            th, td { padding: 10px 12px; }
            .msg-actions { flex-direction: column; }
            .msg-actions .btn { width: 100%; justify-content: center; }
            .topbar-right { flex-wrap: wrap; }
        }
        @media (max-width: 576px) {
            .topbar-right .lang-toggle { padding: 6px 10px; font-size: 0.75rem; }
            .topbar-right .btn { padding: 6px 12px; font-size: 0.8rem; }
            .card-header .btn { width: 100%; justify-content: center; }
            .msg-time { font-size: 0.7rem; }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body>

<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- SIDEBAR -->
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
        <a href="about.php" class="menu-item"><i class="fas fa-user"></i> <?= $lang === 'bn' ? 'আমার সম্পর্কে' : 'About Me' ?></a>
        <div class="menu-label"><?= $lang === 'bn' ? 'ইনবক্স' : 'Inbox' ?></div>
        <a href="messages.php" class="menu-item active"><i class="fas fa-envelope"></i> <?= $lang === 'bn' ? 'বার্তা' : 'Messages' ?>
            <?php if ($unread_count > 0): ?>
                <span class="badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= $lang === 'bn' ? 'লগআউট' : 'Logout' ?></a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> <span><?= $lang === 'bn' ? 'পেছনে' : 'Back' ?></span></a>
            <h1><i class="fas fa-envelope"></i> <?= $lang === 'bn' ? 'বার্তা' : 'Messages' ?>
                <?php if ($unread_count > 0): ?>
                    <span style="font-size:0.8rem;color:#f87171;font-weight:400;">(<?= $unread_count ?> <?= $lang === 'bn' ? 'অপঠিত' : 'unread' ?>)</span>
                <?php endif; ?>
            </h1>
        </div>
        <div class="topbar-right">
            <a href="?lang=bn<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?>" class="lang-toggle">🇧🇩 বাংলা</a>
            <a href="?lang=en<?= isset($_GET['success']) ? '&success=' . $_GET['success'] : '' ?>" class="lang-toggle">🌐 English</a>
            
            <?php if (!empty($messages)): ?>
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1&lang=<?= $lang ?>" class="btn btn-success btn-sm" onclick="return confirm('<?= $lang === 'bn' ? 'সব বার্তা কি পড়া হিসেবে চিহ্নিত করবেন?' : 'Mark all messages as read?' ?>')">
                        <i class="fas fa-check-double"></i> <?= $lang === 'bn' ? 'সব পড়া' : 'Mark All Read' ?>
                    </a>
                <?php endif; ?>
                <a href="?delete_all=1&lang=<?= $lang ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= $lang === 'bn' ? 'সব বার্তা কি মুছে ফেলবেন? এটা পুনরুদ্ধার করা যাবে না!' : 'Delete all messages? This cannot be undone!' ?>')">
                    <i class="fas fa-trash-alt"></i> <?= $lang === 'bn' ? 'সব মুছে ফেলো' : 'Delete All' ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="content">
        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'deleted'): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $lang === 'bn' ? '✅ বার্তা মুছে ফেলা হয়েছে!' : '✅ Message deleted!' ?></div>
            <?php elseif ($_GET['success'] === 'all_deleted'): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $lang === 'bn' ? '✅ সব বার্তা মুছে ফেলা হয়েছে!' : '✅ All messages deleted!' ?></div>
            <?php elseif ($_GET['success'] === 'all_read'): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= $lang === 'bn' ? '✅ সব বার্তা পড়া হিসেবে চিহ্নিত করা হয়েছে!' : '✅ All messages marked as read!' ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-inbox"></i>
                    <?= $lang === 'bn' ? 'সব বার্তা' : 'All Messages' ?>
                    <span style="color:#94a3b8;font-weight:400;font-size:0.85rem;">(<?= count($messages) ?>)</span>
                </h3>
                <span style="color:#94a3b8;font-size:0.85rem;">
                    <?php if ($unread_count > 0): ?>
                        <i class="fas fa-circle" style="color:#6366f1;font-size:0.5rem;"></i>
                        <?= $unread_count ?> <?= $lang === 'bn' ? 'অপঠিত' : 'unread' ?>
                    <?php else: ?>
                        <i class="fas fa-check-circle" style="color:#22c55e;"></i>
                        <?= $lang === 'bn' ? 'সব পড়া হয়েছে' : 'All read' ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (!empty($messages)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:30%;"><?= $lang === 'bn' ? 'বিবরণ' : 'Details' ?></th>
                                <th style="width:25%;"><?= $lang === 'bn' ? 'বার্তা' : 'Message' ?></th>
                                <th style="width:20%;"><?= $lang === 'bn' ? 'সময়' : 'Time' ?></th>
                                <th style="width:25%;"><?= $lang === 'bn' ? 'অ্যাকশন' : 'Action' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                                <tr class="<?= $msg['is_read'] ? '' : 'unread' ?>">
                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:4px;">
                                            <strong style="color:#e2e8f0;"><?= htmlspecialchars($msg['name']) ?></strong>
                                            <span style="color:#94a3b8;font-size:0.85rem;">
                                                <i class="fas fa-envelope" style="font-size:0.7rem;"></i>
                                                <?= htmlspecialchars($msg['email']) ?>
                                            </span>
                                            <?php if (!$msg['is_read']): ?>
                                                <span class="unread-badge"><?= $lang === 'bn' ? 'নতুন' : 'New' ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:4px;">
                                            <span class="msg-subject"><?= htmlspecialchars($msg['subject']) ?></span>
                                            <span class="msg-preview">
                                                <?= htmlspecialchars(substr($msg['message'], 0, 80)) ?>
                                                <?php if (strlen($msg['message']) > 80): ?>...<?php endif; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="msg-time">
                                            <i class="far fa-calendar-alt"></i>
                                            <?= date('d M Y', strtotime($msg['created_at'])) ?>
                                            <br>
                                            <i class="far fa-clock"></i>
                                            <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="msg-actions">
                                            <?php if (!$msg['is_read']): ?>
                                                <a href="?mark_read=<?= $msg['id'] ?>&lang=<?= $lang ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> <?= $lang === 'bn' ? 'পড়া' : 'Read' ?>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-primary btn-sm view-msg-btn" 
                                                    data-name="<?= htmlspecialchars($msg['name']) ?>"
                                                    data-email="<?= htmlspecialchars($msg['email']) ?>"
                                                    data-subject="<?= htmlspecialchars($msg['subject']) ?>"
                                                    data-message="<?= htmlspecialchars($msg['message']) ?>"
                                                    data-time="<?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?>"
                                                    onclick="viewMessage(this)">
                                                <i class="fas fa-eye"></i> <?= $lang === 'bn' ? 'দেখো' : 'View' ?>
                                            </button>
                                            <a href="?delete=<?= $msg['id'] ?>&lang=<?= $lang ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= $lang === 'bn' ? 'এই বার্তাটি মুছে ফেলবেন?' : 'Delete this message?' ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3><?= $lang === 'bn' ? 'কোন বার্তা নেই' : 'No Messages' ?></h3>
                        <p><?= $lang === 'bn' ? 'এখনো কোনো বার্তা আসেনি। আপনার পোর্টফোলিও শেয়ার করুন!' : 'No messages have been received yet. Share your portfolio!' ?></p>
                        <a href="../index.php" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> <?= $lang === 'bn' ? 'পোর্টফোলিও দেখো' : 'View Portfolio' ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL FOR VIEWING MESSAGE ===== -->
<div id="messageModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#1e293b;border:1px solid #334155;border-radius:12px;max-width:600px;width:100%;max-height:80vh;overflow-y:auto;padding:30px;position:relative;">
        <button onclick="closeModal()" style="position:absolute;top:12px;right:16px;background:transparent;border:none;color:#94a3b8;font-size:1.5rem;cursor:pointer;transition:color 0.3s;">
            <i class="fas fa-times"></i>
        </button>
        <h3 style="color:#6366f1;margin-bottom:16px;"><i class="fas fa-envelope"></i> <?= $lang === 'bn' ? 'বার্তার বিবরণ' : 'Message Details' ?></h3>
        <div style="margin-bottom:12px;">
            <label style="color:#94a3b8;font-size:0.85rem;display:block;"><?= $lang === 'bn' ? 'নাম' : 'Name' ?></label>
            <p id="modalName" style="color:#e2e8f0;font-weight:500;"></p>
        </div>
        <div style="margin-bottom:12px;">
            <label style="color:#94a3b8;font-size:0.85rem;display:block;">Email</label>
            <p id="modalEmail" style="color:#e2e8f0;"></p>
        </div>
        <div style="margin-bottom:12px;">
            <label style="color:#94a3b8;font-size:0.85rem;display:block;"><?= $lang === 'bn' ? 'বিষয়' : 'Subject' ?></label>
            <p id="modalSubject" style="color:#e2e8f0;font-weight:500;"></p>
        </div>
        <div style="margin-bottom:12px;">
            <label style="color:#94a3b8;font-size:0.85rem;display:block;"><?= $lang === 'bn' ? 'বার্তা' : 'Message' ?></label>
            <p id="modalMessage" style="color:#cbd5e1;background:#0f172a;padding:16px;border-radius:8px;border:1px solid #334155;white-space:pre-wrap;line-height:1.7;"></p>
        </div>
        <div style="margin-bottom:16px;">
            <label style="color:#94a3b8;font-size:0.85rem;display:block;"><?= $lang === 'bn' ? 'প্রাপ্তির সময়' : 'Received' ?></label>
            <p id="modalTime" style="color:#94a3b8;font-size:0.9rem;"></p>
        </div>
        <button onclick="closeModal()" class="btn btn-secondary" style="width:100%;justify-content:center;">
            <i class="fas fa-times"></i> <?= $lang === 'bn' ? 'বন্ধ করো' : 'Close' ?>
        </button>
    </div>
</div>

<script>
// ===== MODAL FUNCTIONS =====
function viewMessage(btn) {
    document.getElementById('modalName').textContent = btn.dataset.name;
    document.getElementById('modalEmail').textContent = btn.dataset.email;
    document.getElementById('modalSubject').textContent = btn.dataset.subject;
    document.getElementById('modalMessage').textContent = btn.dataset.message;
    document.getElementById('modalTime').textContent = btn.dataset.time;
    document.getElementById('messageModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('messageModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal on outside click
document.getElementById('messageModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

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