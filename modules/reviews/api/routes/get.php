<?php
/**
 * Review Get API
 * Returns a single review by ID.
 */

// This file is included by modules/reviews/api/index.php
// $reviewController is already instantiated there.
// $id is also available.

if ($id) {
    $reviewController->getReview($id);
} else {
    Response::error('Review ID is required.', 400);
}