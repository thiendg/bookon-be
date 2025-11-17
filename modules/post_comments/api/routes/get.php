<?php
/**
 * Post Comment Get API
 * Returns a single post comment by ID.
 */

// This file is included by modules/post_comments/api/index.php
// $postCommentController is already instantiated there.
// $id is also available.

if ($id) {
    $postCommentController->getPostComment($id);
} else {
    Response::error('Post Comment ID is required.', 400);
}
