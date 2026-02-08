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
     /**
      * Create new book listing with image upload
      */
     public function createListing($data, $imageFile)
     {
         // Handle image upload (optional)
         $imagePath = null;
         if (!empty($imageFile) && isset($imageFile['error']) && $imageFile['error'] === UPLOAD_ERR_OK) {
             $uploadResult = $this->uploadImage($imageFile);
             if (strpos($uploadResult, 'uploads/') === 0) {
                 $imagePath = $uploadResult;
             } else {
                 return ['success' => false, 'message' => $uploadResult];
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

         // Insert book listing with payment accounts
         $sql = "INSERT INTO books (user_id, title, author, department, course, description,
         condition_type, exchange_type, price, image_path, file_path,
         payment_account_1_type, payment_account_1_number, payment_account_1_holder,
         payment_account_2_type, payment_account_2_number, payment_account_2_holder,
         payment_account_3_type, payment_account_3_number, payment_account_3_holder,
         status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

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
             $filePath,
             $data['payment_account_1_type'] ?? null,
             $data['payment_account_1_number'] ?? null,
             $data['payment_account_1_holder'] ?? null,
             $data['payment_account_2_type'] ?? null,
             $data['payment_account_2_number'] ?? null,
             $data['payment_account_2_holder'] ?? null,
             $data['payment_account_3_type'] ?? null,
             $data['payment_account_3_number'] ?? null,
             $data['payment_account_3_holder'] ?? null
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
    public function uploadImage($file)
    {
        // Allowed MIME types and extensions
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return 'Image file is too large. Maximum size is 5MB.';
        }

        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return 'Invalid file extension. Allowed: JPG, PNG, GIF.';
        }

        // Verify actual MIME type using finfo (not client-provided type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return 'Invalid file type. Only image files are allowed.';
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

        return 'Failed to upload image file.';
    }

    /**
     * Upload document files (PDF, DOC, DOCX)
     */
    public function uploadDocument($file)
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
     * Get payment accounts for a book
     */
    public function getPaymentAccounts($bookId)
    {
        $sql = "SELECT payment_account_1_type, payment_account_1_number, payment_account_1_holder,
                       payment_account_2_type, payment_account_2_number, payment_account_2_holder,
                       payment_account_3_type, payment_account_3_number, payment_account_3_holder
                FROM books WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookId]);
        $result = $stmt->fetch();

        if (!$result) {
            return [];
        }

        $accounts = [];
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($result["payment_account_{$i}_type"]) && !empty($result["payment_account_{$i}_number"])) {
                $accounts[] = [
                    'type' => $result["payment_account_{$i}_type"],
                    'number' => $result["payment_account_{$i}_number"],
                    'holder' => $result["payment_account_{$i}_holder"] ?? ''
                ];
            }
        }

        return $accounts;
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
                description = ?, condition_type = ?, exchange_type = ?, price = ?, image_path = ?, file_path = ?,
                payment_account_1_type = ?, payment_account_1_number = ?, payment_account_1_holder = ?,
                payment_account_2_type = ?, payment_account_2_number = ?, payment_account_2_holder = ?,
                payment_account_3_type = ?, payment_account_3_number = ?, payment_account_3_holder = ?
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
            $data['image_path'] ?? null,
            $data['file_path'] ?? null,
            $data['payment_account_1_type'] ?? null,
            $data['payment_account_1_number'] ?? null,
            $data['payment_account_1_holder'] ?? null,
            $data['payment_account_2_type'] ?? null,
            $data['payment_account_2_number'] ?? null,
            $data['payment_account_2_holder'] ?? null,
            $data['payment_account_3_type'] ?? null,
            $data['payment_account_3_number'] ?? null,
            $data['payment_account_3_holder'] ?? null,
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
