<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class OrderItemModel extends BaseModel
{
    protected $tableName = 'order_items';
    protected $primaryKey = 'id';

    // Add any specific order item-related methods here if needed
}
