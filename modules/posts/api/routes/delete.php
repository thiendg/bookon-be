<?php
/**
 * Post Delete API
 * Deletes a post by ID.
 */

// This file is included by modules/posts/api/index.php
// $postController is already instantiated there.
// $id is also available.

if ($id) {
    $postController->deletePost($id);
} else {
    Response::error('Post ID is required for deletion.', 400);
}
