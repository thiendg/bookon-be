<?php
// routes/get.php

if ($id) {
    $roleController->getRole($id);
} else {
    $roleController->listRoles();
}
