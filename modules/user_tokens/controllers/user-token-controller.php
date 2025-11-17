<?php
require_once __DIR__ . '/../models/user-token.php';
require_once __DIR__ . '/../../../utils/response.php';

class UserTokenController
{
    private $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new UserTokenModel();
    }

    /**
     * Handles listing all tokens (for admin purposes).
     */
    public function listTokens()
    {
        // NOTE: Exposing all tokens should be heavily restricted to admins.
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        $filters = [];
        if(isset($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        if(isset($_GET['type'])) {
            $filters['type'] = $_GET['type'];
        }

        $result = $this->tokenModel->findPage($page, $pageSize, $filters);
        Response::success($result);
    }

    /**
     * Handles getting a single token by ID (for admin purposes).
     * @param int $id The token ID.
     */
    public function getToken($id)
    {
        // NOTE: Exposing tokens should be heavily restricted to admins.
        if (!is_numeric($id)) {
            Response::error('Invalid token ID', 400);
            return;
        }
        
        $token = $this->tokenModel->find($id);

        if ($token) {
            Response::success($token);
        } else {
            Response::notFound('Token not found');
        }
    }

    /**
     * Handles creating a new token.
     * This is the primary public-facing use case (e.g., for password reset).
     */
    public function createToken()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['user_id']) || empty($data['type'])) {
            Response::error('Missing required fields: user_id, type', 400);
            return;
        }

        if (!in_array($data['type'], ['email_verification', 'password_reset'])) {
            Response::error('Invalid token type.', 400);
            return;
        }

        // Duration could be passed in data, with a default
        $duration = $data['duration'] ?? 3600; // 1 hour default

        $plainToken = $this->tokenModel->createToken($data['user_id'], $data['type'], $duration);

        if ($plainToken) {
            // In a real app, you would email this token to the user.
            // For this API, we return it for testing purposes.
            Response::success(
                ['token' => $plainToken],
                'Token created successfully. Send this to the user.',
                201
            );
        } else {
            Response::error('Failed to create token', 500);
        }
    }

    /**
     * Handles deleting a token.
     * @param int $id The token ID.
     */
    public function deleteToken($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid token ID', 400);
            return;
        }

        $result = $this->tokenModel->delete($id);

        if ($result) {
            Response::success(null, 'Token deleted successfully');
        } else {
            Response::error('Failed to delete token', 500);
        }
    }
}
