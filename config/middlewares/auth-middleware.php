<?php
require_once __DIR__ . '/../../utils/app-session.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../modules/users/models/user.php'; // BaseModel-based UserModel
require_once __DIR__ . '/../../modules/roles/models/role.php'; // My BaseModel-based Role model

class AuthMiddleware
{
    /**
     * Checks if a user is logged in. If not, sends an unauthorized response and exits.
     * If logged in, fetches and stores user data in $_SERVER['authenticated_user'].
     */
    public static function requireLogin()
    {
        AppSession::start();
        if (!AppSession::isAuthenticated()) {
            Response::unauthorized('Authentication required.');
            exit();
        }

        $userId = AppSession::getUserId();
        
        $userModel = new UserModel(); // BaseModel-based User model
        $roleModel = new RoleModel(); // My BaseModel-based Role model

        if (!$user = $userModel->find($userId)) {
            // User ID in session but user not found in DB (e.g., deleted user)
            AppSession::destroy();
            Response::unauthorized('User session invalid.');
            exit();
        }

        // Fetch role name and permissions
        $userData = $user; // User is already an array from find()
        if ($userData['role_id']) {
            $role = $roleModel->find($userData['role_id']);
            if ($role) {
                $userData['role_name'] = $role['name'];
                $userData['permissions'] = json_decode($role['permissions'], true);
            }
        }

        // Store authenticated user data for controllers to use
        // Exclude sensitive info like password_hash
        unset($userData['password_hash']);
        $_SERVER['authenticated_user'] = $userData;
    }

    /**
     * Checks if the authenticated user has a specific permission.
     * Requires login first.
     * @param string $requiredPermission The permission string (e.g., 'users:create').
     */
    public static function requirePermission($requiredPermission)
    {
        self::requireLogin(); // Ensure user is logged in

        $user = $_SERVER['authenticated_user'];
        
        if (!isset($user['permissions'])) {
            Response::forbidden('Role or permissions not found for user.');
            exit();
        }

        $permissions = $user['permissions']; // Permissions are already decoded in requireLogin

        // Check if the required permission exists in the user's role permissions
        // Permissions are typically structured like: ["module": ["action", "action2"]]
        list($module, $action) = explode(':', $requiredPermission);

        if (isset($permissions[$module]) && in_array($action, $permissions[$module])) {
            return; // User has permission
        }

        Response::forbidden('Access denied. Insufficient permissions.');
        exit();
    }

    /**
     * Checks if the authenticated user has a specific role.
     * Requires login first.
     * @param string $requiredRoleName The name of the required role (e.g., 'Admin').
     */
    public static function requireRole($requiredRoleName)
    {
        self::requireLogin(); // Ensure user is logged in

        $user = $_SERVER['authenticated_user'];
        
        if (isset($user['role_name']) && $user['role_name'] === $requiredRoleName) {
            return; // User has the required role
        }

        Response::forbidden('Access denied. Insufficient role.');
        exit();
    }
}
