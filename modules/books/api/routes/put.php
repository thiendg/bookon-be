<?php
if ($id) {
    $bookController->updateBook($id);
} else {
    Response::error('Book ID is required for update.', 400);
}
