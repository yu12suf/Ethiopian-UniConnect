<?php
require_once __DIR__ . '/../../includes/init.php';
// Webhook endpoint for payment provider callbacks (demo)
// Expects JSON payload:
// { "provider": "demo", "provider_txn_id": "abc123", "transaction_id": 42, "status": "success" }

// Minimal authentication using a shared secret in config/payments.php
$cfg = [];
$cfgFile = __DIR__ . '/../../config/payments.php';
if (file_exists($cfgFile)) {
    $cfg = include $cfgFile;
}
$secret = $cfg['webhook_secret'] ?? '';

$headers = getallheaders();
$signature = $headers['X-UNICONNECT-SIGN'] ?? $headers['x-uniconnect-sign'] ?? null;
if ($secret && !$signature) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing signature']);
    exit;
}

// validate signature (simple equality for demo)
if ($secret && $signature !== $secret) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$provider = $data['provider'] ?? 'demo';
$provider_txn_id = $data['provider_txn_id'] ?? null;
$txnId = isset($data['transaction_id']) ? intval($data['transaction_id']) : null;
$status = $data['status'] ?? null; // e.g., success, failed, pending

$db = Database::getInstance()->getConnection();

// Find transaction by provider_txn_id or id
$txn = null;
if ($provider_txn_id) {
    $s = $db->prepare('SELECT * FROM transactions WHERE provider_txn_id = ? LIMIT 1');
    $s->execute([$provider_txn_id]);
    $txn = $s->fetch();
}
if (!$txn && $txnId) {
    $s = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
    $s->execute([$txnId]);
    $txn = $s->fetch();
}

if (!$txn) {
    http_response_code(404);
    echo json_encode(['error' => 'Transaction not found']);
    exit;
}

// Map provider status to our status values
$map = [
    'success' => 'completed',
    'completed' => 'completed',
    'failed' => 'failed',
    'pending' => 'pending',
];

$newStatus = $map[strtolower($status)] ?? 'pending';

try {
    $upd = $db->prepare('UPDATE transactions SET status = ?, provider = ?, provider_txn_id = ? WHERE id = ?');
    $upd->execute([$newStatus, $provider, $provider_txn_id, $txn['id']]);

    // If completed and linked to a request, mark request completed as well
    if ($newStatus === 'completed' && !empty($txn['request_id'])) {
        $r = $db->prepare('UPDATE requests SET status = ? WHERE id = ?');
        $r->execute(['completed', $txn['request_id']]);

        // Notify parties
        $q = $db->prepare('SELECT b.title, u.email AS owner_email, u.full_name AS owner_name, ru.email AS requester_email, ru.full_name AS requester_name, r.book_id FROM transactions t JOIN requests r ON t.request_id = r.id JOIN books b ON t.book_id = b.id JOIN users u ON b.user_id = u.id JOIN users ru ON r.requester_id = ru.id WHERE t.id = ? LIMIT 1');
        $q->execute([$txn['id']]);
        $info = $q->fetch();
        if ($info) {
            $toReq = $info['requester_email'];
            $subReq = 'Payment confirmed for "' . $info['title'] . '"';
            $bodyReq = '<p>Hi ' . htmlspecialchars($info['requester_name']) . ',</p>' .
                '<p>Your payment for the book "' . htmlspecialchars($info['title']) . '" has been confirmed. You can now access the file.</p>' .
                '<p><a href="' . site_url('views/public/book_detail.php?id=' . $info['book_id']) . '">View Book</a></p>' .
                '<p>Thanks,<br>UniConnect</p>';
            send_email($toReq, $subReq, $bodyReq);

            $toOwner = $info['owner_email'];
            $subOwner = 'Payment received for "' . $info['title'] . '"';
            $bodyOwner = '<p>Hi ' . htmlspecialchars($info['owner_name']) . ',</p>' .
                '<p>Payment for your book "' . htmlspecialchars($info['title']) . '" has been confirmed by the provider.</p>' .
                '<p>Request ID: ' . $txn['request_id'] . '</p>' .
                '<p>Thanks,<br>UniConnect</p>';
            send_email($toOwner, $subOwner, $bodyOwner);
        }
    }

    echo json_encode(['success' => true, 'status' => $newStatus]);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}
