<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class CategoryModel extends BaseModel
{
    protected $tableName = 'categories';
    protected $primaryKey = 'id';

    // Add any specific category-related methods here if needed
}
