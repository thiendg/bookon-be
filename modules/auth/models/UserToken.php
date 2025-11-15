<?php
/**
 * UserToken Model
 * Manages one-time use tokens (email verification, password reset)
 */

class UserToken {
    private $conn;
    private $table = 'user_tokens';

    public $id;
    public $user_id;
    public $token_hash;
    public $type;
    public $expires_at;
    public $created_at;

    // Token types
    const TYPE_EMAIL_VERIFICATION = 'email_verification';
    const TYPE_PASSWORD_RESET = 'password_reset';

    // Token expiration times (in seconds)
    const EMAIL_TOKEN_EXPIRY = 86400; // 24 hours
    const PASSWORD_TOKEN_EXPIRY = 3600; // 1 hour

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new token
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET user_id = :user_id, 
                      token_hash = :token_hash, 
                      type = :type,
                      expires_at = :expires_at,
                      created_at = :created_at";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':token_hash', $this->token_hash);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':expires_at', $this->expires_at);
        $stmt->bindParam(':created_at', $this->created_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Find token by hash and type
     */
    public function findByHashAndType($tokenHash, $type) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE token_hash = :token_hash 
                  AND type = :type 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->token_hash = $row['token_hash'];
            $this->type = $row['type'];
            $this->expires_at = $row['expires_at'];
            $this->created_at = $row['created_at'];
            return true;
        }

        return false;
    }

    /**
     * Check if token is expired
     */
    public function isExpired() {
        return time() > $this->expires_at;
    }

    /**
     * Delete token by ID
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    /**
     * Delete all tokens for a user by type
     */
    public function deleteByUserIdAndType($userId, $type) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id AND type = :type";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':type', $type);
        return $stmt->execute();
    }

    /**
     * Clean up expired tokens
     */
    public function cleanExpired() {
        $currentTime = time();
        $query = "DELETE FROM " . $this->table . " WHERE expires_at < :current_time";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':current_time', $currentTime);
        return $stmt->execute();
    }

    /**
     * Generate and save email verification token
     */
    public function generateEmailVerificationToken($userId) {
        require_once __DIR__ . '/../../../utils/token_generator.php';
        
        // Delete old tokens
        $this->deleteByUserIdAndType($userId, self::TYPE_EMAIL_VERIFICATION);

        // Generate new token
        $plainToken = TokenGenerator::generate(32);
        
        $this->user_id = $userId;
        $this->token_hash = TokenGenerator::hash($plainToken);
        $this->type = self::TYPE_EMAIL_VERIFICATION;
        $this->expires_at = time() + self::EMAIL_TOKEN_EXPIRY;
        $this->created_at = time();

        if ($this->create()) {
            return $plainToken;
        }

        return false;
    }

    /**
     * Generate and save password reset token
     */
    public function generatePasswordResetToken($userId) {
        require_once __DIR__ . '/../../../utils/token_generator.php';
        
        // Delete old tokens
        $this->deleteByUserIdAndType($userId, self::TYPE_PASSWORD_RESET);

        // Generate new token
        $plainToken = TokenGenerator::generate(32);
        
        $this->user_id = $userId;
        $this->token_hash = TokenGenerator::hash($plainToken);
        $this->type = self::TYPE_PASSWORD_RESET;
        $this->expires_at = time() + self::PASSWORD_TOKEN_EXPIRY;
        $this->created_at = time();

        if ($this->create()) {
            return $plainToken;
        }

        return false;
    }

    /**
     * Verify and consume token
     */
    public function verifyAndConsume($plainToken, $type) {
        require_once __DIR__ . '/../../../utils/token_generator.php';
        
        $tokenHash = TokenGenerator::hash($plainToken);
        
        if (!$this->findByHashAndType($tokenHash, $type)) {
            return false;
        }

        if ($this->isExpired()) {
            $this->delete();
            return false;
        }

        // Token is valid, delete it (one-time use)
        $userId = $this->user_id;
        $this->delete();
        
        return $userId;
    }
}
?>