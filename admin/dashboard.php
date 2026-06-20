<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

$stats = [
    'projects'     => $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
    'skills'       => $pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn(),
    'blogs'        => $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn(),
    'testimonials' => $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn(),
    'messages'     => $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
    'unread'       => $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn(),
];

$messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'bn' ? 'অ্যাডমিন ড্যাশবোর্ড' : 'Admin Dashboard' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; display: flex; min-height: 100vh; }

        .sidebar { width: 260px; background: #1e293b; border-right: 1px solid #334155; position: fixed; top: 0; left: 0; bottom: 0; display: flex; flex-direction: column; z-index: 100; }
        .sidebar-logo { padding: 28px 24px; border-bottom: 1px solid #334155; }
        .sidebar-logo h2 { color: #6366f1; font-size: 1.2rem; }
        .sidebar-logo p { color: #94a3b8; font-size: 0.8rem; margin-top: 2px; }
        .sidebar-menu { padding: 16px 0; flex: 1; overflow-y: auto; }
        .menu-label { padding: 8px 24px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #475569; margin-top: 8px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #94a3b8; text-decoration: none; transition: all 0.3s; font-size: 0.95rem; position: relative; }
        .menu-item:hover, .menu-item.active { color: #fff; background: rgba(99,102,241,0.15); }
        .menu-item.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #6366f1; }
        .menu-item i { width: 20px; text-align: center; color: #6366f1; }
        .badge { margin-left: auto; background: #ef4444; color: white; font-size: 0.7rem; padding: 2px 8px; border-radius: 99px; font-weight: 600; }
        .sidebar-footer { padding: 16px 24px; border-top: 1px solid #334155; }
        .sidebar-footer a { display: flex; align-items: center; gap: 10px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; transition: color 0.3s; }
        .sidebar-footer a:hover { color: #f87171; }

        .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .topbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .topbar-left h1 { font-size: 1.3rem; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .admin-info { display: flex; align-items: center; gap: 10px; color: #94a3b8; font-size: 0.9rem; }
        .admin-avatar { width: 36px; height: 36px; background: #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: white; font-weight: 700; }
        .lang-toggle { background: transparent; border: 1px solid #334155; color: #94a3b8; padding: 6px 14px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; text-decoration: none; transition: all 0.3s; }
        .lang-toggle:hover { border-color: #6366f1; color: #6366f1; }
        
        /* BACK BUTTON */
        .back-btn { display: inline-flex; align-items: center; gap: 8px; background: rgba(99,102,241,0.15); color: #6366f1; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.3s; border: 1px solid rgba(99,102,241,0.2); }
        .back-btn:hover { background: #6366f1; color: white; border-color: #6366f1; transform: translateX(-3px); }
        .back-btn i { font-size: 0.9rem; }

        .content { padding: 32px; flex: 1; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px 20px; display: flex; align-items: center; gap: 16px; transition: all 0.3s; }
        .stat-card:hover { border-color: #6366f1; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-info h3 { font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .stat-info p { color: #94a3b8; font-size: 0.85rem; margin-top: 4px; }

        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .section-header h2 { font-size: 1.1rem; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.3s; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; transform: translateY(-2px); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }

        .quick-actions { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 32px; }
        .quick-btn { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 16px; text-align: center; text-decoration: none; color: #e2e8f0; transition: all 0.3s; }
        .quick-btn:hover { border-color: #6366f1; background: rgba(99,102,241,0.1); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
        .quick-btn i { font-size: 1.5rem; color: #6366f1; display: block; margin-bottom: 8px; }
        .quick-btn span { font-size: 0.85rem; }

        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid #334155; display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #162032; padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; }
        td { padding: 14px 16px; font-size: 0.9rem; border-bottom: 1px solid #1e293b; color: #cbd5e1; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(99,102,241,0.05); }
        .unread-row td { color: #fff; font-weight: 500; }
        .unread-dot { display: inline-block; width: 8px; height: 8px; background: #6366f1; border-radius: 50%; margin-right: 6px; }
        .empty-state { text-align: center; padding: 40px; color: #94a3b8; font-size: 0.9rem; }

        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main { margin-left: 200px; }
            .stats-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
            .topbar { padding: 12px 16px; flex-wrap: wrap; gap: 10px; }
            .topbar-left h1 { font-size: 1rem; }
        }
        @media (max-width: 576px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .quick-actions { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <h2>⚡ Admin Panel</h2>
        <p><?= $lang === 'bn' ? 'পোর্টফোলিও ব্যবস্থাপনা' : 'Portfolio Management' ?></p>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-label"><?= $lang === 'bn' ? 'মেইন' : 'Main' ?></div>
        <a href="dashboard.php" class="menu-item active">
            <i class="fas fa-home"></i> <?= $lang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' ?>
        </a>
        <a href="../index.php" target="_blank" class="menu-item">
            <i class="fas fa-external-link-alt"></i> <?= $lang === 'bn' ? 'পোর্টফোলিও দেখো' : 'View Portfolio' ?>
        </a>
        <div class="menu-label"><?= $lang === 'bn' ? 'ব্যবস্থাপনা' : 'Manage' ?></div>
        <a href="projects.php" class="menu-item"><i class="fas fa-code"></i> <?= $lang === 'bn' ? 'প্রজেক্ট' : 'Projects' ?></a>
        <a href="skills.php" class="menu-item"><i class="fas fa-star"></i> <?= $lang === 'bn' ? 'দক্ষতা' : 'Skills' ?></a>
        <a href="blogs.php" class="menu-item"><i class="fas fa-pen"></i> <?= $lang === 'bn' ? 'ব্লগ' : 'Blogs' ?></a>
        <a href="testimonials.php" class="menu-item"><i class="fas fa-quote-right"></i> <?= $lang === 'bn' ? 'মতামত' : 'Testimonials' ?></a>
        <a href="about.php" class="menu-item"><i class="fas fa-user"></i> <?= $lang === 'bn' ? 'আমার সম্পর্কে' : 'About Me' ?></a>
        <div class="menu-label"><?= $lang === 'bn' ? 'ইনবক্স' : 'Inbox' ?></div>
        <a href="messages.php" class="menu-item">
            <i class="fas fa-envelope"></i> <?= $lang === 'bn' ? 'বার্তা' : 'Messages' ?>
            <?php if ($stats['unread'] > 0): ?>
                <span class="badge"><?= $stats['unread'] ?></span>
            <?php endif; ?>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= $lang === 'bn' ? 'লগআউট' : 'Logout' ?></a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <!-- BACK BUTTON -->
            <a href="<?= isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin/') !== false ? $_SERVER['HTTP_REFERER'] : 'dashboard.php' ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> <?= $lang === 'bn' ? 'পেছনে' : 'Back' ?>
            </a>
            <h1><?= $lang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' ?></h1>
        </div>
        <div class="topbar-right">
            <a href="?lang=bn" class="lang-toggle">🇧🇩 বাংলা</a>
            <a href="?lang=en" class="lang-toggle">🌐 English</a>
            <div class="admin-info">
                <div class="admin-avatar">A</div>
                <span><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
            </div>
        </div>
    </div>

    <div class="content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.15)">
                    <i class="fas fa-code" style="color:#6366f1"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['projects'] ?></h3>
                    <p><?= $lang === 'bn' ? 'প্রজেক্ট' : 'Projects' ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(34,211,238,0.15)">
                    <i class="fas fa-star" style="color:#22d3ee"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['skills'] ?></h3>
                    <p><?= $lang === 'bn' ? 'দক্ষতা' : 'Skills' ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(34,197,94,0.15)">
                    <i class="fas fa-pen" style="color:#22c55e"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['blogs'] ?></h3>
                    <p><?= $lang === 'bn' ? 'ব্লগ' : 'Blogs' ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15)">
                    <i class="fas fa-quote-right" style="color:#f59e0b"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['testimonials'] ?></h3>
                    <p><?= $lang === 'bn' ? 'মতামত' : 'Testimonials' ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(239,68,68,0.15)">
                    <i class="fas fa-envelope" style="color:#ef4444"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['messages'] ?></h3>
                    <p><?= $lang === 'bn' ? 'বার্তা' : 'Messages' ?>
                        <?php if ($stats['unread'] > 0): ?>
                            <span style="color:#ef4444">(<?= $stats['unread'] ?> <?= $lang === 'bn' ? 'অপঠিত' : 'unread' ?>)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2>⚡ <?= $lang === 'bn' ? 'দ্রুত কাজ' : 'Quick Actions' ?></h2>
        </div>
        <div class="quick-actions">
            <a href="projects.php?action=add" class="quick-btn">
                <i class="fas fa-plus-circle"></i>
                <span><?= $lang === 'bn' ? 'প্রজেক্ট যোগ' : 'Add Project' ?></span>
            </a>
            <a href="skills.php?action=add" class="quick-btn">
                <i class="fas fa-star"></i>
                <span><?= $lang === 'bn' ? 'দক্ষতা যোগ' : 'Add Skill' ?></span>
            </a>
            <a href="blogs.php?action=add" class="quick-btn">
                <i class="fas fa-pen-alt"></i>
                <span><?= $lang === 'bn' ? 'ব্লগ লেখো' : 'Write Blog' ?></span>
            </a>
            <a href="testimonials.php?action=add" class="quick-btn">
                <i class="fas fa-quote-left"></i>
                <span><?= $lang === 'bn' ? 'মতামত যোগ' : 'Add Testimonial' ?></span>
            </a>
            <a href="messages.php" class="quick-btn">
                <i class="fas fa-inbox"></i>
                <span><?= $lang === 'bn' ? 'বার্তা দেখো' : 'View Messages' ?></span>
            </a>
            <a href="about.php" class="quick-btn">
                <i class="fas fa-user-edit"></i>
                <span><?= $lang === 'bn' ? 'আমার সম্পর্কে' : 'Edit About' ?></span>
            </a>
        </div>

        <div class="section-header">
            <h2>📬 <?= $lang === 'bn' ? 'সর্বশেষ বার্তা' : 'Latest Messages' ?></h2>
            <a href="messages.php" class="btn btn-primary btn-sm"><?= $lang === 'bn' ? 'সব দেখো' : 'View All' ?></a>
        </div>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th><?= $lang === 'bn' ? 'নাম' : 'Name' ?></th>
                        <th>Email</th>
                        <th><?= $lang === 'bn' ? 'বিষয়' : 'Subject' ?></th>
                        <th><?= $lang === 'bn' ? 'সময়' : 'Time' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="<?= $msg['is_read'] ? '' : 'unread-row' ?>">
                                <td>
                                    <?php if (!$msg['is_read']): ?>
                                        <span class="unread-dot"></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($msg['name']) ?>
                                </td>
                                <td><?= htmlspecialchars($msg['email']) ?></td>
                                <td><?= htmlspecialchars($msg['subject']) ?></td>
                                <td><?= date('d M, h:i A', strtotime($msg['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-inbox" style="font-size:2rem;color:#334155;display:block;margin-bottom:8px"></i>
                                    <?= $lang === 'bn' ? 'এখনো কোনো বার্তা আসেনি' : 'No messages yet' ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>