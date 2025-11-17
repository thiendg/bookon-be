<?php
/**
 * Order Review Get API
 * Returns a single order review by ID.
 */

// This file is included by modules/order_reviews/api/index.php
// $orderReviewController is already instantiated there.
// $id is also available.

if ($id) {
    $orderReviewController->getOrderReview($id);
} else {
    Response::error('Order Review ID is required.', 400);
}
