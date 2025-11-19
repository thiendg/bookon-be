<?php
// routes/get.php

if ($id) {
    // Get a single user
    $userController->getUser($id);
} else {
    // Get a list of users
    $userController->listUsers();
}
