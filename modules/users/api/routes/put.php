<?php
// routes/put.php

if ($id) {
    $userController->updateUser($id);
} else {
    Response::error('User ID is required for update', 400);
}
