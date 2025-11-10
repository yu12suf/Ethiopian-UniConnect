<?php
require_once __DIR__ . '/../includes/init.php';
try {
    $db = Database::getInstance()->getConnection();
    $check = $db->query("SHOW COLUMNS FROM transactions LIKE 'proof_path'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE transactions ADD COLUMN proof_path VARCHAR(255) DEFAULT NULL");
        echo "Added proof_path column to transactions table\n";
    } else {
        echo "proof_path column already exists\n";
    }
} catch (Exception $e) {
    echo "Failed to alter transactions table: " . $e->getMessage() . "\n";
    exit(1);
}
