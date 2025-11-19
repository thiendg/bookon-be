<?php
/**
 * Review Update API
 * Updates an existing review.
 */

// This file is included by modules/reviews/api/index.php
// $reviewController is already instantiated there.
// $id is also available.

if ($id) {
    $reviewController->updateReview($id);
} else {
    Response::error('Review ID is required for update.', 400);
}