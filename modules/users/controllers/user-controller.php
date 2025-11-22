<?php
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../../../utils/response.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Handles listing all users with pagination, filtering, and sorting.
     */
    public function listUsers()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;

        // Filtering (example: by status)
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['role_id'])) {
            $filters['role_id'] = $_GET['role_id'];
        }


        // Sorting
        $orderBy = [];
        if (isset($_GET['sortBy'])) {
            $direction = isset($_GET['sortOrder']) && strtoupper($_GET['sortOrder']) === 'DESC' ? 'DESC' : 'ASC';
            $orderBy[$_GET['sortBy']] = $direction;
        }

        // Call the new method that includes role name
        $result = $this->userModel->getUsersWithRoleName($page, $pageSize, $filters, $orderBy);

        // Remove password_hash from each user object
        foreach ($result['data'] as &$user) {
            unset($user['password_hash']);
        }
        unset($user); // Unset reference to last element

        Response::success($result);
    }

    /**
     * Handles getting a single user by ID.
     * @param int $id The user ID.
     */
    public function getUser($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid user ID', 400);
            return;
        }
        
        $user = $this->userModel->findUserWithRole($id);

        if ($user) {
            unset($user['password_hash']); // Remove password hash
            Response::success($user);
        } else {
            Response::notFound('User not found');
        }
    }

    /**
     * Handles creating a new user.
     */
    public function createUser()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
            Response::error('Missing required fields: email, password, full_name', 400);
            return;
        }

        // Check if user already exists
        if ($this->userModel->findOne(['email' => $data['email']])) {
            Response::error('User with this email already exists', 409);
            return;
        }

        $result = $this->userModel->create($data);

        if ($result) {
            Response::success(null, 'User created successfully', 201);
        } else {
            Response::error('Failed to create user', 500);
        }
    }

    /**
     * Handles updating an existing user.
     * @param int $id The user ID.
     */
    public function updateUser($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid user ID', 400);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Prevent changing email to one that already exists
        if (isset($data['email'])) {
            $existingUser = $this->userModel->findOne(['email' => $data['email']]);
            if ($existingUser && $existingUser['id'] != $id) {
                Response::error('Another user with this email already exists', 409);
                return;
            }
        }

        $result = $this->userModel->update($id, $data);

        if ($result) {
            Response::success(null, 'User updated successfully');
        } else {
            // This can also mean the update didn't change any rows
            Response::success(null, 'User update operation completed. No changes detected.');
        }
    }

    /**
     * Handles deleting a user.
     * @param int $id The user ID.
     */
    public function deleteUser($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid user ID', 400);
            return;
        }

        $result = $this->userModel->delete($id);

        if ($result) {
            Response::success(null, 'User deleted successfully');
        } else {
            Response::error('Failed to delete user', 500);
        }
    }

    /**
     * Retrieves users formatted for select/dropdown inputs.
     */
    public function getSelectOptions()
    {
        $options = $this->userModel->getSelectOptions('id', 'full_name'); // Assuming 'id' for value and 'full_name' for label
        Response::success($options, 'User select options retrieved successfully.');
    }
}
