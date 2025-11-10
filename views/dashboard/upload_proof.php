<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$request = new Request();
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$transactionId = intval($_POST['transaction_id'] ?? 0);
if ($transactionId <= 0) {
    http_response_code(400);
    echo 'Invalid transaction id';
    exit;
}

// Fetch transaction and ensure current user is the buyer
$stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
$stmt->execute([$transactionId]);
$tx = $stmt->fetch();

if (!$tx) {
    http_response_code(404);
    echo 'Transaction not found';
    exit;
}

$currentUserId = $user->getCurrentUserId();
if ($tx['buyer_id'] != $currentUserId) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
    redirect('/views/dashboard/requests.php');
}

$allowed = ['image/png', 'image/jpeg', 'application/pdf'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['proof']['tmp_name']);
if (!in_array($mime, $allowed, true)) {
    redirect('/views/dashboard/requests.php');
}

$uploadsRoot = realpath(__DIR__ . '/../../uploads');
$targetDir = $uploadsRoot . DIRECTORY_SEPARATOR . 'transactions';
if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

$ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
$filename = 'proof_tx_' . $transactionId . '_' . time() . '.' . $ext;
$targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($_FILES['proof']['tmp_name'], $targetPath)) {
    error_log('Failed to move uploaded proof for transaction ' . $transactionId);
    redirect('/views/dashboard/requests.php');
}

// Save proof path (relative)
$relative = 'uploads/transactions/' . $filename;
$up = $db->prepare('UPDATE transactions SET proof_path = ?, status = ? WHERE id = ?');
$up->execute([$relative, 'proof_uploaded', $transactionId]);

// Notify owner that proof was uploaded
// Retrieve owner email
$q = $db->prepare('SELECT b.title, u.email AS owner_email, u.full_name AS owner_name, ru.email AS requester_email, ru.full_name AS requester_name FROM transactions t JOIN requests r ON t.request_id = r.id JOIN books b ON t.book_id = b.id JOIN users u ON b.user_id = u.id JOIN users ru ON r.requester_id = ru.id WHERE t.id = ? LIMIT 1');
$q->execute([$transactionId]);
$info = $q->fetch();
if ($info) {
    $to = $info['owner_email'];
    $subject = 'Payment proof uploaded for "' . $info['title'] . '"';
    $body = '<p>Hi ' . htmlspecialchars($info['owner_name']) . ',</p>' .
        '<p>' . htmlspecialchars($info['requester_name']) . ' has uploaded payment proof for the transaction for "' . htmlspecialchars($info['title']) . '".</p>' .
        '<p>View the proof and verify it on your Requests page: <a href="' . site_url('views/dashboard/requests.php') . '">Requests</a>.</p>' .
        '<p>Thank you,<br>UniConnect</p>';
    send_email($to, $subject, $body);
}

redirect('/views/dashboard/requests.php');
