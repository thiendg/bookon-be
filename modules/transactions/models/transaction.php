<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class TransactionModel extends BaseModel
{
    protected $tableName = 'transactions';
    protected $primaryKey = 'id';

    // Add any specific transaction-related methods here if needed
}
