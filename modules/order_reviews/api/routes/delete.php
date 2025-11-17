<?php
/**
 * Order Review Delete API
 * Deletes an order review by ID.
 */

// This file is included by modules/order_reviews/api/index.php
// $orderReviewController is already instantiated there.
// $id is also available.

if ($id) {
    $orderReviewController->deleteOrderReview($id);
} else {
    Response::error('Order Review ID is required for deletion.', 400);
}
