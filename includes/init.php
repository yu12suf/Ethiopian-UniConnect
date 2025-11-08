<?php
/**
 * Initialization File
 * Loads configuration, starts session, and includes all classes
 */

// Load configuration first (before using constants)
require_once __DIR__ . '/../config/database.php';

// Start session with custom settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

session_start();

// Autoload classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// Create uploads directory if it doesn't exist
if (!file_exists(__DIR__ . '/../uploads/books')) {
    mkdir(__DIR__ . '/../uploads/books', 0777, true);
}

if (!file_exists(__DIR__ . '/../uploads/profiles')) {
    mkdir(__DIR__ . '/../uploads/profiles', 0777, true);
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to check if user is logged in
function requireLogin() {
    $user = new User();
    if (!$user->isLoggedIn()) {
        redirect('/views/auth/login.php');
    }
}

// Helper function to check if user is admin
function requireAdmin() {
    $user = new User();
    if (!$user->isAdmin()) {
        redirect('/index.php');
    }
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper function to format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Helper function to time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>
