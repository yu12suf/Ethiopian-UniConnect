<?php
// Demo script to POST a webhook to the local payment_webhook endpoint
$webhookUrl = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/views/api/payment_webhook.php';
// But when run from CLI, build based on cwd
$base = 'http://localhost';
$webhookUrl = $base . '/EthiopianUniConnect/views/api/payment_webhook.php';

$payload = [
    'provider' => 'demo',
    'provider_txn_id' => 'demo-' . time(),
    'transaction_id' => 1,
    'status' => 'success'
];

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-UNICONNECT-SIGN: demo-secret-please-change'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$res = curl_exec($ch);
if ($res === false) {
    echo 'Curl error: ' . curl_error($ch) . "\n";
} else {
    echo "Response:\n" . $res . "\n";
}
