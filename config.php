<?php
// Copy this to config.php and fill in your DB credentials
// Default XAMPP local settings:

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'classreserve');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/classreserve/');

// SMTP Mail Settings (Optional)
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'user@example.com');
define('SMTP_PASS', 'password');
define('SMTP_FROM', 'noreply@classreserve.local');
define('SMTP_FROM_NAME', 'ClassReserve Notification');
