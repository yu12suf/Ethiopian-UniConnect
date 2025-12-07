<?php

/**
 * Request Class - Handles book exchange requests
 * Demonstrates: OOP, database relationships
 */

class Request
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new request
     */
    public function createRequest($requesterId, $bookId, $message, $borrowDays = null, $paymentProof = null)
    {
        $existingStmt = $this->db->prepare("SELECT * FROM requests WHERE requester_id = ? AND book_id = ? ORDER BY created_at DESC LIMIT 1");
        $existingStmt->execute([$requesterId, $bookId]);
        $existing = $existingStmt->fetch();

        $borrowDeadline = null;
        if ($borrowDays && is_numeric($borrowDays) && $borrowDays > 0) {
            $borrowDeadline = date('Y-m-d H:i:s', strtotime("+{$borrowDays} days"));
        }

        if ($existing && in_array($existing['status'], ['rejected', 'cancelled'], true)) {
            $upd = $this->db->prepare("UPDATE requests SET message = ?, status = 'pending', requested_borrow_days = ?, borrow_deadline = ?, updated_at = NOW() WHERE id = ?");
            if ($upd->execute([$message, $borrowDays, $borrowDeadline, $existing['id']])) {
                if ($paymentProof) {
                    $proofSql = "INSERT INTO payment_proofs (request_id, proof_image, uploaded_at) VALUES (?, ?, NOW())";
                    $proofStmt = $this->db->prepare($proofSql);
                    $proofStmt->execute([$existing['id'], $paymentProof]);
                }
                return ['success' => true, 'message' => 'Your previous request was rigected. It has been resubmitted with your new details.'];
            }
            return ['success' => false, 'message' => 'Failed to resubmit your request'];
        }



        $sql = "INSERT INTO requests (requester_id, book_id, message, status, requested_borrow_days, borrow_deadline, created_at)
                VALUES (?, ?, ?, 'pending', ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$requesterId, $bookId, $message, $borrowDays, $borrowDeadline])) {
            $requestId = $this->db->lastInsertId();

            // Handle payment proof for 'buy' requests
            if ($paymentProof) {
                $proofSql = "INSERT INTO payment_proofs (request_id, proof_image, uploaded_at)
                            VALUES (?, ?, NOW())";
                $proofStmt = $this->db->prepare($proofSql);
                $proofStmt->execute([$requestId, $paymentProof]);
            }

            return ['success' => true, 'message' => 'Request sent successfully'];
        }

        return ['success' => false, 'message' => 'Failed to send request'];
    }

    /**
     * Check if request already exists
     */
    private function requestExists($requesterId, $bookId)
    {
        $sql = "SELECT id FROM requests WHERE requester_id = ? AND book_id = ? AND status != 'cancelled'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requesterId, $bookId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get received requests for book owner
     */
    public function getReceivedRequests($userId)
    {
        $sql = "SELECT r.*, b.title as book_title, b.id as book_id, b.exchange_type, b.price,
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
    public function getSentRequests($userId)
    {
        $sql = "SELECT r.*, b.title as book_title, b.image_path, b.exchange_type, b.price,
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
    public function updateRequestStatus($requestId, $ownerId, $status, $borrowDays = null)
    {
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

        // Set borrow deadline if accepting borrow request
        $updateSql = "UPDATE requests SET status = ?";
        $params = [$status];
        if ($status === 'accepted' && $borrowDays && is_numeric($borrowDays) && $borrowDays > 0) {
            $borrowDeadline = date('Y-m-d H:i:s', strtotime("+{$borrowDays} days"));
            $updateSql .= ", borrow_deadline = ?";
            $params[] = $borrowDeadline;
        }
        $updateSql .= " WHERE id = ?";

        error_log("Attempting to update request ID: $requestId with status: $status. SQL: $updateSql, Params: " . json_encode(array_merge($params, [$requestId])));
        $stmt = $this->db->prepare($updateSql);
        if ($stmt->execute(array_merge($params, [$requestId]))) {
            error_log("Request ID: $requestId status updated to: $status. Success.");
            // Load request details to use in notifications and transaction handling
            try {
                $q = "SELECT r.id AS req_id, r.requester_id, r.book_id, r.message AS request_message, r.status AS current_status, b.title AS book_title, b.price, b.exchange_type, u.email AS owner_email, u.full_name AS owner_name, ru.email AS requester_email, ru.full_name AS requester_name FROM requests r JOIN books b ON r.book_id = b.id JOIN users u ON b.user_id = u.id JOIN users ru ON r.requester_id = ru.id WHERE r.id = ? LIMIT 1";
                $s = $this->db->prepare($q);
                $s->execute([$requestId]);
                $row = $s->fetch();

                if ($row) {
                    // When owner accepts a buy request, create a pending transaction so buyer can upload proof
                    if ($status === 'accepted' && ($row['exchange_type'] ?? '') === 'buy') {
                        try {
                            $ins = $this->db->prepare('INSERT INTO transactions (request_id, book_id, buyer_id, amount, status) VALUES (?, ?, ?, ?, ?)');
                            $ins->execute([$row['req_id'], $row['book_id'], $row['requester_id'], $row['price'] ?? null, 'pending']);
                        } catch (Exception $txEx) {
                            error_log('Failed to create pending transaction for request ' . $requestId . ': ' . $txEx->getMessage());
                        }

                        // Notify requester by email that their request was accepted and they should upload payment proof
                        $to = $row['requester_email'];
                        $subject = 'Your request for "' . $row['book_title'] . '" was accepted';
                        $body = '<p>Hi ' . htmlspecialchars($row['requester_name']) . ',</p>' .
                            '<p>Your request for the book "' . htmlspecialchars($row['book_title']) . '" has been accepted by ' . htmlspecialchars($row['owner_name']) . '.</p>' .
                            '<p>Please upload proof of payment (receipt) using your Requests page: <a href="' . site_url('views/dashboard/requests.php') . '">My Requests</a>.</p>' .
                            '<p>Once the owner verifies the payment, the file will be available for download.</p>' .
                            '<p>Thank you,<br>UniConnect</p>';
                        send_email($to, $subject, $body);
                    }

                    // When owner marks completed, update the transaction status to completed (if exists) and notify both parties
                    if ($status === 'completed') {
                        try {
                            $up = $this->db->prepare('UPDATE transactions SET status = ? WHERE request_id = ?');
                            $up->execute(['completed', $requestId]);
                        } catch (Exception $txEx) {
                            error_log('Failed to mark transaction completed for request ' . $requestId . ': ' . $txEx->getMessage());
                        }

                        // Notify requester that transaction is completed and files are available
                        $toReq = $row['requester_email'];
                        $subReq = 'Transaction completed for "' . $row['book_title'] . '"';
                        $bodyReq = '<p>Hi ' . htmlspecialchars($row['requester_name']) . ',</p>' .
                            '<p>Your transaction for the book "' . htmlspecialchars($row['book_title']) . '" has been marked as completed by the owner. You can now view and download the file (if attached).</p>' .
                            '<p><a href="' . site_url('views/public/book_detail.php?id=' . $row['book_id']) . '">View Book</a></p>' .
                            '<p>Thank you,<br>UniConnect</p>';
                        send_email($toReq, $subReq, $bodyReq);

                        // Notify owner confirming the completion
                        $toOwner = $row['owner_email'];
                        $subOwner = 'You marked transaction completed for "' . $row['book_title'] . '"';
                        $bodyOwner = '<p>Hi ' . htmlspecialchars($row['owner_name']) . ',</p>' .
                            '<p>You have marked the transaction for "' . htmlspecialchars($row['book_title']) . '" as completed.</p>' .
                            '<p>Request details: ' . nl2br(htmlspecialchars($row['request_message'])) . '</p>' .
                            '<p>Thank you,<br>UniConnect</p>';
                        send_email($toOwner, $subOwner, $bodyOwner);
                    }
                }
            } catch (Exception $ex) {
                error_log('Request post-update processing failed for ' . $requestId . ': ' . $ex->getMessage());
            }

            return ['success' => true, 'message' => 'Request ' . $status];
        }

        return ['success' => false, 'message' => 'Update failed'];
    }

    /**
     * Cancel request
     */
    public function cancelRequest($requestId, $requesterId)
    {
        $sql = "UPDATE requests SET status = 'cancelled' WHERE id = ? AND requester_id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$requestId, $requesterId])) {
            return ['success' => true, 'message' => 'Request cancelled'];
        }

        return ['success' => false, 'message' => 'Cancellation failed'];
    }

    /**
     * Check if a requester has an accepted request for a book
     */
    public function isRequestAccepted($requesterId, $bookId)
    {
        // Check request status and book exchange type. For 'buy' we require a completed transaction.
        $sql = "SELECT r.status, r.borrow_deadline, b.exchange_type FROM requests r JOIN books b ON r.book_id = b.id WHERE r.requester_id = ? AND r.book_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requesterId, $bookId]);
        $row = $stmt->fetch();
        if (!$row) return false;

        $status = $row['status'];
        $exchange = $row['exchange_type'] ?? 'donate';
        $borrowDeadline = $row['borrow_deadline'];

        if ($exchange === 'buy') {
            // For purchases, ensure there's a completed transaction for this request/book/buyer
            $t = $this->db->prepare("SELECT id FROM transactions WHERE book_id = ? AND buyer_id = ? AND status = 'completed' LIMIT 1");
            $t->execute([$bookId, $requesterId]);
            return $t->fetch() !== false;
        }

        if ($exchange === 'borrow') {
            // For borrow, check if accepted and deadline not passed
            if (in_array($status, ['accepted', 'completed'], true)) {
                if ($borrowDeadline && strtotime($borrowDeadline) < time()) {
                    return false; // Deadline passed, access revoked
                }
                return true;
            }
            return false;
        }

        // For donate, accepted or completed status is sufficient
        return in_array($status, ['accepted', 'completed'], true);
    }
}
