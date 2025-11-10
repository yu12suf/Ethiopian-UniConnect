<?php

class Attachment
{
    private $db;
    private $uploadDir;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = __DIR__ . '/../uploads/chats';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Store an uploaded file from $_FILES and create an attachments row.
     * Returns attachment id on success or false on failure.
     */
    public function storeUpload(array $file, int $userId)
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Basic validation
        $maxSize = 10 * 1024 * 1024; // 10 MB per file default
        if ($file['size'] > $maxSize) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'audio/mpeg',
            'audio/ogg',
            'audio/wav',
            'video/mp4',
            'video/webm',
            'application/pdf',
            'application/zip',
            'application/octet-stream'
        ];
        // Allow other types but be conservative (you can extend this list)
        if (!in_array($mime, $allowed)) {
            // still proceed but mark as octet-stream
            $mime = $mime ?? 'application/octet-stream';
        }

        // sanitize filename
        $original = basename($file['name']);
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $uniq = bin2hex(random_bytes(8));
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', substr($original, 0, 200));
        $filename = $uniq . '_' . $safeName;
        $destPath = $this->uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return false;
        }

        // Insert into DB
        $sql = 'INSERT INTO attachments (user_id, filename, mime, size, path, created_at) VALUES (?, ?, ?, ?, ?, NOW())';
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([$userId, $original, $mime, $file['size'], 'uploads/chats/' . $filename]);
        if ($ok) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM attachments WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
