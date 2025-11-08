<?php
/**
 * Book Class - Handles book listings, uploads, and management
 * Demonstrates: OOP, file handling, CRUD operations
 */

class Book {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create new book listing with image upload
     */
    public function createListing($data, $imageFile) {
        // Handle image upload
        $imagePath = null;
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadImage($imageFile);
            if (!$imagePath) {
                return ['success' => false, 'message' => 'Image upload failed'];
            }
        }
        
        // Insert book listing
        $sql = "INSERT INTO books (user_id, title, author, department, course, description, 
                condition_type, exchange_type, price, image_path, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $params = [
            $data['user_id'],
            $data['title'],
            $data['author'],
            $data['department'],
            $data['course'],
            $data['description'],
            $data['condition'],
            $data['exchange_type'],
            $data['price'] ?? null,
            $imagePath
        ];
        
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($params)) {
            return ['success' => true, 'message' => 'Book listing created successfully. Waiting for admin approval.'];
        }
        
        return ['success' => false, 'message' => 'Failed to create listing'];
    }
    
    /**
     * Upload book image
     */
    private function uploadImage($file) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return false;
        }
        
        $uploadDir = __DIR__ . '/../uploads/books/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('book_') . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return 'uploads/books/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Get all approved books with optional filters
     */
    public function getBooks($filters = []) {
        $sql = "SELECT b.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                FROM books b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.status = 'approved' AND b.availability = 'available'";
        
        $params = [];
        
        // Add search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.course LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Add department filter
        if (!empty($filters['department'])) {
            $sql .= " AND b.department = ?";
            $params[] = $filters['department'];
        }
        
        // Add exchange type filter
        if (!empty($filters['exchange_type'])) {
            $sql .= " AND b.exchange_type = ?";
            $params[] = $filters['exchange_type'];
        }
        
        // Add condition filter
        if (!empty($filters['condition'])) {
            $sql .= " AND b.condition_type = ?";
            $params[] = $filters['condition'];
        }
        
        $sql .= " ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get book by ID
     */
    public function getBookById($bookId) {
        $sql = "SELECT b.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                FROM books b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookId]);
        return $stmt->fetch();
    }
    
    /**
     * Get user's listings
     */
    public function getUserListings($userId) {
        $sql = "SELECT * FROM books WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update book listing
     */
    public function updateListing($bookId, $userId, $data) {
        // Verify ownership
        if (!$this->isOwner($bookId, $userId)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $sql = "UPDATE books SET title = ?, author = ?, department = ?, course = ?, 
                description = ?, condition_type = ?, exchange_type = ?, price = ? 
                WHERE id = ? AND user_id = ?";
        
        $params = [
            $data['title'],
            $data['author'],
            $data['department'],
            $data['course'],
            $data['description'],
            $data['condition'],
            $data['exchange_type'],
            $data['price'] ?? null,
            $bookId,
            $userId
        ];
        
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($params)) {
            return ['success' => true, 'message' => 'Listing updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Delete book listing
     */
    public function deleteListing($bookId, $userId) {
        // Verify ownership
        if (!$this->isOwner($bookId, $userId)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        // Get image path to delete file
        $book = $this->getBookById($bookId);
        if ($book && $book['image_path']) {
            $imagePath = __DIR__ . '/../' . $book['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $sql = "DELETE FROM books WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$bookId, $userId])) {
            return ['success' => true, 'message' => 'Listing deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Delete failed'];
    }
    
    /**
     * Mark book as unavailable
     */
    public function markUnavailable($bookId, $userId) {
        if (!$this->isOwner($bookId, $userId)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $sql = "UPDATE books SET availability = 'unavailable' WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$bookId, $userId])) {
            return ['success' => true, 'message' => 'Book marked as unavailable'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Check if user owns the book
     */
    private function isOwner($bookId, $userId) {
        $sql = "SELECT id FROM books WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookId, $userId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get pending books for admin approval
     */
    public function getPendingBooks() {
        $sql = "SELECT b.*, u.full_name as owner_name 
                FROM books b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.status = 'pending' 
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
