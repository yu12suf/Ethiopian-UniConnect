<?php
require_once '../../includes/init.php';
requireLogin();
requireAdmin();

$admin = new Admin();
$stats = $admin->getStatistics();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <h2 class="mb-4"><i class="bi bi-graph-up"></i> System Reports</h2>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Books by Exchange Type</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['by_exchange_type'] as $type): ?>
                                    <tr>
                                        <td><?= ucfirst($type['exchange_type']) ?></td>
                                        <td><strong><?= $type['count'] ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow reports-table">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top Departments</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Books</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['top_departments'] as $dept): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($dept['department']) ?></td>
                                        <td><strong><?= $dept['count'] ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="mb-0">Overall Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <div class="stat-card-admin stat-card-users">
                            <h2><?= $stats['total_users'] ?></h2>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="stat-card-admin stat-card-books">
                            <h2><?= $stats['total_books'] ?></h2>
                            <p>Total Books</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="stat-card-admin stat-card-requests">
                            <h2><?= $stats['total_requests'] ?></h2>
                            <p>Total Requests</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="stat-card-admin stat-card-exchanges">
                            <h2><?= $stats['completed_exchanges'] ?></h2>
                            <p>Completed Exchanges</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary">Back to Admin Dashboard</a>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>