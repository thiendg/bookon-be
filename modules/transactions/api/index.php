<?php
// Set universal headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include dependencies
require_once __DIR__ . '/../controllers/transaction-controller.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // Include AuthMiddleware

// Instantiate controller
$transactionController = new TransactionController();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the ID from the query string, which is set by the main API router
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Apply middleware based on method
switch ($method) {
    case 'GET':
        AuthMiddleware::requireLogin(); 
        AuthMiddleware::requirePermission('transactions:read'); // Specific permission for reading transactions
        break;
    case 'POST':
        AuthMiddleware::requireLogin(); 
        AuthMiddleware::requirePermission('transactions:manage'); // Creating transactions requires specific permission
        break;
    case 'PUT':
        AuthMiddleware::requireLogin(); 
        AuthMiddleware::requirePermission('transactions:manage'); // Updating transactions requires specific permission
        break;
    case 'DELETE':
        AuthMiddleware::requireLogin(); 
        AuthMiddleware::requirePermission('transactions:manage'); // Deleting transactions requires specific permission
        break;
    case 'OPTIONS':
        // Allow preflight requests without authentication
        exit();
    default:
        // Method not allowed
        Response::error('Method Not Allowed', 405);
        exit();
}

// Route the request to the appropriate file
$routePath = __DIR__ . '/routes/' . strtolower($method) . '.php';

// Special handling for GET requests: if no ID, it's a list request
if ($method === 'GET' && $id === null) {
    $routePath = __DIR__ . '/routes/get-all.php'; // Custom file for listing all
} elseif ($method === 'GET' && $id !== null) {
    $routePath = __DIR__ . '/routes/get.php'; // Custom file for getting by ID
}


if (file_exists($routePath)) {
    require $routePath;
} else {
    Response::error('Method Not Allowed or Route Not Found', 405);
}
