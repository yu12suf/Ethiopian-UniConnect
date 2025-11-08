<?php
/**
 * Request Class - Handles book exchange requests
 * Demonstrates: OOP, database relationships
 */

class Request {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create new request
     */
    public function createRequest($requesterId, $bookId, $message) {
        // Check if request already exists
        if ($this->requestExists($requesterId, $bookId)) {
            return ['success' => false, 'message' => 'You have already sent a request for this book'];
        }
        
        $sql = "INSERT INTO requests (requester_id, book_id, message, status, created_at) 
                VALUES (?, ?, ?, 'pending', NOW())";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$requesterId, $bookId, $message])) {
            return ['success' => true, 'message' => 'Request sent successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to send request'];
    }
    
    /**
     * Check if request already exists
     */
    private function requestExists($requesterId, $bookId) {
        $sql = "SELECT id FROM requests WHERE requester_id = ? AND book_id = ? AND status != 'cancelled'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requesterId, $bookId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get received requests for book owner
     */
    public function getReceivedRequests($userId) {
        $sql = "SELECT r.*, b.title as book_title, b.id as book_id, 
                u.full_name as requester_name, u.email as requester_email, u.phone as requester_phone 
                FROM requests r 
                JOIN books b ON r.book_id = b.id 
                JOIN users u ON r.requester_id = u.id 
                WHERE b.user_id = ? 
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get sent requests for requester
     */
    public function getSentRequests($userId) {
        $sql = "SELECT r.*, b.title as book_title, b.image_path, 
                u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                FROM requests r 
                JOIN books b ON r.book_id = b.id 
                JOIN users u ON b.user_id = u.id 
                WHERE r.requester_id = ? 
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update request status
     */
    public function updateRequestStatus($requestId, $ownerId, $status) {
        // Verify the owner is updating their own request
        $sql = "SELECT b.user_id FROM requests r 
                JOIN books b ON r.book_id = b.id 
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request || $request['user_id'] != $ownerId) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $sql = "UPDATE requests SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$status, $requestId])) {
            return ['success' => true, 'message' => 'Request ' . $status];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Cancel request
     */
    public function cancelRequest($requestId, $requesterId) {
        $sql = "UPDATE requests SET status = 'cancelled' WHERE id = ? AND requester_id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$requestId, $requesterId])) {
            return ['success' => true, 'message' => 'Request cancelled'];
        }
        
        return ['success' => false, 'message' => 'Cancellation failed'];
    }
}
?>
