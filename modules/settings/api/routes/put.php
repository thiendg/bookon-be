<?php
/**
 * Setting Update API
 * Updates an existing setting.
 */

// This file is included by modules/settings/api/index.php
// $settingController is already instantiated there.
// $id is also available.

if ($id) {
    $settingController->updateSetting($id);
} else {
    Response::error('Setting ID or key is required for update.', 400);
}
