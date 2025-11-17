<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class OrderReviewModel extends BaseModel
{
    protected $tableName = 'order_reviews';
    protected $primaryKey = 'id';

    // Add any specific order review-related methods here if needed
}
