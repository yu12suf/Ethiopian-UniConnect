<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$userId = $user->getCurrentUserId();
$db = Database::getInstance()->getConnection();

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proof_id'])) {
    $proofId = $_POST['proof_id'];
    $action = $_POST['action'];

    // Verify the proof belongs to the user's book
    $proofSql = "SELECT pp.*, r.book_id, b.user_id as book_owner_id
                 FROM payment_proofs pp
                 JOIN requests r ON pp.request_id = r.id
                 JOIN books b ON r.book_id = b.id
                 WHERE pp.id = ?";

    $proofStmt = $db->prepare($proofSql);
    $proofStmt->execute([$proofId]);
    $proof = $proofStmt->fetch();

    if ($proof && $proof['book_owner_id'] == $userId) {
        if ($action === 'approve') {
            // Mark proof as verified
            $updateSql = "UPDATE payment_proofs SET verified = 1, verified_at = NOW(), verified_by = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$userId, $proofId]);

            // Update request status to completed
            $requestUpdateSql = "UPDATE requests SET status = 'completed', payment_verified = 1, payment_verified_at = NOW() WHERE id = ?";
            $requestStmt = $db->prepare($requestUpdateSql);
            $requestStmt->execute([$proof['request_id']]);

            $success = "Payment proof approved successfully. The buyer can now access the book files.";
        } elseif ($action === 'reject') {
            // Mark proof as rejected (keep verified = 0)
            $updateSql = "UPDATE payment_proofs SET verified = 0 WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$proofId]);

            $success = "Payment proof rejected. The buyer will need to upload a new proof.";
        }
    }
}

// Get pending payment proofs for books owned by this user
$proofsSql = "SELECT pp.*, r.id as request_id, r.message as request_message, r.created_at as request_date,
                     b.title as book_title, b.price,
                     u.full_name as buyer_name, u.email as buyer_email
              FROM payment_proofs pp
              JOIN requests r ON pp.request_id = r.id
              JOIN books b ON r.book_id = b.id
              JOIN users u ON r.requester_id = u.id
              WHERE b.user_id = ?
                AND pp.verified = 0
                AND r.status <> 'completed'
                AND pp.id = (
                    SELECT MAX(pp2.id)
                    FROM payment_proofs pp2
                    WHERE pp2.request_id = r.id AND pp2.verified = 0
                )
              ORDER BY pp.uploaded_at DESC";

$proofsStmt = $db->prepare($proofsSql);
$proofsStmt->execute([$userId]);
$pendingProofs = $proofsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Payments - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Verify Payment Proofs</h2>
            <a href="requests.php" class="btn btn-outline-primary">Back to Requests</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pendingProofs)): ?>
            <div class="alert alert-info">
                <i class="bi bi-check-circle"></i> No pending payment proofs to verify.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($pendingProofs as $proof): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0"><?= htmlspecialchars($proof['book_title']) ?></h5>
                                <small class="text-muted">Price: <?= number_format($proof['price'], 2) ?> ETB</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Buyer Information</h6>
                                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($proof['buyer_name']) ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($proof['buyer_email']) ?></p>
                                        <p class="mb-1"><strong>Request Date:</strong> <?= date('M d, Y H:i', strtotime($proof['request_date'])) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Payment Proof</h6>
                                        <div class="mb-3">
                                            <img src="<?= site_url($proof['proof_image']) ?>" class="img-fluid rounded" alt="Payment Proof" style="max-height: 200px;">
                                        </div>
                                        <p class="small text-muted">Uploaded: <?= date('M d, Y H:i', strtotime($proof['uploaded_at'])) ?></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <h6>Request Message</h6>
                                        <p class="text-muted small"><?= nl2br(htmlspecialchars($proof['request_message'])) ?></p>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <form id="approve-form-<?= $proof['id'] ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="proof_id" value="<?= $proof['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="button" class="btn btn-success" onclick="showConfirmModal('approve', '<?= $proof['id'] ?>')">
                                            <i class="bi bi-check-circle"></i> Approve Payment
                                        </button>
                                    </form>

                                    <form id="reject-form-<?= $proof['id'] ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="proof_id" value="<?= $proof['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="button" class="btn btn-danger" onclick="showConfirmModal('reject', '<?= $proof['id'] ?>')">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    Are you sure you want to proceed?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalConfirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let targetFormId = null;

        function showConfirmModal(action, proofId) {
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            const confirmBtn = document.getElementById('modalConfirmBtn');

            if (action === 'approve') {
                targetFormId = 'approve-form-' + proofId;
                modalTitle.textContent = 'Approve Payment';
                modalBody.textContent = 'Approve this payment proof? The buyer will gain access to the book files.';
                confirmBtn.className = 'btn btn-success';
                confirmBtn.textContent = 'Approve Payment';
            } else {
                targetFormId = 'reject-form-' + proofId;
                modalTitle.textContent = 'Reject Payment';
                modalBody.textContent = 'Reject this payment proof? The buyer will need to upload a new proof.';
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.textContent = 'Reject';
            }

            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();
        }

        document.getElementById('modalConfirmBtn').addEventListener('click', function() {
            if (targetFormId) {
                document.getElementById(targetFormId).submit();
            }
        });
    </script>
</body>

</html>