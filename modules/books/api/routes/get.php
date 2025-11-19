<?php
/**
 * Book Get API
 * Returns a single book by ID.
 */

// This file is included by modules/books/api/index.php
// $bookController is already instantiated there.
// $id is also available.

if ($id) {
    $bookController->getBook($id);
} else {
    Response::error('Book ID is required.', 400);
}
