<?php

/**
 * Initialization File
 * Loads configuration, starts session, and includes all classes
 */

// Load configuration first (before using constants)
require_once __DIR__ . '/../config/database.php';

// Ensure PHP uses the same timezone as the server/DB (prevents time-ago mismatches)
// Use Africa/Nairobi for Ethiopia (UTC+3). Change if your server uses a different timezone.
date_default_timezone_set('Africa/Nairobi');

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

// Provide a global $user instance for views/includes that expect it
// (e.g., navbar uses $user->isLoggedIn()). Instantiate here so it's always available.
try {
    $user = new User();
} catch (Exception $e) {
    // If User or Database fails to initialize, set $user to null and log the error
    error_log("Failed to create User instance: " . $e->getMessage());
    $user = null;
}

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
function redirect($url)
{
    // If full URL provided, redirect directly
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        header("Location: $url");
        exit();
    }

    // Build an absolute URL using current host and project base so redirects work
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

    // Determine project base path (strip everything from /views onward)
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $base = preg_replace('#/views.*$#', '', $scriptDir);
    if ($base === '/') {
        $base = '';
    }

    // Ensure single slash between base and url
    $location = $scheme . '://' . $host . $base . '/' . ltrim($url, '/');
    header("Location: $location");
    exit();
}

// Helper to build absolute URL for links within the app
function site_url($path = '')
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $base = preg_replace('#/views.*$#', '', $scriptDir);
    if ($base === '/') {
        $base = '';
    }

    $url = rtrim($scheme . '://' . $host . $base, '/') . '/' . ltrim($path, '/');
    return $url;
}

// Helper function to check if user is logged in
function requireLogin()
{
    $user = new User();
    if (!$user->isLoggedIn()) {
        redirect('/views/auth/login.php');
    }
}

// Helper function to check if user is admin
function requireAdmin()
{
    $user = new User();
    if (!$user->isAdmin()) {
        redirect('/index.php');
    }
}

// Helper function to sanitize input
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper function to format date
function formatDate($date)
{
    return date('F j, Y', strtotime($date));
}

// Helper function to time ago
function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    // If timestamp is in the future due to small clock/timezone differences,
    // treat the difference as positive so we display a human-friendly "ago"
    // value instead of always showing "just now".
    if ($diff < 0) {
        $diff = abs($diff);
    }

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

// Simple email helper. Tries PHP mail() and falls back to logging the message if mail isn't configured.
function send_email($to, $subject, $body, $from = null)
{
    $sent = false;
    $cfgFile = __DIR__ . '/../config/email.php';
    $config = file_exists($cfgFile) ? include $cfgFile : [];

    // If SMTP is requested and PHPMailer exists, use it
    $useSmtp = !empty($config['use_smtp']);
    $mailerClass = '\\PHPMailer\\PHPMailer\\PHPMailer';
    if ($useSmtp && class_exists($mailerClass)) {
        try {
            $mail = new $mailerClass(true);
            $mail->isSMTP();
            $mail->Host = $config['host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'] ?? '';
            $mail->Password = $config['password'] ?? '';
            $mail->SMTPSecure = $config['encryption'] ?? 'tls';
            $mail->Port = $config['port'] ?? 587;
            if (isset($config['smtp_verify']) && $config['smtp_verify'] === false) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            $fromEmail = $config['from_email'] ?? ($from ?? 'no-reply@localhost');
            $fromName = $config['from_name'] ?? 'UniConnect';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $sent = $mail->send();
        } catch (Exception $ex) {
            $sent = false;
            error_log('PHPMailer send failed: ' . $ex->getMessage());
        }
    } else {
        // Fallback to PHP mail()
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        if ($from) {
            $headers .= 'From: ' . $from . "\r\n";
        }
        try {
            if (function_exists('mail')) {
                $sent = @mail($to, $subject, $body, $headers);
            }
        } catch (Exception $ex) {
            $sent = false;
        }
    }

    // Log the email attempt for auditing and fallback
    $logEntry = sprintf("[%s] To: %s | Subject: %s | Sent: %s\n", date('c'), $to, $subject, $sent ? 'yes' : 'no');
    $logEntry .= "Body:\n" . $body . "\n---------------------\n";
    file_put_contents(__DIR__ . '/../logs/email.log', $logEntry, FILE_APPEND | LOCK_EX);

    return $sent;
}
