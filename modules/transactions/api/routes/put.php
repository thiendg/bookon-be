<?php
/**
 * Transaction Update API
 * Updates an existing transaction.
 */

// This file is included by modules/transactions/api/index.php
// $transactionController is already instantiated there.
// $id is also available.

if ($id) {
    $transactionController->updateTransaction($id);
} else {
    Response::error('Transaction ID is required for update.', 400);
}
