<?php

/**
 * Message Class - Handles messaging between users
 * Demonstrates: OOP, real-time communication database structure
 */

class Message
{
    private $db;
    private $hasSubjectColumn = null;
    private $hasGroupColumn = null;
    private $hasTypeColumn = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        // detect whether messages.subject column exists to avoid runtime errors on older DBs
        try {
            $sth = $this->db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'subject' LIMIT 1");
            $sth->execute();
            $col = $sth->fetch();
            $this->hasSubjectColumn = !empty($col);
        } catch (Exception $e) {
            // If detection fails for any reason, assume column does not exist to be safe
            $this->hasSubjectColumn = false;
        }
        // detect group_id and type columns too
        try {
            $sth = $this->db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'group_id' LIMIT 1");
            $sth->execute();
            $col = $sth->fetch();
            $this->hasGroupColumn = !empty($col);
        } catch (Exception $e) {
            $this->hasGroupColumn = false;
        }

        try {
            $sth = $this->db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'type' LIMIT 1");
            $sth->execute();
            $col = $sth->fetch();
            $this->hasTypeColumn = !empty($col);
        } catch (Exception $e) {
            $this->hasTypeColumn = false;
        }
    }

    /**
     * Send message
     */
    // attachments: array of attachment IDs to link to the message
    public function sendMessage($senderId, $receiverId, $message, $bookId = null, $subject = null, $attachments = null, $groupId = null)
    {
        // Debug: log attempts to send messages to help diagnose duplicate sends
        try {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $caller = isset($bt[1]['file']) ? $bt[1]['file'] . ':' . ($bt[1]['line'] ?? '') : 'unknown';
            $log = sprintf("[%s] sendMessage attempt by %s -> receiver:%s book:%s subject:%s caller:%s\n", date('c'), $senderId, $receiverId, $bookId ?? 'NULL', $subject ?? 'NULL', $caller);
            file_put_contents(__DIR__ . '/../logs/messages.log', $log, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // ignore logging errors
        }

        // Server-side protection: ensure receiver exists and is active
        $check = $this->db->prepare("SELECT status FROM users WHERE id = ?");
        $check->execute([$receiverId]);
        $user = $check->fetch();
        if (!$user) {
            return ['success' => false, 'message' => 'Recipient not found'];
        }
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Cannot send message to inactive user'];
        }

        // To avoid race conditions where two concurrent requests pass the duplicate check and both insert,
        // acquire a MySQL advisory lock scoped to the sender/receiver pair, perform the duplicate check and
        // insertion while holding the lock, then release it. This avoids changing the DB schema.
        $lockName = 'msg_lock_' . intval($senderId) . '_' . intval($receiverId);
        $gotLock = false;
        try {
            try {
                $sthLock = $this->db->prepare('SELECT GET_LOCK(?, 5) as l');
                $sthLock->execute([$lockName]);
                $r = $sthLock->fetch();
                $gotLock = !empty($r) && (int)$r['l'] === 1;
                // diagnostic: log lock acquisition
                try {
                    file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] GET_LOCK(%s) for %s->%s => %s\n", date('c'), $lockName, $senderId, $receiverId, $gotLock ? 'acquired' : 'not_acquired'), FILE_APPEND | LOCK_EX);
                } catch (Exception $e) {
                }
            } catch (Exception $e) {
                // If lock acquisition fails, proceed without lock (best-effort)
                $gotLock = false;
                try {
                    file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] GET_LOCK(%s) error for %s->%s: %s\n", date('c'), $lockName, $senderId, $receiverId, $e->getMessage()), FILE_APPEND | LOCK_EX);
                } catch (Exception $ee) {
                }
            }

            // Under the lock (if acquired) perform duplicate check
            try {
                if ($this->hasSubjectColumn) {
                    $dupSql = "SELECT id FROM messages WHERE sender_id = ? AND receiver_id = ? AND TRIM(message) = TRIM(?) " .
                        "AND (book_id <=> ?) AND (subject <=> ?) AND created_at >= (NOW() - INTERVAL 10 SECOND) LIMIT 1";
                    $dupStmt = $this->db->prepare($dupSql);
                    $dupStmt->execute([$senderId, $receiverId, $message, $bookId, $subject]);
                } else {
                    $dupSql = "SELECT id FROM messages WHERE sender_id = ? AND receiver_id = ? AND TRIM(message) = TRIM(?) " .
                        "AND (book_id <=> ?) AND created_at >= (NOW() - INTERVAL 10 SECOND) LIMIT 1";
                    $dupStmt = $this->db->prepare($dupSql);
                    $dupStmt->execute([$senderId, $receiverId, $message, $bookId]);
                }
                $dup = $dupStmt->fetch();
                if ($dup) {
                    // Log duplicate detection
                    try {
                        file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] duplicate detected (id=%s) for sender %s -> receiver %s\n", date('c'), $dup['id'], $senderId, $receiverId), FILE_APPEND | LOCK_EX);
                    } catch (Exception $e) {
                    }
                    // Duplicate detected; return success to the caller but do not insert again
                    return ['success' => true, 'message' => 'Message sent'];
                }
            } catch (Exception $e) {
                // If duplicate check fails for any reason, proceed with normal insertion (don't block send)
            }

            // Perform insertion while still holding the lock
            // Build INSERT dynamically depending on which optional columns exist (subject, group_id, type)
            $cols = ['sender_id', 'receiver_id', 'book_id', 'message'];
            $placeholders = ['?', '?', '?', '?'];
            $params = [$senderId, $receiverId, $bookId, $message];

            if ($this->hasSubjectColumn) {
                $cols[] = 'subject';
                $placeholders[] = '?';
                $params[] = $subject;
            }
            if ($this->hasGroupColumn && $groupId !== null) {
                $cols[] = 'group_id';
                $placeholders[] = '?';
                $params[] = $groupId;
            }
            if ($this->hasTypeColumn) {
                $cols[] = 'type';
                $placeholders[] = '?';
                // determine type based on attachments presence
                $type = 'text';
                if (!empty($attachments)) {
                    // naive detection: if any attachment present, set type=file
                    $type = 'file';
                }
                $params[] = $type;
            }

            $cols[] = 'created_at';
            $placeholders[] = 'NOW()';

            $sql = 'INSERT INTO messages (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute($params);

            // diagnostic: log insert result and last insert id (if any)
            try {
                if (!empty($ok)) {
                    $lastId = $this->db->lastInsertId();
                    file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] INSERT_OK %s->%s id=%s\n", date('c'), $senderId, $receiverId, $lastId), FILE_APPEND | LOCK_EX);
                    // If attachments were passed, link them
                    if (!empty($attachments) && is_array($attachments)) {
                        $linkStmt = $this->db->prepare('INSERT IGNORE INTO message_attachments (message_id, attachment_id) VALUES (?, ?)');
                        foreach ($attachments as $attId) {
                            $linkStmt->execute([$lastId, intval($attId)]);
                            try {
                                file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] ATTACH_LINK message=%s attachment=%s\n", date('c'), $lastId, intval($attId)), FILE_APPEND | LOCK_EX);
                            } catch (Exception $e) {
                            }
                        }
                    }
                } else {
                    $err = $stmt->errorInfo();
                    file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] INSERT_FAIL %s->%s err=%s\n", date('c'), $senderId, $receiverId, json_encode($err)), FILE_APPEND | LOCK_EX);
                }
            } catch (Exception $e) {
            }
        } finally {
            // Release the lock if we acquired it
            if ($gotLock) {
                try {
                    $sthRel = $this->db->prepare('SELECT RELEASE_LOCK(?)');
                    $sthRel->execute([$lockName]);
                    try {
                        file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] RELEASE_LOCK(%s) for %s->%s\n", date('c'), $lockName, $senderId, $receiverId), FILE_APPEND | LOCK_EX);
                    } catch (Exception $e) {
                    }
                } catch (Exception $e) {
                    // ignore release errors
                    try {
                        file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] RELEASE_LOCK(%s) error for %s->%s: %s\n", date('c'), $lockName, $senderId, $receiverId, $e->getMessage()), FILE_APPEND | LOCK_EX);
                    } catch (Exception $ee) {
                    }
                }
            }
        }

        if ($ok) {
            // Log successful insert
            try {
                file_put_contents(__DIR__ . '/../logs/messages.log', sprintf("[%s] message inserted by %s -> %s\n", date('c'), $senderId, $receiverId), FILE_APPEND | LOCK_EX);
            } catch (Exception $e) {
            }
            return ['success' => true, 'message' => 'Message sent'];
        }

        return ['success' => false, 'message' => 'Failed to send message'];
    }

    /**
     * Get conversation between two users
     */
    public function getConversation($userId1, $userId2, $bookId = null)
    {
        $sql = "SELECT m.*, 
                u1.full_name as sender_name, 
                u2.full_name as receiver_name 
                FROM messages m 
                JOIN users u1 ON m.sender_id = u1.id 
                JOIN users u2 ON m.receiver_id = u2.id 
                WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
                   OR (m.sender_id = ? AND m.receiver_id = ?))";

        $params = [$userId1, $userId2, $userId2, $userId1];

        if ($bookId) {
            $sql .= " AND m.book_id = ?";
            $params[] = $bookId;
        }

        $sql .= " ORDER BY m.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get all conversations for a user
     */
    public function getUserConversations($userId)
    {
        $sql = "SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as other_user_id,
                u.full_name as other_user_name,
                b.title as book_title,
                b.id as book_id,
                (SELECT message FROM messages m2 
                 WHERE (m2.sender_id = other_user_id AND m2.receiver_id = ?) 
                    OR (m2.sender_id = ? AND m2.receiver_id = other_user_id)
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages m2 
                 WHERE (m2.sender_id = other_user_id AND m2.receiver_id = ?) 
                    OR (m2.sender_id = ? AND m2.receiver_id = other_user_id)
                 ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM messages m
                LEFT JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
                LEFT JOIN books b ON m.book_id = b.id
                WHERE m.sender_id = ? OR m.receiver_id = ?
                ORDER BY last_message_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mark messages as read
     */
    public function markAsRead($senderId, $receiverId)
    {
        $sql = "UPDATE messages SET is_read = 1 
                WHERE sender_id = ? AND receiver_id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$senderId, $receiverId]);
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM messages 
                WHERE receiver_id = ? AND is_read = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Edit a message. Only the original sender can edit.
     */
    public function editMessage($messageId, $senderId, $newMessage)
    {
        // ensure message belongs to sender
        $st = $this->db->prepare('SELECT sender_id FROM messages WHERE id = ?');
        $st->execute([$messageId]);
        $r = $st->fetch();
        if (!$r || $r['sender_id'] != $senderId) {
            return ['success' => false, 'message' => 'Permission denied'];
        }

        // attempt to update message; if edited_at column exists set it
        try {
            $sth = $this->db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'edited_at' LIMIT 1");
            $sth->execute();
            $col = $sth->fetch();
            $hasEditedAt = !empty($col);
        } catch (Exception $e) {
            $hasEditedAt = false;
        }

        if ($hasEditedAt) {
            $sql = 'UPDATE messages SET message = ?, edited_at = NOW() WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([$newMessage, $messageId]);
        } else {
            $sql = 'UPDATE messages SET message = ? WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([$newMessage, $messageId]);
        }

        if ($ok) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to update message'];
    }

    /**
     * Delete a single message. Only the sender may delete a message (deletes row).
     */
    public function deleteMessage($messageId, $userId)
    {
        // verify ownership
        $st = $this->db->prepare('SELECT sender_id FROM messages WHERE id = ?');
        $st->execute([$messageId]);
        $r = $st->fetch();
        if (!$r) return ['success' => false, 'message' => 'Message not found'];
        if ($r['sender_id'] != $userId) return ['success' => false, 'message' => 'Permission denied'];

        $del = $this->db->prepare('DELETE FROM messages WHERE id = ?');
        if ($del->execute([$messageId])) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to delete message'];
    }

    /**
     * Delete entire conversation between two users. This deletes all messages exchanged between them.
     * Use with caution — this removes rows permanently.
     */
    public function deleteConversation($userA, $userB)
    {
        $sql = 'DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)';
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$userA, $userB, $userB, $userA])) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to delete conversation'];
    }
}
