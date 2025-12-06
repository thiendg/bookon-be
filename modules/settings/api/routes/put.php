<?php
/**
 * Setting Update API
 * Updates an existing setting.
 */

// This file is included by modules/settings/api/index.php
// $settingController is already instantiated there.
// $id is also available.
    @file_put_contents(__DIR__ . '/settings.log', date('c') . 'PUT with id: '. $id . PHP_EOL, FILE_APPEND);
if ($id) {
    $settingController->updateSetting($id);
} else {
    Response::error('Setting ID or key is required for update.', 400);
}
