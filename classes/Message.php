<?php
/**
 * Message Class - Handles messaging between users
 * Demonstrates: OOP, real-time communication database structure
 */

class Message {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Send message
     */
    public function sendMessage($senderId, $receiverId, $message, $bookId = null) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, book_id, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$senderId, $receiverId, $bookId, $message])) {
            return ['success' => true, 'message' => 'Message sent'];
        }
        
        return ['success' => false, 'message' => 'Failed to send message'];
    }
    
    /**
     * Get conversation between two users
     */
    public function getConversation($userId1, $userId2, $bookId = null) {
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
    public function getUserConversations($userId) {
        $sql = "SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as other_user_id,
                u.full_name as other_user_name,
                b.title as book_title,
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
    public function markAsRead($senderId, $receiverId) {
        $sql = "UPDATE messages SET is_read = 1 
                WHERE sender_id = ? AND receiver_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$senderId, $receiverId]);
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM messages 
                WHERE receiver_id = ? AND is_read = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
?>
