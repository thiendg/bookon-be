<?php
/**
 * Book List API
 * Returns a list of books, with optional filtering and pagination.
 */

// This file is included by modules/books/api/index.php
// $bookController is already instantiated there.
// $id is also available if passed.

$bookController->listBooks();
