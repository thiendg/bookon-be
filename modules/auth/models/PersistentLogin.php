<?php
/**
 * PersistentLogin Model
 * Manages "Remember Me" functionality using selector/validator pattern
 */

class PersistentLogin {
    private $conn;
    private $table = 'persistent_logins';

    public $id;
    public $user_id;
    public $selector;
    public $validator_hash;
    public $expires_at;
    public $created_at;

    // Remember me expiration (30 days)
    const EXPIRY_TIME = 2592000; // 30 days in seconds

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new persistent login
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET user_id = :user_id, 
                      selector = :selector, 
                      validator_hash = :validator_hash,
                      expires_at = :expires_at,
                      created_at = :created_at";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':selector', $this->selector);
        $stmt->bindParam(':validator_hash', $this->validator_hash);
        $stmt->bindParam(':expires_at', $this->expires_at);
        $stmt->bindParam(':created_at', $this->created_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Find persistent login by selector
     */
    public function findBySelector($selector) {
        $query = "SELECT * FROM " . $this->table . " WHERE selector = :selector LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':selector', $selector);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->selector = $row['selector'];
            $this->validator_hash = $row['validator_hash'];
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
     * Delete persistent login by ID
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    /**
     * Delete persistent login by selector
     */
    public function deleteBySelector($selector) {
        $query = "DELETE FROM " . $this->table . " WHERE selector = :selector";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':selector', $selector);
        return $stmt->execute();
    }

    /**
     * Delete all persistent logins for a user
     */
    public function deleteByUserId($userId) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
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
     * Generate and save persistent login token
     */
    public function generateToken($userId) {
        require_once __DIR__ . '/../../../utils/token_generator.php';
        
        // Generate selector and validator
        $tokens = TokenGenerator::generatePersistentToken();
        
        $this->user_id = $userId;
        $this->selector = $tokens['selector'];
        $this->validator_hash = $tokens['validator_hash'];
        $this->expires_at = time() + self::EXPIRY_TIME;
        $this->created_at = time();

        if ($this->create()) {
            // Return combined token for cookie
            return TokenGenerator::combinePersistentToken($tokens['selector'], $tokens['validator']);
        }

        return false;
    }

    /**
     * Verify persistent login token
     */
    public function verify($combinedToken) {
        require_once __DIR__ . '/../../../utils/token_generator.php';
        
        // Split token into selector and validator
        $parts = TokenGenerator::splitPersistentToken($combinedToken);
        
        if (!$parts) {
            return false;
        }

        // Find by selector
        if (!$this->findBySelector($parts['selector'])) {
            return false;
        }

        // Check if expired
        if ($this->isExpired()) {
            $this->delete();
            return false;
        }

        // Verify validator
        if (!TokenGenerator::verify($parts['validator'], $this->validator_hash)) {
            // Possible attack - delete all tokens for this user
            $this->deleteByUserId($this->user_id);
            return false;
        }

        return $this->user_id;
    }

    /**
     * Refresh persistent login token (regenerate after successful verification)
     */
    public function refresh($userId, $oldSelector) {
        // Delete old token
        $this->deleteBySelector($oldSelector);
        
        // Generate new token
        return $this->generateToken($userId);
    }
}
?>