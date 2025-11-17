<?php
/**
 * Order Item Delete API
 * Deletes an order item by ID.
 */

// This file is included by modules/order_items/api/index.php
// $orderItemController is already instantiated there.
// $id is also available.

if ($id) {
    $orderItemController->deleteOrderItem($id);
} else {
    Response::error('Order Item ID is required for deletion.', 400);
}
