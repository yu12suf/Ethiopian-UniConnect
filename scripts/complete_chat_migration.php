<?php
require_once __DIR__ . '/../includes/init.php';
$db = Database::getInstance()->getConnection();

// For DDL statements we avoid wrapping in transactions because many DDL operations cause implicit commits in MySQL
try {
    // Create chat_group_members if missing
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'chat_group_members'");
    $stmt->execute([':db' => DB_NAME]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (intval($row['c']) === 0) {
        echo "Creating chat_group_members table...\n";
        $db->exec(
            <<<'SQL'
CREATE TABLE chat_group_members (
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'member',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id, user_id),
  INDEX (user_id),
  FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    } else {
        echo "chat_group_members already exists, skipping.\n";
    }

    // Create index if missing
    $idxCheck = $db->prepare("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'messages' AND INDEX_NAME = 'idx_messages_sender_receiver_created_at'");
    $idxCheck->execute([':db' => DB_NAME]);
    $idxRow = $idxCheck->fetch(PDO::FETCH_ASSOC);
    if (intval($idxRow['c']) === 0) {
        echo "Creating idx_messages_sender_receiver_created_at index...\n";
        $db->exec("CREATE INDEX idx_messages_sender_receiver_created_at ON messages (sender_id, receiver_id, created_at)");
    } else {
        echo "Index idx_messages_sender_receiver_created_at already exists, skipping.\n";
    }

    echo "Completed additional migration steps.\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
