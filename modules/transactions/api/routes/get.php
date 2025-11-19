<?php
/**
 * Transaction Get API
 * Returns a single transaction by ID.
 */

// This file is included by modules/transactions/api/index.php
// $transactionController is already instantiated there.
// $id is also available.

if ($id) {
    $transactionController->getTransaction($id);
} else {
    Response::error('Transaction ID is required.', 400);
}
