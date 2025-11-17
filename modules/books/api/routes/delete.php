<?php
/**
 * Book Delete API
 * Deletes a book by ID.
 */

// This file is included by modules/books/api/index.php
// $bookController is already instantiated there.
// $id is also available.

if ($id) {
    $bookController->deleteBook($id);
} else {
    Response::error('Book ID is required for deletion.', 400);
}
