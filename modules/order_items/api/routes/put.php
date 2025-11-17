<?php
/**
 * Order Item Update API
 * Updates an existing order item.
 */

// This file is included by modules/order_items/api/index.php
// $orderItemController is already instantiated there.
// $id is also available.

if ($id) {
    $orderItemController->updateOrderItem($id);
} else {
    Response::error('Order Item ID is required for update.', 400);
}
