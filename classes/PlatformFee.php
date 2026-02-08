<?php

/**
 * PlatformFee Class - Handles platform fee payments and verification
 */

class PlatformFee
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Check if user has approved platform fee payment
     */
    public function hasPaidFee($userId)
    {
        $stmt = $this->db->prepare("SELECT id FROM platform_fees WHERE user_id = ? AND status = 'approved' LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get user's platform fee status
     */
    public function getUserFeeStatus($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM platform_fees WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Submit platform fee payment proof
     */
    public function submitPaymentProof($userId, $proofImage)
    {
        // Check if user already has a pending or approved payment
        $existing = $this->getUserFeeStatus($userId);
        if ($existing && $existing['status'] === 'approved') {
            return ['success' => false, 'message' => 'You have already paid the platform fee.'];
        }

        // Handle image upload
        $imagePath = null;
        if (!empty($proofImage) && isset($proofImage['error']) && $proofImage['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/transactions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'proof_' . uniqid() . '_' . time() . '.' . strtolower(pathinfo($proofImage['name'], PATHINFO_EXTENSION));
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($proofImage['tmp_name'], $targetPath)) {
                $imagePath = $targetPath;
            } else {
                return ['success' => false, 'message' => 'Failed to upload proof image.'];
            }
        } else {
            return ['success' => false, 'message' => 'Proof image is required.'];
        }

        // Insert or update payment record
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE platform_fees SET proof_image = ?, status = 'pending', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$imagePath, $existing['id']]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO platform_fees (user_id, amount, proof_image, status) VALUES (?, 10.00, ?, 'pending')");
            $stmt->execute([$userId, $imagePath]);
        }

        return ['success' => true, 'message' => 'Payment proof submitted successfully. Please wait for admin approval.'];
    }

    /**
     * Get all pending platform fee payments (for admin)
     */
    public function getPendingPayments()
    {
        $stmt = $this->db->prepare("
            SELECT pf.*, u.full_name, u.email
            FROM platform_fees pf
            JOIN users u ON pf.user_id = u.id
            WHERE pf.status = 'pending'
            ORDER BY pf.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Approve platform fee payment
     */
    public function approvePayment($paymentId, $adminId, $notes = null)
    {
        $stmt = $this->db->prepare("UPDATE platform_fees SET status = 'approved', admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$notes, $paymentId]);

        // Log admin action
        $this->logAdminAction($adminId, 'approve_platform_fee', $paymentId, 'Approved platform fee payment');

        return $stmt->rowCount() > 0;
    }

    /**
     * Reject platform fee payment
     */
    public function rejectPayment($paymentId, $adminId, $notes = null)
    {
        $stmt = $this->db->prepare("UPDATE platform_fees SET status = 'rejected', admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$notes, $paymentId]);

        // Log admin action
        $this->logAdminAction($adminId, 'reject_platform_fee', $paymentId, 'Rejected platform fee payment');

        return $stmt->rowCount() > 0;
    }

    /**
     * Get payment details by ID
     */
    public function getPaymentById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT pf.*, u.full_name, u.email
            FROM platform_fees pf
            JOIN users u ON pf.user_id = u.id
            WHERE pf.id = ?
        ");
        $stmt->execute([$paymentId]);
        return $stmt->fetch();
    }

    /**
     * Log admin action
     */
    private function logAdminAction($adminId, $action, $targetId, $details)
    {
        $stmt = $this->db->prepare("INSERT INTO admin_logs (admin_id, action, target_id, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $targetId, $details]);
    }
}