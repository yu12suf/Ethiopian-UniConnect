<?php

class PasswordReset {
    private $db;
    private $user;

    public function __construct() {
        $this->db = DB::getInstance();
        $this->user = new User();
    }

    public function requestReset($email) {
        $user_data = $this->db->get('users', ['email', '=', $email])->first();

        if ($user_data) {
            $user_id = $user_data->id;
            $token = bin2hex(random_bytes(16)); // Generate a 32-character token
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

            // Delete any existing tokens for this user
            $this->db->delete('password_resets', ['user_id', '=', $user_id]);

            // Store the new token
            $this->db->insert('password_resets', [
                'user_id' => $user_id,
                'token' => $token,
                'expires_at' => $expires
            ]);

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
        $reset_data = $this->db->get('password_resets', ['token', '=', $token])->first();

        if ($reset_data && strtotime($reset_data->expires_at) > time()) {
            $user_id = $reset_data->user_id;

            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update user's password
            $this->db->update('users', $user_id, ['password' => $hashed_password]);

            // Invalidate the token
            $this->db->delete('password_resets', ['id', '=', $reset_data->id]);

            return ['success' => true, 'message' => 'Your password has been reset successfully.'];
        }

        return ['success' => false, 'message' => 'Invalid or expired password reset token.'];
    }

    public function validateToken($token) {
        $reset_data = $this->db->get('password_resets', ['token', '=', $token])->first();
        return ($reset_data && strtotime($reset_data->expires_at) > time());
    }
}
