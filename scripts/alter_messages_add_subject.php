<?php
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance()->getConnection();

try {
    $db->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS subject VARCHAR(255) DEFAULT NULL");
    echo "Added subject column to messages table or it already exists\n";
} catch (PDOException $e) {
    // Some MySQL versions do not support IF NOT EXISTS for ADD COLUMN
    $msg = $e->getMessage();
    if (strpos($msg, 'Duplicate column name') !== false) {
        echo "Subject column already exists\n";
    } else {
        echo "Error altering messages table: " . $e->getMessage() . "\n";
    }
}

return;
