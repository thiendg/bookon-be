<?php
/**
 * User Model
 * Handles user CRUD operations
 */

class User {
    private $conn;
    private $table = 'users';

    // User properties - matching database schema
    public $id;
    public $role_id;
    public $full_name;
    public $email;
    public $password_hash;
    public $phone_number;
    public $address;
    public $avatar_url;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new user
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET email = :email, 
                      password_hash = :password_hash, 
                      full_name = :full_name,
                      phone_number = :phone_number,
                      address = :address,
                      avatar_url = :avatar_url,
                      role_id = :role_id,
                      status = :status,
                      created_at = :created_at,
                      updated_at = :updated_at";

        $stmt = $this->conn->prepare($query);

        // Hash password if not already hashed
        if (!password_get_info($this->password_hash)['algo']) {
            $this->password_hash = password_hash($this->password_hash, PASSWORD_BCRYPT);
        }
        
        $currentTime = time();

        // Bind values
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':phone_number', $this->phone_number);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':avatar_url', $this->avatar_url);
        $stmt->bindParam(':role_id', $this->role_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':created_at', $currentTime);
        $stmt->bindParam(':updated_at', $currentTime);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->created_at = $currentTime;
            $this->updated_at = $currentTime;
            return true;
        }

        return false;
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->role_id = $row['role_id'];
            $this->full_name = $row['full_name'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->phone_number = $row['phone_number'];
            $this->address = $row['address'];
            $this->avatar_url = $row['avatar_url'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->role_id = $row['role_id'];
            $this->full_name = $row['full_name'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->phone_number = $row['phone_number'];
            $this->address = $row['address'];
            $this->avatar_url = $row['avatar_url'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    /**
     * Update user profile
     */
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET full_name = :full_name,
                      phone_number = :phone_number,
                      address = :address,
                      avatar_url = :avatar_url,
                      updated_at = :updated_at 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $currentTime = time();

        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':phone_number', $this->phone_number);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':avatar_url', $this->avatar_url);
        $stmt->bindParam(':updated_at', $currentTime);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            $this->updated_at = $currentTime;
            return true;
        }

        return false;
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Verify password
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Update user status
     */
    public function updateStatus($status) {
        $query = "UPDATE " . $this->table . " 
                  SET status = :status, 
                      updated_at = :updated_at 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $currentTime = time();

        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':updated_at', $currentTime);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            $this->status = $status;
            $this->updated_at = $currentTime;
            return true;
        }

        return false;
    }

    /**
     * Mark email as verified (change status to active)
     */
    public function markEmailAsVerified() {
        return $this->updateStatus('active');
    }

    /**
     * Update password
     */
    public function updatePassword($newPassword) {
        $query = "UPDATE " . $this->table . " 
                  SET password_hash = :password_hash, 
                      updated_at = :updated_at 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $currentTime = time();

        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->bindParam(':updated_at', $currentTime);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            $this->password_hash = $hashedPassword;
            $this->updated_at = $currentTime;
            return true;
        }

        return false;
    }

    /**
     * Check if email is verified (status is active)
     */
    public function isEmailVerified() {
        return $this->status === 'active';
    }

    /**
     * Check if user is active
     */
    public function isActive() {
        return $this->status === 'active';
    }

    /**
     * Check if user is banned
     */
    public function isBanned() {
        return $this->status === 'banned';
    }

    /**
     * Get user data as array (without password)
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'avatar_url' => $this->avatar_url,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>