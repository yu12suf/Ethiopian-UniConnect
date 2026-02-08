<?php

class PasswordReset {
    private $db;
    private $user;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->user = new User();
    }

    public function requestReset($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user_data = $stmt->fetch();

        if ($user_data) {
            $user_id = $user_data['id'];
            $token = bin2hex(random_bytes(16)); // Generate a 32-character token
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

            // Delete any existing tokens for this user
            $sql = "DELETE FROM password_resets WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);

            // Store the new token
            $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $token, $expires]);

            // In a real application, you would send an email here.
            // For demonstration, we'll just return the reset link.
            $reset_link = site_url('views/auth/reset_password.php?token=' . $token);

            $subject = 'Password Reset Request for UniConnect';
            $body = '<p>Hello,</p>';
            $body .= '<p>You have requested to reset your password for your UniConnect account.</p>';
            $body .= '<p>Please click on the following link to reset your password: <a href="' . $reset_link . '">' . $reset_link . '</a></p>';
            $body .= '<p>This link will expire in 1 hour.</p>';
            $body .= '<p>If you did not request a password reset, please ignore this email.</p>';
            $body .= '<p>Regards,<br>UniConnect Team</p>';

            send_email($email, $subject, $body);

            return ['success' => true, 'message' => 'Password reset link sent to your email.', 'reset_link' => $reset_link];
        }

        return ['success' => false, 'message' => 'If an account with that email exists, a password reset link has been sent.'];
    }

    public function resetPassword($token, $new_password) {
        $sql = "SELECT id, user_id, expires_at FROM password_resets WHERE token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $reset_data = $stmt->fetch();

        if ($reset_data && strtotime($reset_data['expires_at']) > time()) {
            $user_id = $reset_data['user_id'];

            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update user's password
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hashed_password, $user_id]);

            // Invalidate the token
            $sql = "DELETE FROM password_resets WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$reset_data['id']]);

            return ['success' => true, 'message' => 'Your password has been reset successfully.'];
        }

        return ['success' => false, 'message' => 'Invalid or expired password reset token.'];
    }

    public function validateToken($token) {
        $sql = "SELECT expires_at FROM password_resets WHERE token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $reset_data = $stmt->fetch();
        return ($reset_data && strtotime($reset_data['expires_at']) > time());
    }
}
