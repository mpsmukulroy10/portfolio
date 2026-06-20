<?php
define('DB_HOST', 'sql100.ezyro.com');
define('DB_USER', 'ezyro_41607235');
define('DB_PASS', '2b4cae4382');
define('DB_NAME', 'ezyro_41607235_portfolio');

define('SITE_URL', 'https://mpsmukulroy10.github.io/portfolio/');
define('SITE_NAME', 'My Portfolio');

// Email Configuration
define('MAIL_FROM', 'mroy151172@gmail.com');
define('MAIL_TO', 'mroy151172@gmail.com');
define('MAIL_PASSWORD', 'nhmi sjus rmhx vbwp'); // Gmail App Password

// Social Links
define('GITHUB_URL', 'https://github.com/yourusername');
define('LINKEDIN_URL', 'https://linkedin.com/in/yourusername');
define('FACEBOOK_URL', 'https://facebook.com/yourusername');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
