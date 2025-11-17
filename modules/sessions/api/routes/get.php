<?php
// routes/get.php

if ($id) {
    $sessionController->getSession($id);
} else {
    $sessionController->listSessions();
}
