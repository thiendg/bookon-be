<?php
/**
 * Setting Get API
 * Returns a single setting by ID or key.
 */

// This file is included by modules/settings/api/index.php
// $settingController is already instantiated there.
// $id is also available.
    @file_put_contents(__DIR__ . '/settings.log', date('c') . 'GET with id: ' . $id . PHP_EOL, FILE_APPEND);
if ($id) {
    $settingController->getSetting($id);
} else {
    Response::error('Setting ID or key is required.', 400);
}
