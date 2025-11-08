<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sina_portfolio');
define('DB_USER', 'root');
define('DB_PASS', '1');

define('SITE_URL', 'http://localhost/project-sina');
define('ADMIN_URL', SITE_URL . '/admin');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>