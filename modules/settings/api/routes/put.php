<?php
if ($id) {
    $settingController->updateSetting($id);
} else {
    Response::error('Setting ID or key is required for update.', 400);
}
