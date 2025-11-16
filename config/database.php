<?php
/**
 * Database Configuration
 * Establishes connection to MySQL database
 */

class Database {
    // Database credentials
    private $host = 'localhost';
    private $db_name = 'web_bansach';  // Change to your database name
    private $username = 'root';          // Change to your MySQL username
    private $password = '';              // Change to your MySQL password
    private $charset = 'utf8mb4';
    
    public $conn;

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }
}
?>