<?php
require_once '../../includes/init.php';
requireLogin();
requireAdmin();

$db = Database::getInstance()->getConnection();
$statusFilter = $_GET['status'] ?? '';

$where = '';
$params = [];
if ($statusFilter) {
    $where = 'WHERE t.status = ?';
    $params[] = $statusFilter;
}

$sql = "SELECT t.*, b.title AS book_title, u.full_name AS buyer_name, u.email AS buyer_email, o.full_name AS owner_name FROM transactions t JOIN books b ON t.book_id = b.id JOIN users u ON t.buyer_id = u.id JOIN users o ON b.user_id = o.id $where ORDER BY t.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// CSV headers
$filename = 'transactions_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$out = fopen('php://output', 'w');

// Write header row
fputcsv($out, ['id', 'request_id', 'book_id', 'book_title', 'buyer_id', 'buyer_name', 'buyer_email', 'amount', 'status', 'provider', 'provider_txn_id', 'proof_path', 'created_at']);

foreach ($transactions as $t) {
    fputcsv($out, [
        $t['id'],
        $t['request_id'],
        $t['book_id'],
        $t['book_title'],
        $t['buyer_id'],
        $t['buyer_name'],
        $t['buyer_email'],
        $t['amount'],
        $t['status'],
        $t['provider'],
        $t['provider_txn_id'],
        $t['proof_path'],
        $t['created_at']
    ]);
}

fclose($out);
exit;
