<?php
require_once __DIR__ . '/../models/session.php';
require_once __DIR__ . '/../../../utils/response.php';

class SessionController
{
    private $sessionModel;

    public function __construct()
    {
        $this->sessionModel = new SessionModel();
    }

    /**
     * Handles listing all sessions (for admin purposes).
     */
    public function listSessions()
    {
        // NOTE: Exposing sessions should be heavily restricted to admins.
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        
        $filters = [];
        if(isset($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }

        $result = $this->sessionModel->findPage($page, $pageSize, $filters);
        Response::success($result);
    }

    /**
     * Handles getting a single session by its ID.
     * @param string $id The session ID.
     */
    public function getSession($id)
    {
        // NOTE: Exposing sessions should be heavily restricted to admins.
        if (empty($id)) {
            Response::error('Invalid session ID', 400);
            return;
        }
        
        $session = $this->sessionModel->find($id);

        if ($session) {
            Response::success($session);
        } else {
            Response::notFound('Session not found');
        }
    }

    /**
     * Handles deleting a session (force logout).
     * @param string $id The session ID.
     */
    public function deleteSession($id)
    {
        if (empty($id)) {
            Response::error('Invalid session ID', 400);
            return;
        }

        $result = $this->sessionModel->delete($id);

        if ($result) {
            Response::success(null, 'Session deleted successfully');
        } else {
            Response::error('Failed to delete session', 500);
        }
    }
}
