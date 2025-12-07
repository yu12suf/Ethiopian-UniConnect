<?php
require_once '../../includes/init.php';

$user = new User();
$book = new Book();
$request = new Request();

$bookId = $_GET['id'] ?? 0;

// Get payment accounts for this book
$paymentAccounts = $book->getPaymentAccounts($bookId);
$bookData = $book->getBookById($bookId);

if (!$bookData || $bookData['status'] !== 'approved') {
    redirect('/index.php');
}

$errors = [];
$success = '';

// Handle payment completion
if (isset($_GET['payment']) && $_GET['payment'] === 'completed') {
    if (isset($_GET['demo']) && $_GET['demo'] === 'true') {
        // Demo payment completion - simulate webhook
        $txRef = $_GET['tx_ref'] ?? null;
        if ($txRef) {
            // Find transaction by tx_ref
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM transactions WHERE provider_txn_id = ? LIMIT 1');
            $stmt->execute([$txRef]);
            $txn = $stmt->fetch();

            if ($txn) {
                // Update transaction status
                $upd = $db->prepare('UPDATE transactions SET status = ?, provider = ? WHERE id = ?');
                $upd->execute(['completed', 'chapa', $txn['id']]);

                // Mark request as completed if exists
                if (!empty($txn['request_id'])) {
                    $r = $db->prepare('UPDATE requests SET status = ? WHERE id = ?');
                    $r->execute(['completed', $txn['request_id']]);
                }

                $success = 'Payment completed successfully! You can now access the book files.';
            }
        }
    } else {
        $success = 'Payment completed successfully! You can now access the book files.';
    }
}

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user->isLoggedIn()) {
    $message = sanitize($_POST['message']);
    $borrowDays = null;
    $paymentProof = null;

    if ($bookData['exchange_type'] === 'borrow' && isset($_POST['borrow_days'])) {
        $borrowDays = intval($_POST['borrow_days']);
        if ($borrowDays < 1 || $borrowDays > 30) {
            $errors[] = 'Borrow days must be between 1 and 30';
        }
    }

    // Handle payment proof upload for 'buy' exchange type
    if ($bookData['exchange_type'] === 'buy') {
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Payment proof screenshot is required';
        } else {
            // Upload payment proof
            $proofFile = $_FILES['payment_proof'];
            $uploadDir = __DIR__ . '/../../uploads/transactions/';

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = uniqid('proof_') . '.' . strtolower(pathinfo($proofFile['name'], PATHINFO_EXTENSION));
            $uploadPath = $uploadDir . $filename;

            if (move_uploaded_file($proofFile['tmp_name'], $uploadPath)) {
                $paymentProof = 'uploads/transactions/' . $filename;
            } else {
                $errors[] = 'Failed to upload payment proof';
            }
        }
    }

    if (empty($errors)) {
        $result = $request->createRequest($user->getCurrentUserId(), $bookId, $message, $borrowDays, $paymentProof);

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($bookData['title']) ?> - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= site_url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($bookData['title']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-4">
                <?php if ($bookData['image_path']): ?>
                    <img src="<?= site_url($bookData['image_path']) ?>" class="img-fluid rounded shadow" alt="Book Image">
                <?php else: ?>
                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="height: 400px;">
                        <i class="bi bi-book" style="font-size: 6rem;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <h2 class="fw-bold"><?= htmlspecialchars($bookData['title']) ?></h2>
                <p class="text-muted lead">by <?= htmlspecialchars($bookData['author']) ?></p>

                <div class="mb-3">
                    <span class="badge bg-info me-2"><?= ucfirst($bookData['exchange_type']) ?></span>
                    <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $bookData['condition_type'])) ?></span>
                    <span class="badge bg-success"><?= ucfirst($bookData['availability']) ?></span>
                </div>

                <?php if ($bookData['exchange_type'] == 'buy' && $bookData['price']): ?>
                    <h3 class="text-success mb-3"><?= number_format($bookData['price'], 2) ?> ETB</h3>
                <?php endif; ?>

                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Book Details</h5>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td><strong>Category:</strong></td>
                                <td><?= htmlspecialchars($bookData['department']) ?></td>
                            </tr>
                            <?php if (!empty($bookData['course'])): ?>
                                <tr>
                                    <td><strong>Course/Topic:</strong></td>
                                    <td><?= htmlspecialchars($bookData['course']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Condition:</strong></td>
                                <td><?= ucfirst(str_replace('_', ' ', $bookData['condition_type'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Posted:</strong></td>
                                <td><?= timeAgo($bookData['created_at']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($bookData['description']): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>Description</h5>
                            <p><?= nl2br(htmlspecialchars($bookData['description'])) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['file_path'])): ?>
                    <?php
                    // Determine access rules
                    $exchange = $bookData['exchange_type'];
                    $fileAvailable = false;
                    $accessMessage = '';
                    $showFileSection = true;

                    if ($exchange === 'donate') {
                        // Public access - anyone can view/download donate books
                        $fileAvailable = true;
                        $showFileSection = true;
                    } elseif ($exchange === 'buy') {
                        // For buy: only owner, admin, or buyer with verified payment proof
                        $fileAvailable = false;
                        if ($user->isLoggedIn()) {
                            $currentId = $user->getCurrentUserId();
                            if ($currentId == $bookData['user_id'] || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')) {
                                $fileAvailable = true;
                            } else {
                                // Check for verified payment proof
                                $db = Database::getInstance()->getConnection();
                                $proofStmt = $db->prepare("
                                    SELECT pp.id FROM payment_proofs pp
                                    JOIN requests r ON pp.request_id = r.id
                                    WHERE r.book_id = ? AND r.requester_id = ? AND pp.verified = 1
                                    LIMIT 1
                                ");
                                $proofStmt->execute([$bookData['id'], $currentId]);
                                if ($proofStmt->fetch()) {
                                    $fileAvailable = true;
                                }
                            }
                        }

                        if (!$fileAvailable) {
                            $accessMessage = 'This book is for sale. Complete the payment using one of the accounts shown above, then upload proof for verification to access the files.';
                        }
                    } elseif ($exchange === 'borrow') {
                        // For borrow: only owner, admin, or requester with accepted request and within deadline
                        $fileAvailable = false;
                        if ($user->isLoggedIn()) {
                            $currentId = $user->getCurrentUserId();
                            if ($currentId == $bookData['user_id'] || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')) {
                                $fileAvailable = true;
                            } else {
                                if ((new Request())->isRequestAccepted($currentId, $bookData['id'])) {
                                    $fileAvailable = true;
                                } else {
                                    // For borrow, hide file section entirely until request is accepted
                                    $showFileSection = false;
                                }
                            }
                        } else {
                            // For borrow, hide file section for non-logged-in users
                            $showFileSection = false;
                        }

                        if (!$fileAvailable && $showFileSection) {
                            $accessMessage = 'This book is set to borrow. Files are available once the owner accepts your borrow request.';
                        }
                    }

                    if ($showFileSection):
                    ?>

                        <div class="mb-3">
                            <?php if ($fileAvailable): ?>
                                <a href="<?= site_url('views/public/download.php?book_id=' . $bookData['id'] . '&action=view') ?>" class="btn btn-outline-primary me-2" target="_blank">View File</a>
                                <a href="<?= site_url('views/public/download.php?book_id=' . $bookData['id'] . '&action=download') ?>" class="btn btn-primary">Download File</a>
                                <?php
                                // Show borrow deadline if this is a borrow request
                                if ($exchange === 'borrow' && $user->isLoggedIn()) {
                                    $currentUserId = $user->getCurrentUserId();
                                    $deadlineQuery = $db->prepare("SELECT borrow_deadline FROM requests WHERE book_id = ? AND requester_id = ? AND status IN ('accepted', 'completed') LIMIT 1");
                                    $deadlineQuery->execute([$bookData['id'], $currentUserId]);
                                    $deadlineResult = $deadlineQuery->fetch();
                                    if ($deadlineResult && $deadlineResult['borrow_deadline']) {
                                        $daysRemaining = ceil((strtotime($deadlineResult['borrow_deadline']) - time()) / (60 * 60 * 24));
                                        echo '<div class="alert alert-info mt-2"><i class="bi bi-clock"></i> Borrow access expires on ' . date('M d, Y', strtotime($deadlineResult['borrow_deadline'])) . ' (' . $daysRemaining . ' days remaining)</div>';
                                    }
                                }
                                ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <?= htmlspecialchars($accessMessage) ?>
                                    <?php if ($user->isLoggedIn() && $user->getCurrentUserId() != $bookData['user_id']): ?>
                                        <?php if ($exchange === 'buy'): ?>
                                            <?php $cfg = include __DIR__ . '/../../config/payments.php';
                                            $chapaEnabled = !empty($cfg['chapa']['secret_key']) && strpos($cfg['chapa']['secret_key'], 'CHAPA_SECRET_KEY_HERE') === false;
                                            $stripeEnabled = !empty($cfg['stripe']['secret_key']) && strpos($cfg['stripe']['secret_key'], 'sk_test_') === 0 && strpos($cfg['stripe']['secret_key'], '...') === false; ?>
                                            <?php if ($chapaEnabled || $stripeEnabled): ?>
                                                <div class="mt-2">
                                                    <a href="<?= site_url('views/public/pay.php?book_id=' . $bookData['id']) ?>" class="btn btn-success">Pay Now</a>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-2">Online payment is disabled. Use the accounts below and upload a screenshot with your request.</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="mt-2">You can <strong>send a request</strong> below to start the transaction.</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="mt-2">Please <a href="<?= site_url('views/auth/login.php') ?>">login</a> to request access.</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <h5>Contact Information</h5>
                        <p class="mb-1"><i class="bi bi-person-circle"></i> <strong><?= htmlspecialchars($bookData['owner_name']) ?></strong></p>
                        <?php if ($user->isLoggedIn() && $user->getCurrentUserId() != $bookData['user_id']): ?>
                            <p class="mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($bookData['owner_email']) ?></p>
                            <?php if ($bookData['owner_phone']): ?>
                                <p class="mb-0"><i class="bi bi-phone"></i> <?= htmlspecialchars($bookData['owner_phone']) ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted small">Login to see contact details</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($bookData['exchange_type'] === 'buy' && !empty($paymentAccounts)): ?>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5>Payment Information</h5>
                            <p class="text-muted small">Choose one of the following accounts to complete your payment:</p>
                            <div class="row">
                                <?php foreach ($paymentAccounts as $index => $account): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">Account <?= $index + 1 ?></h6>
                                                <p class="mb-1"><strong>Bank:</strong> <?= htmlspecialchars($account['type']) ?></p>
                                                <p class="mb-1"><strong>Account Number:</strong> <code><?= htmlspecialchars($account['number']) ?></code></p>
                                                <?php if (!empty($account['holder'])): ?>
                                                    <p class="mb-0"><strong>Account Holder:</strong> <?= htmlspecialchars($account['holder']) ?></p>
                                                <?php else: ?>
                                                    <p class="mb-0"><strong>Account Holder:</strong> <?= htmlspecialchars($bookData['owner_name']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> After completing the payment, upload a screenshot of the transaction proof below to get access to the book files.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                // Show transaction history for this book (owner and involved parties)
                $db = Database::getInstance()->getConnection();
                $txsStmt = $db->prepare('SELECT t.*, u.full_name AS buyer_name, u.email AS buyer_email FROM transactions t JOIN users u ON t.buyer_id = u.id WHERE t.book_id = ? ORDER BY t.created_at DESC');
                $txsStmt->execute([$bookId]);
                $txs = $txsStmt->fetchAll();
                if (!empty($txs)): ?>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5>Transaction History</h5>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($txs as $t): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($t['buyer_name']) ?></strong>
                                        - <?= htmlspecialchars($t['status']) ?>
                                        <?php if (!empty($t['proof_path'])): ?>
                                            - <a href="<?= site_url($t['proof_path']) ?>" target="_blank">View proof</a>
                                        <?php endif; ?>
                                        <span class="text-muted small d-block"><?= $t['created_at'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$user->isLoggedIn()): ?>
                    <div class="alert alert-info mt-3">
                        Please <a href="<?= site_url('views/auth/login.php') ?>">login</a> to send a request for this book.
                    </div>
                <?php elseif ($user->getCurrentUserId() == $bookData['user_id']): ?>
                    <div class="alert alert-warning mt-3">
                        This is your own listing.
                    </div>
                <?php else: ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mt-3">
                            <?php foreach ($errors as $error): ?>
                                <?= htmlspecialchars($error) ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label text-primary">Send a request message:</label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Introduce yourself and explain why you need this book" required></textarea>
                        </div>
                        <?php if ($bookData['exchange_type'] === 'borrow'): ?>
                            <div class="mb-3">
                                <label class="form-label">How many days do you want to borrow this book? (1-30 days)</label>
                                <input type="number" name="borrow_days" class="form-control" min="1" max="30" required>
                            </div>
                        <?php elseif ($bookData['exchange_type'] === 'buy'): ?>
                            <div class="mb-3">
                                <label class="form-label text-primary">Payment Proof Screenshot *</label>
                                <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
                                <small class="text-warning">Upload a screenshot showing the completed payment transaction</small>
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-send"></i> Send Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>