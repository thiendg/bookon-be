<?php
require_once __DIR__ . '/../config/upload/upload.php'; // Load upload configuration

class FileUploader
{
    private $uploadConfig;
    private $errors = [];

    public function __construct()
    {
        $this->uploadConfig = require __DIR__ . '/../config/upload/upload.php';
        // Ensure the base upload directory exists
        if (!is_dir($this->uploadConfig['upload_dir'])) {
            mkdir($this->uploadConfig['upload_dir'], 0777, true);
        }
    }

    /**
     * Uploads a single file.
     *
     * @param array $file The $_FILES array entry for the file (e.g., $_FILES['avatar']).
     * @param string $subDirectory An optional subdirectory within the main upload directory.
     * @return string|false The relative path to the uploaded file on success, or false on failure.
     */
    public function upload(array $file, string $subDirectory = ''): string|false
    {
        $this->errors = []; // Reset errors for new upload attempt

        // Basic file upload checks
        if (!isset($file['error']) || is_array($file['error'])) {
            $this->errors[] = 'Invalid upload parameters.';
            return false;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $this->errors[] = 'No file sent.';
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors[] = 'Exceeded filesize limit.';
                return false;
            default:
                $this->errors[] = 'Unknown upload error.';
                return false;
        }

        // Validate file size
        if ($file['size'] > $this->uploadConfig['max_size']) {
            $this->errors[] = 'File size exceeds limit (' . ($this->uploadConfig['max_size'] / (1024 * 1024)) . ' MB).';
            return false;
        }

        // Get file extension
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $extension = array_search(
            $mimeType,
            [
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ],
            true
        );

        if ($extension === false || !in_array($extension, $this->uploadConfig['allowed_types'])) {
            $this->errors[] = 'Invalid file format. Allowed types: ' . implode(', ', $this->uploadConfig['allowed_types']) . '.';
            return false;
        }

        // Validate image dimensions (if it's an image)
        if (str_starts_with($mimeType, 'image/')) {
            list($width, $height) = getimagesize($file['tmp_name']);
            if ($width > $this->uploadConfig['max_width'] || $height > $this->uploadConfig['max_height']) {
                $this->errors[] = 'Image dimensions exceed limit (' . $this->uploadConfig['max_width'] . 'x' . $this->uploadConfig['max_height'] . ').';
                return false;
            }
        }

        // Determine target directory
        $targetDir = $this->uploadConfig['upload_dir'];
        if (!empty($subDirectory)) {
            $targetDir .= '/' . trim($subDirectory, '/');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
        }

        // Generate unique filename
        $fileName = sprintf('%s.%s', sha1_file($file['tmp_name']), $extension);
        $filePath = $targetDir . '/' . $fileName;

        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->errors[] = 'Failed to move uploaded file.';
            return false;
        }

        // Return relative path from public/uploads
        $relativePath = str_replace($this->uploadConfig['upload_dir'], '', $filePath);
        return 'public/uploads' . $relativePath;
    }

    /**
     * Uploads multiple files from a single $_FILES entry (e.g., $_FILES['images']).
     *
     * @param array $files The $_FILES array entry for multiple files.
     * @param string $subDirectory An optional subdirectory within the main upload directory.
     * @return array An array of relative paths to the uploaded files on success.
     *               If any file fails, it will be omitted from the success list, and errors will be stored.
     */
    public function uploadMultiple(array $files, string $subDirectory = ''): array
    {
        $uploadedPaths = [];
        $this->errors = []; // Reset errors for new upload attempt

        // Reorganize the $_FILES array for easier iteration if it's in the common format
        // (e.g., $_FILES['images']['name'][0], $_FILES['images']['type'][0]...)
        $fileArray = [];
        if (isset($files['name']) && is_array($files['name'])) {
            foreach ($files['name'] as $key => $name) {
                $fileArray[$key] = [
                    'name' => $name,
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key],
                ];
            }
        } else {
            // If it's not an array of files, treat it as a single file upload attempt
            // and let the single upload method handle it.
            $this->errors[] = 'Invalid multiple file upload parameters.';
            return [];
        }

        foreach ($fileArray as $file) {
            // Only attempt to upload if a file was actually provided and no critical error
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip if no file was selected for this input
            }
            
            $path = $this->upload($file, $subDirectory);
            if ($path) {
                $uploadedPaths[] = $path;
            } else {
                // Collect errors from individual file uploads
                $this->errors = array_merge($this->errors, $this->getErrors());
            }
        }

        return $uploadedPaths;
    }

    /**
     * Returns an array of errors from the last upload attempt.
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
