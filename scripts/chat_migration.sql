-- Migration to add chat/group/attachments support
-- Run with: mysql -uUSER -pPASSWORD DB_NAME < chat_migration.sql

ALTER TABLE messages
  ADD COLUMN `type` VARCHAR
(20) NOT NULL DEFAULT 'text',
ADD COLUMN `group_id` INT NULL DEFAULT NULL,
ADD COLUMN `message_hash` VARCHAR
(64) NULL;

CREATE TABLE
IF NOT EXISTS attachments
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  filename VARCHAR
(255) NOT NULL,
  mime VARCHAR
(100) NOT NULL,
  size INT NOT NULL,
  path VARCHAR
(512) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX
(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE
IF NOT EXISTS message_attachments
(
  message_id INT NOT NULL,
  attachment_id INT NOT NULL,
  PRIMARY KEY
(message_id, attachment_id),
  INDEX
(attachment_id),
  FOREIGN KEY
(message_id) REFERENCES messages
(id) ON
DELETE CASCADE,
  FOREIGN KEY (attachment_id)
REFERENCES attachments
(id) ON
DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE
IF NOT EXISTS chat_groups
(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR
(255) NOT NULL,
  owner_id INT NOT NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX
(owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE
IF NOT EXISTS chat_group_members
(
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  role VARCHAR
(32) NOT NULL DEFAULT 'member',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY
(group_id, user_id),
  INDEX
(user_id),
  FOREIGN KEY
(group_id) REFERENCES chat_groups
(id) ON
DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: add index to speed up conversation queries
CREATE INDEX
IF NOT EXISTS idx_messages_sender_receiver_created_at ON messages
(sender_id, receiver_id, created_at);
