<?php
// Include CORS configuration early
require_once __DIR__ . '/../../../utils/cors.php';

// Set universal headers (excluding CORS which is handled by cors.php)
header("Content-Type: application/json; charset=UTF-8");

// Include dependencies
require_once __DIR__ . '/../controllers/book-controller.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // Include AuthMiddleware

// Instantiate controller
$bookController = new BookController();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the ID from the query string, which is set by the main API router
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}
// Apply middleware based on method
switch ($method) {
    case 'GET':
        // Check for special 'select' action
        if (isset($_GET['action']) && $_GET['action'] === 'select') {
            // Select options for books are publicly readable for dropdowns
            $routePath = __DIR__ . '/routes/select.php';
            require $routePath; // Require the select file and exit
            exit();
        }
        // Public access for reading books is allowed.
        // AuthMiddleware::requireLogin();
        // AuthMiddleware::requirePermission('books:read');
        break;
    case 'POST':
        AuthMiddleware::requirePermission('books:create'); // Creating books requires specific permission
        break;
    case 'PUT':
        AuthMiddleware::requirePermission('books:update'); // Updating books requires specific permission
        break;
    case 'DELETE':
        AuthMiddleware::requirePermission('books:delete'); // Deleting books requires specific permission
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
