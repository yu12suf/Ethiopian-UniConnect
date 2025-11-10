<?php

/**
 * Book Class - Handles book listings, uploads, and management
 * Demonstrates: OOP, file handling, CRUD operations
 */

class Book
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new book listing with image upload
     */
    public function createListing($data, $imageFile)
    {
        // Handle image upload (optional)
        $imagePath = null;
        if (!empty($imageFile) && isset($imageFile['error']) && $imageFile['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadImage($imageFile);
            if (!$imagePath) {
                return ['success' => false, 'message' => 'Image upload failed'];
            }
        }

        // Handle book file upload (optional) - support PDF/DOC/DOCX
        $filePath = null;
        if (!empty($_FILES['book_file']) && isset($_FILES['book_file']['error']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
            $filePath = $this->uploadDocument($_FILES['book_file']);
            if ($filePath === false) {
                return ['success' => false, 'message' => 'Book file upload failed'];
            }
        }

        // Insert book listing
        $sql = "INSERT INTO books (user_id, title, author, department, course, description, 
        condition_type, exchange_type, price, image_path, file_path, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

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
            $imagePath,
            $filePath
        ];

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($params)) {
            return ['success' => true, 'message' => 'Book listing created successfully. Waiting for admin approval.'];
        }

        return ['success' => false, 'message' => 'Failed to create listing'];
    }

    /**
     * Upload book image with secure validation
     */
    private function uploadImage($file)
    {
        // Allowed MIME types and extensions
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return false;
        }

        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        // Verify actual MIME type using finfo (not client-provided type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return false;
        }

        // Create upload directory if needed
        $uploadDir = __DIR__ . '/../uploads/books/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate secure filename
        $filename = uniqid('book_') . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return 'uploads/books/' . $filename;
        }

        return false;
    }

    /**
     * Upload document files (PDF, DOC, DOCX)
     */
    private function uploadDocument($file)
    {
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream',
            'text/plain'
        ];
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];

        // Increase allowed size for documents (10MB)
        if ($file['size'] > (MAX_FILE_SIZE * 2)) {
            return false;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            // Some clients may send octet-stream; allow by extension fallback
            if ($mimeType !== 'application/octet-stream') {
                return false;
            }
        }

        $uploadDir = __DIR__ . '/../uploads/books/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid('bookfile_') . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return 'uploads/books/' . $filename;
        }

        return false;
    }

    /**
     * Get all approved books with optional filters
     */
    public function getBooks($filters = [])
    {
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
    public function getBookById($bookId)
    {
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
    public function getUserListings($userId)
    {
        $sql = "SELECT * FROM books WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Update book listing
     */
    public function updateListing($bookId, $userId, $data)
    {
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
    public function deleteListing($bookId, $userId)
    {
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
    public function markUnavailable($bookId, $userId)
    {
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
    private function isOwner($bookId, $userId)
    {
        $sql = "SELECT id FROM books WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookId, $userId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get pending books for admin approval
     */
    public function getPendingBooks()
    {
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
