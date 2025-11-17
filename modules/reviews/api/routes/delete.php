<?php
/**
 * Review Delete API
 * Deletes a review by ID.
 */

// This file is included by modules/reviews/api/index.php
// $reviewController is already instantiated there.
// $id is also available.

if ($id) {
    $reviewController->deleteReview($id);
} else {
    Response::error('Review ID is required for deletion.', 400);
}