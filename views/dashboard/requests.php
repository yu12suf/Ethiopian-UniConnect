<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$request = new Request();

$userId = $user->getCurrentUserId();
$receivedRequests = $request->getReceivedRequests($userId);
$sentRequests = $request->getSentRequests($userId);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = $_POST['request_id'];
    $status = $_POST['status'];
    $request->updateRequestStatus($requestId, $userId, $status);
    redirect('/views/dashboard/requests.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container my-5">
        <h2 class="mb-4">Book Requests</h2>
        
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#received">
                    Received Requests <span class="badge bg-primary"><?= count($receivedRequests) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sent">
                    Sent Requests <span class="badge bg-info"><?= count($sentRequests) ?></span>
                </button>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Received Requests -->
            <div class="tab-pane fade show active" id="received">
                <?php if (empty($receivedRequests)): ?>
                    <div class="alert alert-info">No requests received yet.</div>
                <?php else: ?>
                    <?php foreach ($receivedRequests as $req): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title"><?= htmlspecialchars($req['book_title']) ?></h5>
                                        <p class="mb-2"><strong>From:</strong> <?= htmlspecialchars($req['requester_name']) ?></p>
                                        <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($req['requester_email']) ?></p>
                                        <?php if ($req['requester_phone']): ?>
                                            <p class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($req['requester_phone']) ?></p>
                                        <?php endif; ?>
                                        <p class="mb-2"><strong>Message:</strong> <?= nl2br(htmlspecialchars($req['message'])) ?></p>
                                        <p class="text-muted small"><i class="bi bi-clock"></i> <?= timeAgo($req['created_at']) ?></p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="status-badge status-<?= $req['status'] ?> d-block mb-2">
                                            <?= ucfirst($req['status']) ?>
                                        </span>
                                        
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="status" value="accepted">
                                                <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sent Requests -->
            <div class="tab-pane fade" id="sent">
                <?php if (empty($sentRequests)): ?>
                    <div class="alert alert-info">You haven't sent any requests yet.</div>
                <?php else: ?>
                    <?php foreach ($sentRequests as $req): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <?php if ($req['image_path']): ?>
                                            <img src="/<?= htmlspecialchars($req['image_path']) ?>" class="img-fluid rounded" alt="Book">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-10">
                                        <h5 class="card-title"><?= htmlspecialchars($req['book_title']) ?></h5>
                                        <p class="mb-2"><strong>To:</strong> <?= htmlspecialchars($req['owner_name']) ?></p>
                                        <p class="mb-2"><strong>Your Message:</strong> <?= nl2br(htmlspecialchars($req['message'])) ?></p>
                                        <p class="text-muted small"><i class="bi bi-clock"></i> <?= timeAgo($req['created_at']) ?></p>
                                        
                                        <span class="status-badge status-<?= $req['status'] ?>">
                                            <?= ucfirst($req['status']) ?>
                                        </span>
                                        
                                        <?php if ($req['status'] === 'accepted'): ?>
                                            <div class="alert alert-success mt-2">
                                                <strong>Contact Info:</strong><br>
                                                Email: <?= htmlspecialchars($req['owner_email']) ?><br>
                                                <?php if ($req['owner_phone']): ?>
                                                    Phone: <?= htmlspecialchars($req['owner_phone']) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
