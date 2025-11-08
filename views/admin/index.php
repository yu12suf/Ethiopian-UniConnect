<?php
require_once '../../includes/init.php';
requireLogin();
requireAdmin();

$admin = new Admin();
$book = new Book();

$stats = $admin->getStatistics();
$pendingBooks = $book->getPendingBooks();

// Handle book approval/blocking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = $_POST['book_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $admin->approveBook($bookId);
    } elseif ($action === 'block') {
        $admin->blockBook($bookId);
    }
    
    redirect('/views/admin/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container my-5">
        <h2 class="mb-4"><i class="bi bi-shield-check"></i> Admin Dashboard</h2>
        
        <!-- Statistics -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card">
                    <h3><?= $stats['total_users'] ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white" style="border-radius: 10px; padding: 20px;">
                    <h3><?= $stats['total_books'] ?></h3>
                    <p>Total Books</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white" style="border-radius: 10px; padding: 20px;">
                    <h3><?= $stats['pending_approvals'] ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white" style="border-radius: 10px; padding: 20px;">
                    <h3><?= $stats['completed_exchanges'] ?></h3>
                    <p>Completed Exchanges</p>
                </div>
            </div>
        </div>
        
        <!-- Pending Approvals -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Pending Book Approvals</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pendingBooks)): ?>
                    <p class="text-center text-muted py-4">No pending approvals</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Owner</th>
                                    <th>Department</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingBooks as $pendingBook): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pendingBook['title']) ?></td>
                                        <td><?= htmlspecialchars($pendingBook['author']) ?></td>
                                        <td><?= htmlspecialchars($pendingBook['owner_name']) ?></td>
                                        <td><?= htmlspecialchars($pendingBook['department']) ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($pendingBook['exchange_type']) ?></span></td>
                                        <td><?= formatDate($pendingBook['created_at']) ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="book_id" value="<?= $pendingBook['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="book_id" value="<?= $pendingBook['id'] ?>">
                                                <input type="hidden" name="action" value="block">
                                                <button type="submit" class="btn btn-sm btn-danger">Block</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5><i class="bi bi-people"></i> User Management</h5>
                        <p class="text-muted">Manage user accounts and permissions</p>
                        <a href="users.php" class="btn btn-primary">Manage Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5><i class="bi bi-graph-up"></i> Reports</h5>
                        <p class="text-muted">View detailed statistics and reports</p>
                        <a href="reports.php" class="btn btn-primary">View Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
