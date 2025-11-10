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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['txn_id'])) {
    $txnId = intval($_POST['txn_id']);
    $newStatus = $_POST['status'];
    $upd = $db->prepare('UPDATE transactions SET status = ? WHERE id = ?');
    $upd->execute([$newStatus, $txnId]);
    redirect('/views/dashboard/transactions.php');
}

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transactions - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container my-5">
        <h2>Transactions</h2>
        <div class="mb-3">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select w-auto">
                    <option value="">All</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="proof_uploaded" <?= $statusFilter === 'proof_uploaded' ? 'selected' : '' ?>>Proof Uploaded</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
                <button class="btn btn-primary">Filter</button>
                <a href="<?= site_url('views/dashboard/transactions.php?status=' . ($statusFilter ?: '')) ?>" class="btn btn-outline-secondary ms-2">Refresh</a>
                <a href="<?= site_url('views/dashboard/export_transactions.php?status=' . ($statusFilter ?: '')) ?>" class="btn btn-secondary ms-2">Export CSV</a>
            </form>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="alert alert-info">No transactions found.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book</th>
                        <th>Buyer</th>
                        <th>Owner</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Proof</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><?= htmlspecialchars($t['book_title']) ?></td>
                            <td><?= htmlspecialchars($t['buyer_name']) ?> (<?= htmlspecialchars($t['buyer_email']) ?>)</td>
                            <td><?= htmlspecialchars($t['owner_name']) ?></td>
                            <td><?= $t['amount'] ?></td>
                            <td><?= htmlspecialchars($t['status']) ?></td>
                            <td>
                                <?php if (!empty($t['proof_path'])): ?>
                                    <a href="<?= site_url($t['proof_path']) ?>" target="_blank">View</a>
                                <?php endif; ?>
                            </td>
                            <td><?= $t['created_at'] ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="txn_id" value="<?= $t['id'] ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="pending">pending</option>
                                        <option value="proof_uploaded">proof_uploaded</option>
                                        <option value="completed">completed</option>
                                        <option value="failed">failed</option>
                                    </select>
                                    <button class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>