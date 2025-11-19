<?php
require_once __DIR__ . '/../models/role.php';
require_once __DIR__ . '/../../../utils/response.php';

class RoleController
{
    private $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    /**
     * Handles listing all roles with pagination.
     */
    public function listRoles()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;

        $result = $this->roleModel->findPage($page, $pageSize);

        // Decode the permissions JSON string into an array for cleaner output
        if (isset($result['data'])) {
            foreach ($result['data'] as &$role) {
                if (isset($role['permissions'])) {
                    $role['permissions'] = json_decode($role['permissions'], true);
                }
            }
        }

        Response::success($result);
    }

    /**
     * Handles getting a single role by ID.
     * @param int $id The role ID.
     */
    public function getRole($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid role ID', 400);
            return;
        }
        
        $role = $this->roleModel->find($id);

        if ($role) {
            if (isset($role['permissions'])) {
                $role['permissions'] = json_decode($role['permissions'], true);
            }
            Response::success($role);
        } else {
            Response::notFound('Role not found');
        }
    }

    /**
     * Handles creating a new role.
     */
    public function createRole()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            Response::error('Missing required field: name', 400);
            return;
        }

        if ($this->roleModel->findOne(['name' => $data['name']])) {
            Response::error('Role with this name already exists', 409);
            return;
        }

        $result = $this->roleModel->create($data);

        if ($result) {
            Response::success(null, 'Role created successfully', 201);
        } else {
            Response::error('Failed to create role', 500);
        }
    }

    /**
     * Handles updating an existing role.
     * @param int $id The role ID.
     */
    public function updateRole($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid role ID', 400);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        if (isset($data['name'])) {
            $existing = $this->roleModel->findOne(['name' => $data['name']]);
            if ($existing && $existing['id'] != $id) {
                Response::error('Another role with this name already exists', 409);
                return;
            }
        }

        $result = $this->roleModel->update($id, $data);

        if ($result) {
            Response::success(null, 'Role updated successfully');
        } else {
            Response::success(null, 'Role update operation completed. No changes detected.');
        }
    }

    /**
     * Handles deleting a role.
     * @param int $id The role ID.
     */
    public function deleteRole($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid role ID', 400);
            return;
        }

        $result = $this->roleModel->delete($id);

        if ($result) {
            Response::success(null, 'Role deleted successfully');
        } else {
            Response::error('Failed to delete role', 500);
        }
    }
}
