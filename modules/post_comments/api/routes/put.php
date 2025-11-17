<?php
/**
 * Post Comment Update API
 * Updates an existing post comment.
 */

// This file is included by modules/post_comments/api/index.php
// $postCommentController is already instantiated there.
// $id is also available.

if ($id) {
    $postCommentController->updatePostComment($id);
} else {
    Response::error('Post Comment ID is required for update.', 400);
}
