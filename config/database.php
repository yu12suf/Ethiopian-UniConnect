<?php
/**
 * Database Configuration File
 * Contains database connection settings for MySQL on port 3307
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'uniconnect');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('SITE_NAME', 'UniConnect');
define('SITE_URL', 'http://localhost:5000');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Session settings
define('SESSION_LIFETIME', 86400); // 24 hours
define('COOKIE_LIFETIME', 2592000); // 30 days for "Remember Me"

// Admin email
define('ADMIN_EMAIL', 'admin@uniconnect.edu.et');
?>
