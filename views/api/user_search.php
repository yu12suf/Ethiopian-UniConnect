<?php
require_once __DIR__ . '/../../includes/init.php';
requireLogin();

$q = trim($_GET['q'] ?? '');
$limit = intval($_GET['limit'] ?? 10);
$db = Database::getInstance()->getConnection();

if ($q === '') {
    echo json_encode([]);
    exit;
}

$pattern = '%' . str_replace('%', '\\%', $q) . '%';
$limit = max(1, min(100, $limit)); // clamp to reasonable range
$sql = "SELECT id, full_name, email FROM users WHERE status = 'active' AND (full_name LIKE ? OR email LIKE ?) LIMIT ?";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute([$pattern, $pattern, $limit]);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    // Return a safe JSON error instead of throwing a fatal exception which can break the frontend
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Search failed']);
    exit;
}

$out = [];
foreach ($rows as $r) {
    $out[] = ['id' => $r['id'], 'label' => $r['full_name'] . ' <' . $r['email'] . '>', 'name' => $r['full_name'], 'email' => $r['email']];
}

header('Content-Type: application/json');
echo json_encode($out);
