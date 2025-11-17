<?php
/**
 * Post Comment Delete API
 * Deletes a post comment by ID.
 */

// This file is included by modules/post_comments/api/index.php
// $postCommentController is already instantiated there.
// $id is also available.

if ($id) {
    $postCommentController->deletePostComment($id);
} else {
    Response::error('Post Comment ID is required for deletion.', 400);
}
