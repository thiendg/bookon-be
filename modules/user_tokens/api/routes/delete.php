<?php
// routes/delete.php

if ($id) {
    $tokenController->deleteToken($id);
} else {
    Response::error('Token ID is required for deletion', 400);
}
