<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$book = new Book();
$request = new Request();

$userId = $user->getCurrentUserId();
$userListings = $book->getUserListings($userId);
$receivedRequests = $request->getReceivedRequests($userId);
$sentRequests = $request->getSentRequests($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container my-5">
        <h2 class="mb-4">My Dashboard</h2>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card">
                    <h3><?= count($userListings) ?></h3>
                    <p>My Listings</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white" style="border-radius: 10px; padding: 20px;">
                    <h3><?= count($receivedRequests) ?></h3>
                    <p>Received Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white" style="border-radius: 10px; padding: 20px;">
                    <h3><?= count($sentRequests) ?></h3>
                    <p>Sent Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white" style="border-radius: 10px; padding: 20px;">
                    <h3><?= count(array_filter($userListings, fn($b) => $b['status'] == 'pending')) ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>
        </div>
        
        <!-- My Listings -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Book Listings</h5>
                    <a href="upload.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Upload New Book
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($userListings)): ?>
                    <p class="text-muted text-center py-4">You haven't uploaded any books yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userListings as $listing): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($listing['title']) ?></td>
                                        <td><?= htmlspecialchars($listing['author']) ?></td>
                                        <td><?= htmlspecialchars($listing['course']) ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($listing['exchange_type']) ?></span></td>
                                        <td>
                                            <span class="status-badge status-<?= $listing['status'] ?>">
                                                <?= ucfirst($listing['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_book.php?id=<?= $listing['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <a href="delete_book.php?id=<?= $listing['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Requests -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Requests</h5>
            </div>
            <div class="card-body">
                <?php if (empty($receivedRequests) && empty($sentRequests)): ?>
                    <p class="text-muted text-center py-4">No requests yet.</p>
                <?php else: ?>
                    <div class="mb-3">
                        <a href="requests.php" class="btn btn-primary">View All Requests</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
