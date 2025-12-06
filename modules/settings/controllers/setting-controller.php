<?php
require_once __DIR__ . '/../models/setting.php';
require_once __DIR__ . '/../../../utils/response.php';

class SettingController
{
    private $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    /**
     * Handles listing all settings with pagination and filtering.
     */
    public function listSettings()
    {
        // Pagination (match BookController flow)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;

        // Filtering
        $filters = [];
        if (isset($_GET['key'])) {
            $filters['setting_key LIKE'] = '%' . $_GET['key'] . '%';
        }

        // Ordering (optional)
        $orderBy = [];
        if (isset($_GET['sortBy']) && isset($_GET['sortOrder'])) {
            $orderBy[$_GET['sortBy']] = $_GET['sortOrder'];
        }

        $result = $this->settingModel->findPage($page, $pageSize, $filters, $orderBy);

        Response::success($result, 'Settings retrieved successfully.');
    }

    /**
     * Handles getting a single setting by ID or key.
     * @param int|string $id The setting ID or key.
     */
    public function getSetting($id)
    {
        $setting = null;
        if (is_numeric($id)) {
            $setting = $this->settingModel->find($id);
        } else {
            // Assuming 'setting_key' is unique and can be used for retrieval
            $setting = $this->settingModel->findOne(['setting_key' => $id]);
        }

        if ($setting) {
            Response::success($setting, 'Setting retrieved successfully.');
        } else {
            Response::notFound('Setting not found.');
        }
    }

    /**
     * Handles creating a new setting.
     */
    public function createSetting()
    {
        // Accept form-data (`$_POST`) or JSON body
        $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['setting_key']) || !isset($data['setting_value'])) {
            Response::error('Missing required fields: setting_key, setting_value', 400);
            return;
        }

        // Check if setting_key already exists
        if ($this->settingModel->findOne(['setting_key' => $data['setting_key']])) {
            Response::error('Setting with this key already exists', 409);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        if ($this->settingModel->create($data)) {
            // For settings, primary key is `setting_key`
            $newSetting = $this->settingModel->findOne(['setting_key' => $data['setting_key']]);
            Response::success(['setting' => $newSetting], 'Setting created successfully.', 201);
        } else {
            Response::error('Failed to create setting.', 500);
        }
    }

    /**
     * Handles updating an existing setting.
     * @param int|string $id The setting ID or key.
     */
    public function updateSetting($id)
    {
        // Fetch existing by key (settings primary key is `setting_key`)
        if (is_numeric($id)) {
            $existingSetting = $this->settingModel->find($id);
        } else {
            $existingSetting = $this->settingModel->findOne(['setting_key' => $id]);
        }

        if (!$existingSetting) {
            Response::notFound('Setting not found.');
            return;
        }

        // Accept form-data (`$_POST`) or JSON body
        $data = !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Prevent changing setting_key to one that already exists
        if (isset($data['setting_key']) && $data['setting_key'] !== $existingSetting['setting_key']) {
            if ($this->settingModel->findOne(['setting_key' => $data['setting_key']])) {
                Response::error('Another setting with this key already exists', 409);
                return;
            }
        }

        // Use the setting_key as the identifier for update
        $identifier = $existingSetting['setting_key'];

        if ($this->settingModel->update($identifier, $data)) {
            $updatedSetting = $this->settingModel->findOne(['setting_key' => $identifier]);
            Response::success(['setting' => $updatedSetting], 'Setting updated successfully.');
        } else {
            Response::error('Failed to update setting.', 500);
        }
    }

    /**
     * Handles deleting a setting.
     * @param int|string $id The setting ID or key.
     */
    public function deleteSetting($id)
    {
        $existingSetting = null;
        if (is_numeric($id)) {
            $existingSetting = $this->settingModel->find($id);
        } else {
            $existingSetting = $this->settingModel->findOne(['setting_key' => $id]);
        }

        if (!$existingSetting) {
            Response::notFound('Setting not found.');
            return;
        }

        // Use the primary key `setting_key` to delete
        $identifier = $existingSetting['setting_key'];

        if ($this->settingModel->delete($identifier)) {
            Response::success(null, 'Setting deleted successfully.');
        } else {
            Response::error('Failed to delete setting.', 500);
        }
    }
}
