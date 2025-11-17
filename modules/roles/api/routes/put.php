<?php
// routes/put.php

if ($id) {
    $roleController->updateRole($id);
} else {
    Response::error('Role ID is required for update', 400);
}
