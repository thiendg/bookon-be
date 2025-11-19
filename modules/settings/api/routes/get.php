<?php
/**
 * Setting Get API
 * Returns a single setting by ID or key.
 */

// This file is included by modules/settings/api/index.php
// $settingController is already instantiated there.
// $id is also available.

if ($id) {
    $settingController->getSetting($id);
} else {
    Response::error('Setting ID or key is required.', 400);
}
