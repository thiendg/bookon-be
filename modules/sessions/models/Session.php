<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class SessionModel extends BaseModel
{
    protected $tableName = 'sessions';
    protected $primaryKey = 'session_id';
}
