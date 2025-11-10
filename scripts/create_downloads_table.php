<?php
require_once __DIR__ . '/../includes/init.php';

try {
    $db = Database::getInstance()->getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        book_id INT NOT NULL,
        action ENUM('view','download') NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_book (book_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "downloads table created or already exists\n";
} catch (Exception $e) {
    echo "Failed to create downloads table: " . $e->getMessage() . "\n";
    exit(1);
}
