<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('log_errors', 1);

// // Log that script started
// error_log("read.php: Script started");
// header('Content-Type: application/json');

require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/app_session.php';
require_once __DIR__ . '/../../../config/database.php';

// Check authentication
if (!AppSession::isAuthenticated()) {
    Response::unauthorized();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT id, email, full_name, phone_number, address, avatar_url, status, created_at, updated_at
              FROM users 
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success($user, 'User Info retrieved successfully');
} catch (Exception $e) {
    Response::error('Failed to fetch users', 500);
}
?>