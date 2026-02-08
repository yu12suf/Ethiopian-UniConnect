<?php
/**
 * Update Admin Name Script
 */

require_once __DIR__ . '/../includes/init.php';

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE email = ? AND role = 'admin'");
    $stmt->execute(['Yusuf Kedir', 'admin@uniconnect.edu.et']);

    echo "✓ Admin name updated to Yusuf Kedir\n";

} catch (Exception $e) {
    echo "✗ Error updating admin name: " . $e->getMessage() . "\n";
}