<?php
/**
 * Book Update API
 * Handles updating an existing book, including multiple image uploads.
 */

// This file is included by modules/books/api/index.php
// $bookController is already instantiated there.
// $id is also available.

if ($id) {
    $bookController->updateBook($id);
} else {
    Response::error('Book ID is required for update.', 400);
}

