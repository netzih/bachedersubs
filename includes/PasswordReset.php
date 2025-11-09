<?php
/**
 * PasswordReset Class
 * Handles password reset token generation and validation
 */

class PasswordReset {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a password reset token for a user
     */
    public function createResetToken($email) {
        // Find user by email
        $stmt = $this->db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Don't reveal if email exists - return true anyway for security
            return ['success' => true, 'token' => null];
        }

        // Generate secure random token
        $token = bin2hex(random_bytes(32));

        // Token expires in 1 hour
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Invalidate any existing tokens for this user
        $stmt = $this->db->prepare("UPDATE password_resets SET used = TRUE WHERE user_id = ? AND used = FALSE");
        $stmt->execute([$user['id']]);

        // Insert new token
        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $token, $expiresAt]);

        return [
            'success' => true,
            'token' => $token,
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_email' => $email
        ];
    }

    /**
     * Validate a reset token
     */
    public function validateToken($token) {
        $stmt = $this->db->prepare("
            SELECT pr.id, pr.user_id, pr.expires_at, pr.used, u.email, u.name
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ?
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            return ['valid' => false, 'message' => 'Invalid reset token'];
        }

        if ($reset['used']) {
            return ['valid' => false, 'message' => 'This reset link has already been used'];
        }

        if (strtotime($reset['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'This reset link has expired'];
        }

        return [
            'valid' => true,
            'user_id' => $reset['user_id'],
            'email' => $reset['email'],
            'name' => $reset['name']
        ];
    }

    /**
     * Reset password using token
     */
    public function resetPassword($token, $newPassword) {
        // Validate token
        $validation = $this->validateToken($token);
        if (!$validation['valid']) {
            return $validation;
        }

        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user password
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $validation['user_id']]);

        // Mark token as used
        $stmt = $this->db->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
        $stmt->execute([$token]);

        return [
            'success' => true,
            'message' => 'Password reset successfully'
        ];
    }

    /**
     * Clean up expired tokens (can be run via cron)
     */
    public function cleanupExpiredTokens() {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = TRUE");
        return $stmt->execute();
    }
}
