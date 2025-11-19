<?php
/**
 * Order Item Get API
 * Returns a single order item by ID.
 */

// This file is included by modules/order_items/api/index.php
// $orderItemController is already instantiated there.
// $id is also available.

if ($id) {
    $orderItemController->getOrderItem($id);
} else {
    Response::error('Order Item ID is required.', 400);
}
