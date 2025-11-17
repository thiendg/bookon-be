<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class PostCommentModel extends BaseModel
{
    protected $tableName = 'post_comments';
    protected $primaryKey = 'id';

    // Add any specific post comment-related methods here if needed
}
