<?php
/**
 * Post Get API
 * Returns a single post by ID.
 */

// This file is included by modules/posts/api/index.php
// $postController is already instantiated there.
// $id is also available.

if ($id) {
    $postController->getPost($id);
} else {
    Response::error('Post ID is required.', 400);
}
