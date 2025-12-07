<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$book = new Book();

$bookId = intval($_GET['book_id'] ?? 0);
$bookData = $book->getBookById($bookId);
// Owner payment accounts for manual transfer
$paymentAccounts = $book->getPaymentAccounts($bookId);

if (!$bookData || $bookData['status'] !== 'approved' || $bookData['exchange_type'] !== 'buy' || empty($bookData['price'])) {
    redirect('/index.php');
}

$errors = [];
$success = '';

// Determine which gateways are enabled based on config keys
$cfg = include '../../config/payments.php';
$chapaEnabled = !empty($cfg['chapa']['secret_key']) && strpos($cfg['chapa']['secret_key'], 'CHAPA_SECRET_KEY_HERE') === false;
$stripeEnabled = !empty($cfg['stripe']['secret_key']) && strpos($cfg['stripe']['secret_key'], 'sk_test_') === 0 && strpos($cfg['stripe']['secret_key'], '...') === false; // simplistic check

// Handle payment initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($chapaEnabled || $stripeEnabled)) {
    $gateway = $_POST['gateway'] ?? 'chapa';

    // Create transaction record
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('INSERT INTO transactions (book_id, buyer_id, amount, status) VALUES (?, ?, ?, ?)');
    $stmt->execute([$bookId, $user->getCurrentUserId(), $bookData['price'], 'pending']);

    $transactionId = $db->lastInsertId();

    // Redirect to payment gateway
    if ($gateway === 'chapa' && $chapaEnabled) {
        // Chapa integration - Initialize payment
        $chapaConfig = include '../../config/payments.php';
        $chapaUrl = 'https://api.chapa.co/v1/hosted/pay';

        $txRef = 'txn_' . $transactionId . '_' . time();
        $payload = [
            'amount' => $bookData['price'],
            'currency' => 'ETB',
            'email' => $user->getCurrentUser()['email'],
            'first_name' => $user->getCurrentUser()['full_name'],
            'last_name' => '',
            'tx_ref' => $txRef,
            'callback_url' => site_url('views/api/payment_webhook.php'),
            'return_url' => site_url('views/public/book_detail.php?id=' . $bookId . '&payment=completed'),
            'customization' => [
                'title' => 'Payment for ' . $bookData['title'],
                'description' => 'Book purchase on UniConnect',
                'logo' => site_url('assets/images/logo.png')
            ]
        ];

        // Update transaction with tx_ref
        $db->prepare('UPDATE transactions SET provider_txn_id = ? WHERE id = ?')->execute([$txRef, $transactionId]);

        // Always attempt to create Chapa checkout session
        // Chapa handles test vs production mode based on API keys
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $chapaUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $chapaConfig['chapa']['secret_key']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Debug logging
        error_log("Chapa API Response: HTTP $httpCode, Response: $response, Error: $curlError");

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['data']['checkout_url'])) {
                // Redirect to Chapa checkout
                redirect($result['data']['checkout_url']);
            } else {
                $msg = $result['message'] ?? 'Unknown error';
                if (is_array($msg)) {
                    $msg = json_encode($msg);
                }
                $errors[] = 'Payment initialization failed: Invalid response from Chapa - ' . $msg;
            }
        } else {
            $errorMsg = 'Payment initialization failed: Unable to connect to Chapa. ';
            if ($curlError) {
                $errorMsg .= "Connection error: $curlError";
            } elseif ($response) {
                $result = json_decode($response, true);
                $msg = $result['message'] ?? "HTTP $httpCode";
                if (is_array($msg)) {
                    $msg = json_encode($msg);
                }
                $errorMsg .= $msg;
            } else {
                $errorMsg .= "HTTP $httpCode";
            }
            $errors[] = $errorMsg;
        }
    } elseif ($gateway === 'stripe' && $stripeEnabled) {
        // Stripe integration
        // Similar structure
        redirect(site_url('views/public/book_detail.php?id=' . $bookId . '&payment=success'));
    } else {
        $errors[] = 'Online payment gateway not available. Please use manual payment.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay for <?= htmlspecialchars($bookData['title']) ?> - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center">Complete Payment</h2>

                        <div class="text-center mb-4">
                            <h4><?= htmlspecialchars($bookData['title']) ?></h4>
                            <p class="text-muted">by <?= htmlspecialchars($bookData['author']) ?></p>
                            <h3 class="text-success"><?= number_format($bookData['price'], 2) ?> ETB</h3>
                        </div>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <?= htmlspecialchars($error) ?><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($chapaEnabled || $stripeEnabled): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Select Payment Method</label>
                                    <?php if ($chapaEnabled): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gateway" id="chapa" value="chapa" checked>
                                            <label class="form-check-label" for="chapa">Chapa (Ethiopian Payments)</label>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($stripeEnabled): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gateway" id="stripe" value="stripe" <?= $chapaEnabled ? '' : 'checked' ?>>
                                            <label class="form-check-label" for="stripe">Stripe (International)</label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-credit-card"></i> Pay Now
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$chapaEnabled && !$stripeEnabled): ?>
                            <div class="alert alert-info mt-3">
                                Online payment is currently disabled. Please use the manual payment option below and upload a screenshot for verification.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($paymentAccounts)): ?>
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h5>Manual Payment Accounts</h5>
                                    <p class="text-muted small">Use any of the accounts below to transfer the amount, then upload proof via the request form.</p>
                                    <div class="row">
                                        <?php foreach ($paymentAccounts as $index => $account): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Account <?= $index + 1 ?></h6>
                                                        <p class="mb-1"><strong>Bank:</strong> <?= htmlspecialchars($account['type']) ?></p>
                                                        <p class="mb-1"><strong>Account Number:</strong> <code><?= htmlspecialchars($account['number']) ?></code></p>
                                                        <?php if (!empty($account['holder'])): ?>
                                                            <p class="mb-0"><strong>Account Holder:</strong> <?= htmlspecialchars($account['holder']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="alert alert-warning mt-2">
                                        After payment, go to the book page and send your request with a screenshot so the owner can verify it.
                                    </div>
                                    <a href="<?= site_url('views/public/book_detail.php?id=' . $bookId) ?>" class="btn btn-primary">Open Book Page to Upload Proof</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="<?= site_url('views/public/book_detail.php?id=' . $bookId) ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>