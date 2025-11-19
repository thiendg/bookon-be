<?php
/**
 * Transaction List API
 * Returns a list of transactions, with optional filtering and pagination.
 */

// This file is included by modules/transactions/api/index.php
// $transactionController is already instantiated there.

$transactionController->listTransactions();
