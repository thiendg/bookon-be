<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class SettingModel extends BaseModel
{
    protected $tableName = 'settings';
    protected $primaryKey = 'id';

    // Add any specific setting-related methods here if needed
}
