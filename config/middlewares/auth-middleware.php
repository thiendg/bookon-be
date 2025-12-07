<?php
require_once __DIR__ . '/../../utils/app-session.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../modules/users/models/user.php';
require_once __DIR__ . '/../../modules/roles/models/role.php';

class AuthMiddleware
{
    public static function requireLogin()
    {
        AppSession::start();
        if (!AppSession::isAuthenticated()) {
            Response::unauthorized('Authentication required.');
            exit();
        }

        $userId = AppSession::getUserId();

        $userModel = new UserModel();
        $roleModel = new RoleModel();

        if (!$user = $userModel->find($userId)) {
            AppSession::destroy();
            Response::unauthorized('User session invalid.');
            exit();
        }

        $userData = $user;
        if ($userData['role_id']) {
            $role = $roleModel->find($userData['role_id']);
            if ($role) {
                $userData['role_name'] = $role['name'];
                $userData['permissions'] = json_decode($role['permissions'], true);
            }
        }

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
        self::requireLogin();

        $user = $_SERVER['authenticated_user'];

        if (!isset($user['permissions'])) {
            Response::forbidden('Role or permissions not found for user.');
            exit();
        }

        $permissions = $user['permissions'];

        list($module, $action) = explode(':', $requiredPermission);

        if (isset($permissions[$module]) && in_array($action, $permissions[$module])) {
            return;
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
        self::requireLogin();

        $user = $_SERVER['authenticated_user'];

        if (isset($user['role_name']) && $user['role_name'] === $requiredRoleName) {
            return;
        }

        Response::forbidden('Access denied. Insufficient role.');
        exit();
    }
}
