<?php
require_once __DIR__ . '/../../../utils/cors.php';

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../controllers/book-controller.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php';

$bookController = new BookController();

$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}

switch ($method) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'select') {
            $routePath = __DIR__ . '/routes/select.php';
            require $routePath;
            exit();
        }
        break;
    case 'POST':
        AuthMiddleware::requirePermission('books:create');
        break;
    case 'PUT':
        AuthMiddleware::requirePermission('books:update');
        break;
    case 'DELETE':
        AuthMiddleware::requirePermission('books:delete');
        break;
    case 'OPTIONS':
        exit();
    default:
        Response::error('Method Not Allowed', 405);
        exit();
}

$routePath = __DIR__ . '/routes/' . strtolower($method) . '.php';

if ($method === 'GET' && $id === null) {
    $routePath = __DIR__ . '/routes/get-all.php';
} elseif ($method === 'GET' && $id !== null) {
    $routePath = __DIR__ . '/routes/get.php';
}


if (file_exists($routePath)) {
    require $routePath;
} else {
    Response::error('Method Not Allowed or Route Not Found', 405);
}
