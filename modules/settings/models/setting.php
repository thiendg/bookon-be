<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class SettingModel extends BaseModel
{
    protected $tableName = 'settings';
    // Settings table uses `setting_key` as the primary key (see scripts.sql)
    protected $primaryKey = 'setting_key';

    // Add any specific setting-related methods here if needed
}
