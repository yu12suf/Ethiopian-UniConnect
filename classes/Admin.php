<?php
/**
 * Admin Class - Handles administrative functions
 * Demonstrates: OOP, user management, reporting
 */

class Admin {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Approve book listing
     */
    public function approveBook($bookId) {
        $sql = "UPDATE books SET status = 'approved' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$bookId])) {
            $this->logAction('approve_book', $bookId);
            return ['success' => true, 'message' => 'Book approved'];
        }
        
        return ['success' => false, 'message' => 'Approval failed'];
    }
    
    /**
     * Block/reject book listing
     */
    public function blockBook($bookId, $reason = '') {
        $sql = "UPDATE books SET status = 'blocked' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$bookId])) {
            $this->logAction('block_book', $bookId, $reason);
            return ['success' => true, 'message' => 'Book blocked'];
        }
        
        return ['success' => false, 'message' => 'Block failed'];
    }
    
    /**
     * Delete book listing
     */
    public function deleteBook($bookId) {
        // Get book details first
        $sql = "SELECT image_path FROM books WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        
        // Delete image file
        if ($book && $book['image_path']) {
            $imagePath = __DIR__ . '/../' . $book['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Delete book
        $sql = "DELETE FROM books WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$bookId])) {
            $this->logAction('delete_book', $bookId);
            return ['success' => true, 'message' => 'Book deleted'];
        }
        
        return ['success' => false, 'message' => 'Delete failed'];
    }
    
    /**
     * Get all users
     */
    public function getAllUsers() {
        $sql = "SELECT id, full_name, email, department, phone, role, status, created_at, last_login 
                FROM users ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Deactivate user account
     */
    public function deactivateUser($userId) {
        $sql = "UPDATE users SET status = 'inactive' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$userId])) {
            $this->logAction('deactivate_user', $userId);
            return ['success' => true, 'message' => 'User deactivated'];
        }
        
        return ['success' => false, 'message' => 'Deactivation failed'];
    }
    
    /**
     * Activate user account
     */
    public function activateUser($userId) {
        $sql = "UPDATE users SET status = 'active' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$userId])) {
            $this->logAction('activate_user', $userId);
            return ['success' => true, 'message' => 'User activated'];
        }
        
        return ['success' => false, 'message' => 'Activation failed'];
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total users
        $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Total books
        $sql = "SELECT COUNT(*) as count FROM books";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total_books'] = $stmt->fetch()['count'];
        
        // Pending approvals
        $sql = "SELECT COUNT(*) as count FROM books WHERE status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['pending_approvals'] = $stmt->fetch()['count'];
        
        // Total requests
        $sql = "SELECT COUNT(*) as count FROM requests";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total_requests'] = $stmt->fetch()['count'];
        
        // Completed exchanges
        $sql = "SELECT COUNT(*) as count FROM requests WHERE status = 'accepted'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['completed_exchanges'] = $stmt->fetch()['count'];
        
        // Books by exchange type
        $sql = "SELECT exchange_type, COUNT(*) as count FROM books WHERE status = 'approved' GROUP BY exchange_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['by_exchange_type'] = $stmt->fetchAll();
        
        // Most active departments
        $sql = "SELECT department, COUNT(*) as count FROM books GROUP BY department ORDER BY count DESC LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['top_departments'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Log admin action
     */
    private function logAction($action, $targetId, $details = '') {
        $adminId = $_SESSION['user_id'] ?? null;
        
        $sql = "INSERT INTO admin_logs (admin_id, action, target_id, details, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$adminId, $action, $targetId, $details]);
    }
    
    /**
     * Get admin logs
     */
    public function getAdminLogs($limit = 50) {
        $sql = "SELECT l.*, u.full_name as admin_name 
                FROM admin_logs l 
                LEFT JOIN users u ON l.admin_id = u.id 
                ORDER BY l.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>
