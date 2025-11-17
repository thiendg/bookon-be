<?php
/**
 * Setting Delete API
 * Deletes a setting by ID or key.
 */

// This file is included by modules/settings/api/index.php
// $settingController is already instantiated there.
// $id is also available.

if ($id) {
    $settingController->deleteSetting($id);
} else {
    Response::error('Setting ID or key is required for deletion.', 400);
}
