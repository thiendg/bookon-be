<?php
/**
 * File Upload API
 * Handles uploading of files (e.g., avatars, book images)
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // For authentication
require_once __DIR__ . '/../../../utils/file-uploader.php'; // File Uploader utility

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Ensure user is logged in (optional, depending on requirements)
// AuthMiddleware::requireLogin();
// AuthMiddleware::requirePermission('files:upload'); // Example permission check

// Check if any file was uploaded
if (empty($_FILES) || !isset($_FILES['file'])) {
    Response::error('No file uploaded or invalid file input name (expected "file").', 400);
}

$fileUploader = new FileUploader();
$uploadedFilePath = $fileUploader->upload($_FILES['file']);

if ($uploadedFilePath) {
    Response::success(['file_path' => $uploadedFilePath], 'File uploaded successfully.');
} else {
    Response::error('File upload failed: ' . implode(', ', $fileUploader->getErrors()), 400);
}
