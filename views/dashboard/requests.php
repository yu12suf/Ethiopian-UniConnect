<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$request = new Request();

$userId = $user->getCurrentUserId();
$receivedRequests = $request->getReceivedRequests($userId);
$sentRequests = $request->getSentRequests($userId);
$db = Database::getInstance()->getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = $_POST['request_id'];
    $status = $_POST['status'];
    $borrowDays = $_POST['borrow_days'] ?? null;
    $request->updateRequestStatus($requestId, $userId, $status, $borrowDays);
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
                                        <?php if (($req['exchange_type'] ?? '') === 'borrow' && !empty($req['requested_borrow_days'])): ?>
                                            <p class="mb-2"><strong>Requested Borrow Period:</strong> <?= $req['requested_borrow_days'] ?> days</p>
                                        <?php endif; ?>
                                        <p class="text-muted small"><i class="bi bi-clock"></i> <?= timeAgo($req['created_at']) ?></p>
                                        <?php
                                        // Show any uploaded proof for owners
                                        $txCheck = Database::getInstance()->getConnection()->prepare('SELECT * FROM transactions WHERE request_id = ? LIMIT 1');
                                        $txCheck->execute([$req['id']]);
                                        $txdata = $txCheck->fetch();
                                        if ($txdata && !empty($txdata['proof_path'])): ?>
                                            <p class="mt-2"><strong>Payment Proof:</strong> <a href="<?= site_url($txdata['proof_path']) ?>" target="_blank">View proof</a></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <?php
                                        $statusLabel = '';
                                        switch ($req['status']) {
                                            case 'pending':
                                                $statusLabel = 'Pending';
                                                break;
                                            case 'accepted':
                                                $statusLabel = 'Accepted';
                                                break;
                                            case 'rejected':
                                                $statusLabel = 'Rejected';
                                                break;
                                            case 'cancelled':
                                                $statusLabel = 'Cancelled';
                                                break;
                                            case 'completed':
                                                $statusLabel = 'Completed';
                                                break;
                                            default:
                                                $statusLabel = ucfirst($req['status']); // Fallback
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge status-<?= $req['status'] ?> d-block mb-2">
                                            <?= $statusLabel ?>
                                        </span>

                                        <?php if ($req['status'] === 'pending'): ?>
                                            <?php if ($req['exchange_type'] === 'borrow'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <input type="number" name="borrow_days" min="1" max="30" placeholder="Days" class="form-control form-control-sm d-inline w-auto" required>
                                                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php
                                        // If request is accepted and book is for sale, allow owner to mark transaction completed
                                        $bookExchange = $req['exchange_type'] ?? null;
                                        if ($req['status'] === 'accepted' && $bookExchange === 'buy'): ?>
                                            <?php
                                            // Use the transaction data (if any) fetched earlier in the left column
                                            $canComplete = isset($txdata) && !empty($txdata['proof_path']);
                                            ?>
                                            <form method="POST" class="d-inline mt-2">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-outline-primary btn-sm" <?= $canComplete ? '' : 'disabled' ?>>Mark as Paid / Complete Transaction</button>
                                            </form>
                                            <?php if (!$canComplete): ?>
                                                <div class="small text-muted mt-1">No payment proof uploaded yet.</div>
                                            <?php endif; ?>
                                        <?php elseif ($req['status'] === 'completed' && $bookExchange === 'buy' && isset($txdata['created_at'])): ?>
                                            <div class="alert alert-success mt-2">
                                                You sold this book on <?= formatDate($txdata['created_at']) ?>.
                                            </div>
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

                                        <?php
                                        $statusLabel = '';
                                        switch ($req['status']) {
                                            case 'pending':
                                                $statusLabel = 'Pending';
                                                break;
                                            case 'accepted':
                                                $statusLabel = 'Accepted';
                                                break;
                                            case 'rejected':
                                                $statusLabel = 'Rejected';
                                                break;
                                            case 'cancelled':
                                                $statusLabel = 'Cancelled';
                                                break;
                                            case 'completed':
                                                $statusLabel = 'Completed';
                                                break;
                                            default:
                                                $statusLabel = ucfirst($req['status']); // Fallback
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge status-<?= $req['status'] ?>">
                                            <?= $statusLabel ?>
                                        </span>

                                        <?php if ($req['status'] === 'accepted' || $req['status'] === 'completed'): ?>
                                            <div class="alert alert-success mt-2">
                                                <strong>Contact Info:</strong><br>
                                                Email: <?= htmlspecialchars($req['owner_email']) ?><br>
                                                <?php if ($req['owner_phone']): ?>
                                                    Phone: <?= htmlspecialchars($req['owner_phone']) ?>
                                                <?php endif; ?>
                                                <?php if (($req['exchange_type'] ?? '') === 'borrow' && !empty($req['borrow_deadline'])): ?>
                                                    <br><strong>Borrow Period:</strong> Until <?= date('M d, Y', strtotime($req['borrow_deadline'])) ?> (<?= ceil((strtotime($req['borrow_deadline']) - time()) / (60 * 60 * 24)) ?> days remaining)
                                                <?php endif; ?>
                                            </div>

                                            <?php if (($req['exchange_type'] ?? '') === 'buy'): ?>
                                                <?php
                                                $txStmt = $db->prepare('SELECT * FROM transactions WHERE request_id = ? LIMIT 1');
                                                $txStmt->execute([$req['id']]);
                                                $tx = $txStmt->fetch();
                                                ?>
                                                <div class="mt-2">
                                                    <?php if (!$tx): ?>
                                                        <div class="alert alert-warning">Waiting for owner to initialize the transaction.</div>
                                                    <?php else: ?>
                                                        <p><strong>Transaction status:</strong> <?= htmlspecialchars($tx['status']) ?></p>
                                                        <?php if (!empty($tx['proof_path'])): ?>
                                                            <p>Proof uploaded: <a href="<?= site_url($tx['proof_path']) ?>" target="_blank">View proof</a></p>
                                                        <?php endif; ?>

                                                        <?php if ($tx['status'] === 'pending'): ?>
                                                            <form method="POST" action="<?= site_url('views/dashboard/upload_proof.php') ?>" enctype="multipart/form-data" class="mt-2">
                                                                <input type="hidden" name="transaction_id" value="<?= $tx['id'] ?>">
                                                                <div class="input-group">
                                                                    <input type="file" name="proof" accept="image/*,application/pdf" class="form-control" required>
                                                                    <button class="btn btn-primary" type="submit">Upload Proof</button>
                                                                </div>
                                                            </form>
                                                        <?php elseif ($tx['status'] === 'proof_uploaded'): ?>
                                                            <div class="alert alert-info">Payment proof uploaded. Waiting for owner verification.</div>
                                                        <?php elseif ($tx['status'] === 'completed'): ?>
                                                            <div class="alert alert-success">
                                                                Payment completed. You bought this book on <?= formatDate($tx['created_at']) ?>.
                                                                You can now <a href="<?= site_url('views/public/book_detail.php?id=' . $req['book_id']) ?>">view/download the book</a>.
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
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