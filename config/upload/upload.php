<?php
// config/upload.php

return [
    'upload_dir' => __DIR__ . '/../../public/uploads', // Absolute path to the upload directory
    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], // Allowed file extensions
    'max_size' => 5 * 1024 * 1024, // 5 MB in bytes
    'max_width' => 1920, // Max width for images
    'max_height' => 1080, // Max height for images
];
