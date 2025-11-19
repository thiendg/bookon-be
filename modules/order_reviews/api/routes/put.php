<?php
/**
 * Order Review Update API
 * Updates an existing order review.
 */

// This file is included by modules/order_reviews/api/index.php
// $orderReviewController is already instantiated there.
// $id is also available.

if ($id) {
    $orderReviewController->updateOrderReview($id);
} else {
    Response::error('Order Review ID is required for update.', 400);
}
