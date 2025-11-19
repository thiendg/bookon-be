<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class RoleModel extends BaseModel
{
    protected $tableName = 'roles';

    /**
     * Overriding create to handle timestamps.
     * @param array $data Role data.
     * @return bool True on success, false on failure.
     */
    public function create($data)
    {
        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        // The permissions field should be JSON encoded if it's an array
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions']);
        }

        return parent::create($data);
    }

    /**
     * Overriding update to handle timestamps.
     * @param int $id The role ID to update.
     * @param array $data Role data.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data)
    {
        // Set timestamp
        $data['updated_at'] = time();

        // The permissions field should be JSON encoded if it's an array
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions']);
        }

        return parent::update($id, $data);
    }
}
