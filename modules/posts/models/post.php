<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class PostModel extends BaseModel
{
    protected $tableName = 'posts';
    protected $primaryKey = 'id';

    // Add any specific post-related methods here if needed
}
