<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class OrderModel extends BaseModel
{
    protected $tableName = 'orders';
    protected $primaryKey = 'id';

    // Add any specific order-related methods here if needed
}
