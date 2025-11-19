<?php
/**
 * Order Update API
 * Updates an existing order.
 */

// This file is included by modules/orders/api/index.php
// $orderController is already instantiated there.
// $id is also available.

if ($id) {
    $orderController->updateOrder($id);
} else {
    Response::error('Order ID is required for update.', 400);
}
