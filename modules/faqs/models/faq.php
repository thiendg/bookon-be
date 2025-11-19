<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class FaqModel extends BaseModel
{
    protected $tableName = 'faqs';
    protected $primaryKey = 'id';

    // Add any specific FAQ-related methods here if needed
}
