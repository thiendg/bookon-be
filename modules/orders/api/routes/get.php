<?php
/**
 * Order Get API
 * Returns a single order by ID.
 */

// This file is included by modules/orders/api/index.php
// $orderController is already instantiated there.
// $id is also available.

if ($id) {
    $orderController->getOrder($id);
} else {
    Response::error('Order ID is required.', 400);
}
