<?php
// Include CORS configuration early
require_once __DIR__ . '/../../../utils/cors.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log that script started
error_log("read.php: Script started");

// Set universal headers (excluding CORS which is handled by cors.php)
header("Content-Type: application/json; charset=UTF-8");

// Include dependencies
require_once __DIR__ . '/../controllers/faq-controller.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // Include AuthMiddleware

// Instantiate controller
$faqController = new FaqController();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the ID from the query string, which is set by the main API router
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Apply middleware based on method
switch ($method) {
    case 'GET':
        // FAQs might be publicly readable, but for consistency with other modules, require login for now
 

        break;
    case 'POST':
        AuthMiddleware::requirePermission('faqs:manage'); // Creating FAQs requires specific permission
        break;
    case 'PUT':
        AuthMiddleware::requirePermission('faqs:manage'); // Updating FAQs requires specific permission
        break;
    case 'DELETE':
        AuthMiddleware::requirePermission('faqs:manage'); // Deleting FAQs requires specific permission
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
