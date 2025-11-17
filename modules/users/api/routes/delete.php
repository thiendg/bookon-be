<?php
// routes/delete.php

if ($id) {
    $userController->deleteUser($id);
} else {
    Response::error('User ID is required for deletion', 400);
}
