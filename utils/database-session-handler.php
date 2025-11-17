<?php

require_once __DIR__ . '/../modules/sessions/models/session.php'; // Use the BaseModel-based SessionModel

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private $sessionModel;

    public function __construct()
    {
        $this->sessionModel = new SessionModel();
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $session = $this->sessionModel->findOne(['session_id' => $id]);
        if ($session) {
            if ($session['last_activity'] > (time() - ini_get('session.gc_maxlifetime'))) {
                return $session['payload'];
            }
        }
        return '';
    }

    public function write(string $id, string $data): bool
    {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $lastActivity = time();

        $existingSession = $this->sessionModel->findOne(['session_id' => $id]);

        if ($existingSession) {
            // Update existing session
            return $this->sessionModel->update($existingSession['session_id'], [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'payload' => $data,
                'last_activity' => $lastActivity
            ]);
        } else {
            // Create new session
            return $this->sessionModel->create([
                'session_id' => $id,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'payload' => $data,
                'last_activity' => $lastActivity
            ]);
        }
    }

    public function destroy(string $id): bool
    {
        return $this->sessionModel->deleteWhere(['session_id' => $id]);
    }

    public function gc($max_lifetime): int|false
    {
        return $this->sessionModel->deleteWhere(['last_activity <' => time() - $max_lifetime]);
    }
}
