<?php
/**
 * Post Update API
 * Updates an existing post.
 */

// This file is included by modules/posts/api/index.php
// $postController is already instantiated there.
// $id is also available.

if ($id) {
    $postController->updatePost($id);
} else {
    Response::error('Post ID is required for update.', 400);
}
