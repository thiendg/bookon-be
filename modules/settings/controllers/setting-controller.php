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
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['key'])) {
            $filters['setting_key LIKE'] = '%' . $_GET['key'] . '%';
        }
        // Add more filters as needed

        $settings = $this->settingModel->findAll($filters, $limit, $offset);
        $totalSettings = $this->settingModel->count($filters);

        Response::success([
            'settings' => $settings,
            'total' => $totalSettings,
            'page' => $page,
            'limit' => $limit
        ], 'Settings retrieved successfully.');
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
        $data = json_decode(file_get_contents('php://input'), true);

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

        if ($newSettingId = $this->settingModel->create($data)) {
            $newSetting = $this->settingModel->find($newSettingId);
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

        $data = json_decode(file_get_contents('php://input'), true);

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

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->settingModel->update($existingSetting['id'], $data)) {
            $updatedSetting = $this->settingModel->find($existingSetting['id']);
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

        if ($this->settingModel->delete($existingSetting['id'])) {
            Response::success(null, 'Setting deleted successfully.');
        } else {
            Response::error('Failed to delete setting.', 500);
        }
    }
}
