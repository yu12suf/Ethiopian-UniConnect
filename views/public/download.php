<?php
require_once '../../includes/init.php';

// Secure file download/view endpoint for book attachments
// Usage: download.php?book_id=123&action=view|download

$bookId = intval($_GET['book_id'] ?? 0);
action:
$action = $_GET['action'] ?? 'download'; // default to download

if ($bookId <= 0) {
    http_response_code(400);
    echo 'Invalid book id';
    exit;
}

// Fetch book
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT id, title, user_id, status, file_path, exchange_type FROM books WHERE id = ?');
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book || $book['status'] !== 'approved') {
    http_response_code(404);
    echo 'Book not found or not available';
    exit;
}

if (empty($book['file_path'])) {
    http_response_code(404);
    echo 'No file attached to this book';
    exit;
}

// Enforce access policy by exchange type:
// - donate: public (no login required)
// - buy: only owner/admin or requester with completed transaction
// - borrow: only owner/admin or requester with accepted request and within deadline

$exchange = $book['exchange_type'] ?? 'donate';

// For donate files, allow public access (no login required)
if ($exchange === 'donate') {
    $allowed = true;
} elseif ($exchange === 'buy') {
    // For buy: only owner/admin or requester with completed transaction
    $user = new User();
    if (!$user->isLoggedIn()) {
        redirect('/views/auth/login.php');
    }

    $currentUserId = $user->getCurrentUserId();
    $isOwner = ($currentUserId == $book['user_id']);
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

    $allowed = false;
    if ($isOwner || $isAdmin) {
        $allowed = true;
    } else {
        // Check if the user has a verified payment proof for this book
        $proofStmt = $db->prepare("
            SELECT pp.id FROM payment_proofs pp
            JOIN requests r ON pp.request_id = r.id
            WHERE r.book_id = ? AND r.requester_id = ? AND pp.verified = 1
            LIMIT 1
        ");
        $proofStmt->execute([$bookId, $currentUserId]);
        if ($proofStmt->fetch()) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo '<h3>Access denied</h3>';
        echo '<p>This book is for sale. Complete the payment to access the file.';
        echo ' <a href="' . site_url('views/public/pay.php?book_id=' . $bookId) . '">Pay Now</a>.</p>';
        exit;
    }
} elseif ($exchange === 'borrow') {
    // For borrow: only owner/admin or requester with accepted request and within deadline
    $user = new User();
    if (!$user->isLoggedIn()) {
        redirect('/views/auth/login.php');
    }

    $currentUserId = $user->getCurrentUserId();
    $isOwner = ($currentUserId == $book['user_id']);
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

    $allowed = false;
    if ($isOwner || $isAdmin) {
        $allowed = true;
    } else {
        // Check if the user has an accepted request for this book (includes deadline check)
        $req = new Request();
        if ($req->isRequestAccepted($currentUserId, $bookId)) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo '<h3>Access denied</h3>';
        echo '<p>This book is available for borrowing. Send a request to the owner to access the file.';
        echo ' <a href="' . site_url('views/public/book_detail.php?id=' . $bookId) . '">Send Request</a>.</p>';
        exit;
    }
} else {
    // Unknown exchange type, deny access
    http_response_code(403);
    echo '<h3>Access denied</h3>';
    echo '<p>This file is only available after the transaction/request is approved by the owner.';
    echo ' To request access, please <a href="' . site_url('views/public/book_detail.php?id=' . $bookId) . '">send a request for this book</a>.</p>';
    exit;
}

// Build absolute path to file and validate
$relative = ltrim($book['file_path'], '/\\'); // normalize
$projectRoot = realpath(__DIR__ . '/../../');
$filePath = realpath($projectRoot . DIRECTORY_SEPARATOR . $relative);

$uploadsDir = realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'books');

if (!$filePath || strpos($filePath, $uploadsDir) !== 0 || !file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Log the view/download action to the downloads table (best-effort)
try {
    // Determine current user id if any
    $logUserId = null;
    if (!isset($user)) {
        $user = new User();
    }
    if ($user->isLoggedIn()) {
        $logUserId = $user->getCurrentUserId();
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $logStmt = $db->prepare('INSERT INTO downloads (user_id, book_id, action, file_path, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    $logStmt->execute([$logUserId, $bookId, $action === 'view' ? 'view' : 'download', $relative, $ip, $ua]);
} catch (Exception $ex) {
    // Don't break file serving if logging fails; record to error log for later inspection
    error_log('Failed to log download/view: ' . $ex->getMessage());
}

// Optionally: check additional permissions (e.g., only owner or admin). Currently we allow any logged-in user.
// Serve file with correct headers
$mime = mime_content_type($filePath) ?: 'application/octet-stream';
$basename = basename($filePath);
$filesize = filesize($filePath);

if ($action === 'view' && strpos($mime, 'pdf') !== false) {
    // Inline display for PDFs
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Content-Disposition: inline; filename="' . $basename . '"');
    readfile($filePath);
    exit;
}

// For other types or explicit download, force attachment
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $basename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: must-revalidate');
header('Pragma: public');
readfile($filePath);
exit;
