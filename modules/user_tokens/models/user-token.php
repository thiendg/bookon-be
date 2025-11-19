<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class UserTokenModel extends BaseModel
{
    protected $tableName = 'user_tokens';

    /**
     * Creates a new token, hashing it before storage.
     * @param int $userId The user's ID.
     * @param string $type The type of token ('email_verification' or 'password_reset').
     * @param int $durationSeconds The duration for which the token is valid.
     * @return string The plain-text token (for sending to the user).
     */
    public function createToken($userId, $type, $durationSeconds = 3600)
    {
        // Generate a cryptographically secure random token
        $plainToken = bin2hex(random_bytes(32));
        
        // Hash the token for database storage
        $tokenHash = hash('sha256', $plainToken);

        $data = [
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'type' => $type,
            'expires_at' => time() + $durationSeconds,
            'created_at' => time()
        ];

        if (parent::create($data)) {
            // Return the plain token only on successful creation
            return $plainToken;
        }

        return false;
    }

    /**
     * Finds a user by a plain-text token.
     * @param string $plainToken The plain-text token from the user.
     * @return array|null The token record from the database if found and not expired.
     */
    public function findByToken($plainToken)
    {
        $tokenHash = hash('sha256', $plainToken);
        
        $tokenRecord = $this->findOne(['token_hash' => $tokenHash]);

        if ($tokenRecord && $tokenRecord['expires_at'] > time()) {
            return $tokenRecord;
        }

        return null;
    }

    /**
     * Deletes a token by its hash.
     * @param string $plainToken The plain-text token.
     * @return bool
     */
    public function deleteByToken($plainToken)
    {
        $tokenHash = hash('sha256', $plainToken);
        return $this->deleteWhere(['token_hash' => $tokenHash]);
    }
}
