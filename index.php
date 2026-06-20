<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Fetch all data
$about        = $pdo->query("SELECT * FROM about WHERE id = 1")->fetch();
$skills       = $pdo->query("SELECT * FROM skills ORDER BY category, level DESC")->fetchAll();
$projects     = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 6")->fetchAll();
$blogs        = $pdo->query("SELECT * FROM blogs WHERE status='published' ORDER BY created_at DESC LIMIT 3")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM testimonials")->fetchAll();

$grouped_skills = [];
foreach ($skills as $skill) {
    $grouped_skills[$skill['category']][] = $skill;
}

// Prepare taglines for JavaScript
$default_taglines = [
    '🚀 Full Stack Developer',
    '💻 Web Designer',
    '📱 Mobile App Developer',
    '🎨 UI/UX Enthusiast',
    '🌟 Problem Solver'
];

$tagline_text = $about['tagline'] ?? 'Full Stack Developer,Web Designer,Mobile App Developer,UI/UX Enthusiast,Problem Solver';
$tagline_parts = array_map('trim', explode(',', $tagline_text));

if (empty($tagline_parts) || (count($tagline_parts) === 1 && empty($tagline_parts[0]))) {
    $tagline_parts = $default_taglines;
}

// Escape for JavaScript
$taglines_json = json_encode($tagline_parts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($about['name'] ?? SITE_NAME) ?> | Portfolio</title>
    
    <!-- FOUC রোধ -->
    <script>
        if(localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-init');
        }
    </script>
    <style>
        html.light-init body {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        
        /* ===== SCROLL REVEAL ===== */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .scroll-reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* ===== FADE IN PARAGRAPH ===== */
        .fade-in-paragraph {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }
        .fade-in-paragraph.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .fade-in-paragraph:nth-child(2) { transition-delay: 0.1s; }
        .fade-in-paragraph:nth-child(3) { transition-delay: 0.2s; }
        .fade-in-paragraph:nth-child(4) { transition-delay: 0.3s; }
        .fade-in-paragraph:nth-child(5) { transition-delay: 0.4s; }
        
        /* ===== TAGLINE TYPING EFFECT ===== */
        .tagline-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            min-height: 50px;
            padding: 8px 0;
            width: 100%;
            max-width: 700px;
        }
        
        .tagline-label {
            font-size: 1.1rem;
            color: var(--text-muted, #94a3b8);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .tagline-label i {
            color: var(--primary, #6366f1);
        }
        
        .tagline-typing-container {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 200px;
            height: 50px;
            position: relative;
        }
        
        #typed-tagline {
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary, #6366f1), var(--secondary, #22d3ee));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline;
            padding: 0 4px;
            letter-spacing: 0.5px;
            line-height: 1.4;
            min-height: 1.4em;
        }
        
        .typing-cursor {
            display: inline-block;
            width: 3px;
            height: 1.3em;
            background: var(--primary, #6366f1);
            margin-left: 2px;
            animation: blink 1s step-end infinite;
            vertical-align: text-bottom;
            flex-shrink: 0;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .tagline-wrapper {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
                max-width: 100%;
            }
            .tagline-typing-container {
                height: 40px;
                min-width: 100%;
            }
            #typed-tagline {
                font-size: 1.1rem;
            }
            .tagline-label {
                font-size: 0.95rem;
            }
            .typing-cursor {
                height: 1.1em;
            }
        }
        
        @media (max-width: 480px) {
            #typed-tagline {
                font-size: 0.95rem;
            }
            .tagline-label {
                font-size: 0.85rem;
            }
            .tagline-typing-container {
                height: 35px;
            }
            .typing-cursor {
                height: 1em;
            }
        }
    </style>
    
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar">
    <div class="nav-logo">💼 <?= htmlspecialchars($about['name'] ?? 'Portfolio') ?></div>
    <ul class="nav-links">
        <li><a href="#about" data-i18n="nav_about">About</a></li>
        <li><a href="#skills" data-i18n="nav_skills">Skills</a></li>
        <li><a href="#projects" data-i18n="nav_projects">Projects</a></li>
        <li><a href="#blog" data-i18n="nav_blog">Blog</a></li>
        <li><a href="#testimonials" data-i18n="nav_testimonials">Testimonials</a></li>
        <li><a href="#contact" data-i18n="nav_contact">Contact</a></li>
    </ul>
    <div class="nav-controls">
        <button id="langToggle" class="lang-toggle" aria-label="Toggle Language">🌐 EN</button>
        <button id="themeToggle" class="theme-toggle" aria-label="Toggle Theme">🌙</button>
    </div>
    <div class="hamburger"><i class="fas fa-bars"></i></div>
</nav>

<!-- ===== HERO / ABOUT ===== -->
<section id="about" class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <p class="hero-greeting scroll-reveal" data-i18n="hero_greeting">👋 Hello, I'm</p>
            <h1 class="scroll-reveal" style="transition-delay:0.1s;">
                <?= htmlspecialchars($about['name'] ?? 'Your Name') ?>
            </h1>
            
            <!-- ===== TAGLINE TYPING EFFECT ===== -->
            <div class="tagline-wrapper scroll-reveal" style="transition-delay:0.2s;">
                <span class="tagline-label">
                    <i class="fas fa-bolt"></i>
                    <span data-i18n="i_am">I am</span>
                </span>
                <div class="tagline-typing-container">
                    <span id="typed-tagline"></span>
                    <span class="typing-cursor"></span>
                </div>
            </div>
            
            <!-- ===== BIO ===== -->
            <div class="bio-wrapper" style="max-width:500px; margin-top: 12px;">
                <?php 
                $bio_lines = explode("\n", $about['bio'] ?? '');
                foreach ($bio_lines as $index => $line):
                    if (trim($line) === '') continue;
                ?>
                    <p class="bio fade-in-paragraph scroll-reveal" style="transition-delay:<?= $index * 0.15 + 0.3 ?>s; margin-bottom:10px;">
                        <?= htmlspecialchars(trim($line)) ?>
                    </p>
                <?php endforeach; ?>
            </div>
            
            <!-- ===== HERO BUTTONS ===== -->
            <div class="hero-btns scroll-reveal" style="transition-delay:0.6s;">
                <a href="<?= $about['cv_url'] ?? '#' ?>" class="btn btn-primary" data-i18n-btn="download_cv" <?= empty($about['cv_url']) ? 'style="opacity:0.5;pointer-events:none;"' : '' ?>>
                    <i class="fas fa-download"></i> <span class="btn-text">Download CV</span>
                </a>
                <a href="#contact" class="btn btn-outline" data-i18n-btn="hire_me">
                    <i class="fas fa-envelope"></i> <span class="btn-text">Hire Me</span>
                </a>
            </div>
            
            <!-- ===== SOCIAL ICONS ===== -->
            <div class="hero-social scroll-reveal" style="transition-delay:0.7s; margin-top:24px; display:flex; gap:16px;">
                <?php if(!empty($about['github_url'])): ?>
                    <a href="<?= htmlspecialchars($about['github_url']) ?>" target="_blank" aria-label="GitHub">
                        <i class="fab fa-github" style="font-size:1.4rem;"></i>
                    </a>
                <?php endif; ?>
                <?php if(!empty($about['linkedin_url'])): ?>
                    <a href="<?= htmlspecialchars($about['linkedin_url']) ?>" target="_blank" aria-label="LinkedIn">
                        <i class="fab fa-linkedin" style="font-size:1.4rem;"></i>
                    </a>
                <?php endif; ?>
                <?php if(!empty($about['facebook_url'])): ?>
                    <a href="<?= htmlspecialchars($about['facebook_url']) ?>" target="_blank" aria-label="Facebook">
                        <i class="fab fa-facebook" style="font-size:1.4rem;"></i>
                    </a>
                <?php endif; ?>
                <?php if(!empty($about['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($about['email']) ?>" aria-label="Email">
                        <i class="fas fa-envelope" style="font-size:1.4rem;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ===== HERO IMAGE ===== -->
        <div class="hero-image scroll-reveal" style="transition-delay:0.4s;">
            <?php if (!empty($about['photo'])): ?>
                <img src="<?= SITE_URL ?>/assets/images/<?= htmlspecialchars($about['photo']) ?>" alt="Profile Photo of <?= htmlspecialchars($about['name'] ?? '') ?>">
            <?php else: ?>
                <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== SKILLS ===== -->
<section id="skills" class="section skills-section">
    <div class="container">
        <h2 class="section-title scroll-reveal"><span data-i18n="my_skills">My Skills</span></h2>
        <p class="section-sub scroll-reveal" data-i18n="skills_sub">What I know &amp; use</p>

        <?php if (!empty($grouped_skills)): ?>
            <?php foreach ($grouped_skills as $category => $cat_skills): ?>
                <h3 class="skill-category scroll-reveal"><?= htmlspecialchars($category) ?></h3>
                <div class="skills-grid">
                    <?php foreach ($cat_skills as $skill): ?>
                        <div class="skill-card scroll-reveal">
                            <div class="skill-header">
                                <span class="skill-icon"><?= htmlspecialchars($skill['icon'] ?? '⚡') ?></span>
                                <span class="skill-name"><?= htmlspecialchars($skill['name']) ?></span>
                                <span class="skill-percent"><?= $skill['level'] ?>%</span>
                            </div>
                            <div class="skill-bar">
                                <div class="skill-fill" style="width:<?= $skill['level'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-msg" data-i18n="no_skills">Add skills from Admin Panel.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ===== PROJECTS ===== -->
<section id="projects" class="section projects-section">
    <div class="container">
        <h2 class="section-title scroll-reveal"><span data-i18n="my_projects">My Projects</span></h2>
        <p class="section-sub scroll-reveal" data-i18n="projects_sub">My work samples</p>
        <div class="projects-grid">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                    <div class="project-card scroll-reveal">
                        <?php if (!empty($project['image'])): ?>
                            <img src="<?= SITE_URL ?>/assets/images/<?= htmlspecialchars($project['image']) ?>" alt="<?= htmlspecialchars($project['title']) ?>">
                        <?php else: ?>
                            <div class="project-img-placeholder"><i class="fas fa-code"></i></div>
                        <?php endif; ?>
                        <div class="project-info">
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                            <p><?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...</p>
                            <div class="tech-tags">
                                <?php foreach (explode(',', $project['tech_stack']) as $tech): ?>
                                    <span><?= htmlspecialchars(trim($tech)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="project-links">
                                <?php if (!empty($project['live_url'])): ?>
                                    <a href="<?= $project['live_url'] ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> <span data-i18n="live">Live</span>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($project['github_url'])): ?>
                                    <a href="<?= $project['github_url'] ?>" target="_blank">
                                        <i class="fab fa-github"></i> <span data-i18n="github">GitHub</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-msg" data-i18n="no_projects">Add projects from Admin Panel.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== BLOG ===== -->
<section id="blog" class="section blog-section">
    <div class="container">
        <h2 class="section-title scroll-reveal"><span data-i18n="my_blog">My Blog</span></h2>
        <p class="section-sub scroll-reveal" data-i18n="blog_sub">My writings</p>
        <div class="blog-grid">
            <?php if (!empty($blogs)): ?>
                <?php foreach ($blogs as $blog): ?>
                    <div class="blog-card scroll-reveal">
                        <?php if (!empty($blog['image'])): ?>
                            <img src="<?= SITE_URL ?>/assets/images/<?= htmlspecialchars($blog['image']) ?>" alt="<?= htmlspecialchars($blog['title']) ?>">
                        <?php else: ?>
                            <div class="blog-img-placeholder"><i class="fas fa-pen"></i></div>
                        <?php endif; ?>
                        <div class="blog-info">
                            <span class="blog-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('d M Y', strtotime($blog['created_at'])) ?>
                            </span>
                            <h3><?= htmlspecialchars($blog['title']) ?></h3>
                            <p><?= htmlspecialchars(substr(strip_tags($blog['content']), 0, 100)) ?>...</p>
                            <a href="#" class="read-more" data-i18n="read_more">Read More →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-msg" data-i18n="no_blogs">Add blogs from Admin Panel.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section id="testimonials" class="section testimonials-section">
    <div class="container">
        <h2 class="section-title scroll-reveal"><span data-i18n="what_they_say">What They Say</span></h2>
        <p class="section-sub scroll-reveal" data-i18n="testimonials_sub">What people say about me</p>
        <div class="testimonials-grid">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $t): ?>
                    <div class="testimonial-card scroll-reveal">
                        <p class="testimonial-msg">"<?= htmlspecialchars($t['message']) ?>"</p>
                        <div class="testimonial-author">
                            <?php if (!empty($t['photo'])): ?>
                                <img src="<?= SITE_URL ?>/assets/images/<?= htmlspecialchars($t['photo']) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
                            <?php else: ?>
                                <div class="author-avatar"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($t['name']) ?></strong>
                                <span><?= htmlspecialchars($t['role']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-msg" data-i18n="no_testimonials">Add testimonials from Admin Panel.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== CONTACT ===== -->
<section id="contact" class="section contact-section">
    <div class="container">
        <h2 class="section-title scroll-reveal"><span data-i18n="contact_me">Contact Me</span></h2>
        <p class="section-sub scroll-reveal" data-i18n="contact_sub">Get in touch</p>
        <div class="contact-wrapper">
            <div class="contact-info">
                <div class="info-item scroll-reveal">
                    <i class="fas fa-envelope"></i>
                    <span><?= htmlspecialchars($about['email'] ?? MAIL_TO) ?></span>
                </div>
                <?php if (!empty($about['github_url'])): ?>
                <div class="info-item scroll-reveal" style="transition-delay:0.1s;">
                    <i class="fab fa-github"></i>
                    <a href="<?= htmlspecialchars($about['github_url']) ?>" target="_blank" style="color:var(--text-muted);">
                        <?= str_replace(['https://github.com/', 'http://github.com/'], '', $about['github_url']) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($about['linkedin_url'])): ?>
                <div class="info-item scroll-reveal" style="transition-delay:0.2s;">
                    <i class="fab fa-linkedin"></i>
                    <a href="<?= htmlspecialchars($about['linkedin_url']) ?>" target="_blank" style="color:var(--text-muted);">
                        <?= str_replace(['https://linkedin.com/in/', 'https://www.linkedin.com/in/'], '', $about['linkedin_url']) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($about['facebook_url'])): ?>
                <div class="info-item scroll-reveal" style="transition-delay:0.3s;">
                    <i class="fab fa-facebook"></i>
                    <a href="<?= htmlspecialchars($about['facebook_url']) ?>" target="_blank" style="color:var(--text-muted);">
                        <?= str_replace(['https://facebook.com/', 'https://www.facebook.com/'], '', $about['facebook_url']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- ===== CONTACT FORM ===== -->
            <form class="contact-form" id="contactForm" method="POST" action="<?= SITE_URL ?>/includes/contact.php" novalidate>
                <div class="form-group scroll-reveal">
                    <input type="text" name="name" id="contactName" data-i18n-ph="name_ph" placeholder="Your Name" required>
                </div>
                <div class="form-group scroll-reveal" style="transition-delay:0.1s;">
                    <input type="email" name="email" id="contactEmail" data-i18n-ph="email_ph" placeholder="Your Email" required>
                </div>
                <div class="form-group scroll-reveal" style="transition-delay:0.2s;">
                    <input type="text" name="subject" id="contactSubject" data-i18n-ph="subject_ph" placeholder="Subject" required>
                </div>
                <div class="form-group scroll-reveal" style="transition-delay:0.3s;">
                    <textarea name="message" id="contactMessage" rows="5" data-i18n-ph="message_ph" placeholder="Your Message" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary scroll-reveal" style="transition-delay:0.4s;" id="submitBtn" data-i18n-btn="send_message">
                    <i class="fas fa-paper-plane"></i> <span class="btn-text">Send Message</span>
                </button>
                <div id="formMsg" class="form-msg" style="display:none;"></div>
            </form>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <p>Made with ❤️ by <?= htmlspecialchars($about['name'] ?? 'Portfolio') ?></p>
    <div class="social-links">
        <?php if(!empty($about['github_url'])): ?>
            <a href="<?= htmlspecialchars($about['github_url']) ?>" target="_blank" aria-label="GitHub"><i class="fab fa-github"></i></a>
        <?php endif; ?>
        <?php if(!empty($about['linkedin_url'])): ?>
            <a href="<?= htmlspecialchars($about['linkedin_url']) ?>" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
        <?php endif; ?>
        <?php if(!empty($about['facebook_url'])): ?>
            <a href="<?= htmlspecialchars($about['facebook_url']) ?>" target="_blank" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
        <?php endif; ?>
        <?php if(!empty($about['email'])): ?>
            <a href="mailto:<?= htmlspecialchars($about['email']) ?>" aria-label="Email"><i class="fas fa-envelope"></i></a>
        <?php endif; ?>
    </div>
</footer>

<!-- ===== JAVASCRIPT ===== -->
<script>
    // PHP থেকে JS এ path পাঠানো
    const CONTACT_URL = "<?= SITE_URL ?>/includes/contact.php";
    const SITE_BASE   = "<?= SITE_URL ?>";
    
    // Taglines for typing effect
    const taglines = <?= $taglines_json ?>;
</script>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

<!-- ===== TAGLINE TYPING EFFECT ===== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Index page loaded');
    
    // ===== TYPING EFFECT =====
    let ti = 0, ci = 0, del = false;
    const typed = document.getElementById('typed-tagline');
    
    function typeEffect() {
        if (!typed) return;
        
        const cur = taglines[ti];
        if (!cur) return;
        
        if (!del && ci <= cur.length) {
            typed.textContent = cur.substring(0, ci);
            ci++;
            setTimeout(typeEffect, 90);
        } else if (!del && ci > cur.length) {
            del = true;
            setTimeout(typeEffect, 1600);
        } else if (del && ci >= 0) {
            typed.textContent = cur.substring(0, ci);
            ci--;
            setTimeout(typeEffect, 50);
        } else if (del && ci < 0) {
            del = false;
            ci = 0;
            ti = (ti + 1) % taglines.length;
            setTimeout(typeEffect, 300);
        }
    }
    
    // Start typing effect after a small delay
    setTimeout(typeEffect, 500);
    
    // ===== SCROLL REVEAL OBSERVER =====
    const revealElements = document.querySelectorAll('.scroll-reveal');
    
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    });
    
    revealElements.forEach(el => {
        if (!el.style.opacity || el.style.opacity === '') {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.7s cubic-bezier(0.4, 0, 0.2, 1)';
        }
        revealObserver.observe(el);
    });
    
    // ===== BIO PARAGRAPHS =====
    document.querySelectorAll('.fade-in-paragraph').forEach(el => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    el.classList.add('visible');
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.2 });
        observer.observe(el);
    });
    
    // ===== CONTACT FORM FALLBACK =====
    const form = document.getElementById('contactForm');
    if (form && typeof initForm !== 'function') {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const msg = document.getElementById('formMsg');
            const btn = document.getElementById('submitBtn');
            const btnTxt = btn?.querySelector('.btn-text');
            
            btn.disabled = true;
            if (btnTxt) btnTxt.textContent = 'Sending...';
            
            try {
                const response = await fetch(CONTACT_URL, {
                    method: 'POST',
                    body: new FormData(form)
                });
                const data = await response.json();
                
                if (msg) {
                    msg.textContent = data.message || (data.success ? '✅ Sent!' : '❌ Error!');
                    msg.className = 'form-msg ' + (data.success ? 'success' : 'error');
                    msg.style.display = 'block';
                }
                
                if (data.success) form.reset();
            } catch(err) {
                if (msg) {
                    msg.textContent = '❌ Network error. Please try again.';
                    msg.className = 'form-msg error';
                    msg.style.display = 'block';
                }
            }
            
            btn.disabled = false;
            if (btnTxt) btnTxt.textContent = 'Send Message';
        });
    }
});
</script>

</body>
</html>