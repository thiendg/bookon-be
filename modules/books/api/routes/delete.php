<?php
if ($id) {
    $bookController->deleteBook($id);
} else {
    Response::error('Book ID is required for deletion.', 400);
}
