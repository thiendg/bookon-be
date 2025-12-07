<?php
if ($id) {
    $bookController->getBook($id);
} else {
    Response::error('Book ID is required.', 400);
}
