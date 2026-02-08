<?php
require_once '../../includes/init.php';
requireLogin();
requireAdmin();

$platformFee = new PlatformFee();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $paymentId = intval($_POST['payment_id'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if ($action === 'approve' && $paymentId > 0) {
        if ($platformFee->approvePayment($paymentId, $_SESSION['user_id'], $notes)) {
            $success = 'Payment approved successfully.';
        } else {
            $errors[] = 'Failed to approve payment.';
        }
    } elseif ($action === 'reject' && $paymentId > 0) {
        if ($platformFee->rejectPayment($paymentId, $_SESSION['user_id'], $notes)) {
            $success = 'Payment rejected.';
        } else {
            $errors[] = 'Failed to reject payment.';
        }
    }
}

$pendingPayments = $platformFee->getPendingPayments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Fees - Admin - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-credit-card"></i> Platform Fee Payments</h2>
            <a href="index.php" class="btn btn-outline-secondary">Back to Admin</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <?= htmlspecialchars($error) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pendingPayments)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No pending platform fee payments.
            </div>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Pending Approvals</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Amount</th>
                                    <th>Submitted</th>
                                    <th>Proof</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['full_name']) ?></td>
                                        <td><?= htmlspecialchars($payment['email']) ?></td>
                                        <td><?= number_format($payment['amount'], 2) ?> ETB</td>
                                        <td><?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></td>
                                        <td>
                                            <?php if ($payment['proof_image']): ?>
                                                <a href="../../<?= htmlspecialchars($payment['proof_image']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View Proof
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No proof</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success me-2" data-bs-toggle="modal" data-bs-target="#actionModal" data-action="approve" data-payment-id="<?= $payment['id'] ?>" data-user="<?= htmlspecialchars($payment['full_name']) ?>">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#actionModal" data-action="reject" data-payment-id="<?= $payment['id'] ?>" data-user="<?= htmlspecialchars($payment['full_name']) ?>">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p id="actionText"></p>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes..."></textarea>
                        </div>
                        <input type="hidden" name="payment_id" id="modalPaymentId">
                        <input type="hidden" name="action" id="modalAction">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="confirmBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const actionModal = document.getElementById('actionModal');
        actionModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const action = button.getAttribute('data-action');
            const paymentId = button.getAttribute('data-payment-id');
            const user = button.getAttribute('data-user');

            const modalTitle = actionModal.querySelector('.modal-title');
            const actionText = actionModal.querySelector('#actionText');
            const confirmBtn = actionModal.querySelector('#confirmBtn');

            document.getElementById('modalPaymentId').value = paymentId;
            document.getElementById('modalAction').value = action;

            if (action === 'approve') {
                modalTitle.textContent = 'Approve Payment';
                actionText.textContent = `Are you sure you want to approve the platform fee payment for ${user}?`;
                confirmBtn.textContent = 'Approve';
                confirmBtn.className = 'btn btn-success';
            } else {
                modalTitle.textContent = 'Reject Payment';
                actionText.textContent = `Are you sure you want to reject the platform fee payment for ${user}?`;
                confirmBtn.textContent = 'Reject';
                confirmBtn.className = 'btn btn-danger';
            }
        });
    </script>
</body>
</html>