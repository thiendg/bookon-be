<?php
/**
 * Transaction Delete API
 * Deletes a transaction by ID.
 */

// This file is included by modules/transactions/api/index.php
// $transactionController is already instantiated there.
// $id is also available.

if ($id) {
    $transactionController->deleteTransaction($id);
} else {
    Response::error('Transaction ID is required for deletion.', 400);
}
