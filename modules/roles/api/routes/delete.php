<?php
// routes/delete.php

if ($id) {
    $roleController->deleteRole($id);
} else {
    Response::error('Role ID is required for deletion', 400);
}
