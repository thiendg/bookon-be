<?php
// routes/get.php

if ($id) {
    $tokenController->getToken($id);
} else {
    $tokenController->listTokens();
}
