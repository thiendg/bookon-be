<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class ReviewModel extends BaseModel
{
    protected $tableName = 'reviews';
    protected $primaryKey = 'id';

    // Add any specific review-related methods here if needed
}
