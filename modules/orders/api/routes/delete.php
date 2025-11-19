<?php
/**
 * Order Delete API
 * Deletes an order by ID.
 */

// This file is included by modules/orders/api/index.php
// $orderController is already instantiated there.
// $id is also available.

if ($id) {
    $orderController->deleteOrder($id);
} else {
    Response::error('Order ID is required for deletion.', 400);
}
