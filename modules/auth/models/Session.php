<?php
/**
 * Session Model
 * Manages database sessions
 */

class Session {
    private $conn;
    private $table = 'sessions';

    public $session_id;
    public $user_id;
    public $ip_address;
    public $user_agent;
    public $payload;
    public $last_activity;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create or update session
     */
    public function save() {
        $query = "INSERT INTO " . $this->table . " 
                  (session_id, user_id, ip_address, user_agent, payload, last_activity)
                  VALUES (:session_id, :user_id, :ip_address, :user_agent, :payload, :last_activity)
                  ON DUPLICATE KEY UPDATE
                  user_id = VALUES(user_id),
                  ip_address = VALUES(ip_address),
                  user_agent = VALUES(user_agent),
                  payload = VALUES(payload),
                  last_activity = VALUES(last_activity)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':session_id', $this->session_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':ip_address', $this->ip_address);
        $stmt->bindParam(':user_agent', $this->user_agent);
        $stmt->bindParam(':payload', $this->payload);
        $stmt->bindParam(':last_activity', $this->last_activity);

        return $stmt->execute();
    }

    /**
     * Find session by session ID
     */
    public function findBySessionId($sessionId) {
        $query = "SELECT * FROM " . $this->table . " WHERE session_id = :session_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->session_id = $row['session_id'];
            $this->user_id = $row['user_id'];
            $this->ip_address = $row['ip_address'];
            $this->user_agent = $row['user_agent'];
            $this->payload = $row['payload'];
            $this->last_activity = $row['last_activity'];
            return true;
        }

        return false;
    }

    /**
     * Delete session by session ID
     */
    public function deleteBySessionId($sessionId) {
        $query = "DELETE FROM " . $this->table . " WHERE session_id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        return $stmt->execute();
    }

    /**
     * Delete all sessions for a user
     */
    public function deleteByUserId($userId) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    /**
     * Clean up expired sessions (older than specified time)
     */
    public function cleanExpired($expirationTime = 86400) {
        $expiredTime = time() - $expirationTime;
        $query = "DELETE FROM " . $this->table . " WHERE last_activity < :expired_time";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':expired_time', $expiredTime);
        return $stmt->execute();
    }

    /**
     * Get all active sessions for a user
     */
    public function getByUserId($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY last_activity DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>