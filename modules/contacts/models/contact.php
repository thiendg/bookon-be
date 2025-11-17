<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class ContactModel extends BaseModel
{
    protected $tableName = 'contacts';
    protected $primaryKey = 'id';

    // Add any specific contact-related methods here if needed
}