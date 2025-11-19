<?php
// routes/delete.php

if ($id) {
    $sessionController->deleteSession($id);
} else {
    Response::error('Session ID is required for deletion', 400);
}
