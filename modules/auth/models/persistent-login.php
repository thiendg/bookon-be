<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';
require_once __DIR__ . '/../../../utils/token_generator.php'; // For token generation and verification

class PersistentLoginModel extends BaseModel
{
    protected $tableName = 'persistent_logins';

    // Remember me expiration (30 days)
    const EXPIRY_TIME = 2592000; // 30 days in seconds

    /**
     * Generates a new persistent login token and saves it to the database.
     * @param int $userId The ID of the user.
     * @return string|false The combined token (selector:validator) for the cookie, or false on failure.
     */
    public function generateToken($userId)
    {
        // Generate selector and validator
        $tokens = TokenGenerator::generatePersistentToken();
        
        $data = [
            'user_id' => $userId,
            'selector' => $tokens['selector'],
            'validator_hash' => $tokens['validator_hash'],
            'expires_at' => time() + self::EXPIRY_TIME,
            'created_at' => time()
        ];

        if ($this->create($data)) {
            // Return combined token for cookie
            return TokenGenerator::combinePersistentToken($tokens['selector'], $tokens['validator']);
        }

        return false;
    }

    /**
     * Verifies a combined persistent login token.
     * @param string $combinedToken The combined token from the cookie.
     * @return int|false The user ID if the token is valid, false otherwise.
     */
    public function verify($combinedToken)
    {
        // Split token into selector and validator
        $parts = TokenGenerator::splitPersistentToken($combinedToken);
        
        if (!$parts) {
            return false;
        }

        // Find by selector
        $persistentLogin = $this->findOne(['selector' => $parts['selector']]);

        if (!$persistentLogin) {
            return false;
        }

        // Check if expired
        if ($persistentLogin['expires_at'] < time()) {
            $this->delete($persistentLogin[$this->primaryKey]); // Delete expired token
            return false;
        }

        // Verify validator
        if (!TokenGenerator::verify($parts['validator'], $persistentLogin['validator_hash'])) {
            // Possible attack - delete all tokens for this user
            $this->deleteWhere(['user_id' => $persistentLogin['user_id']]);
            return false;
        }

        return $persistentLogin['user_id'];
    }

    /**
     * Refreshes a persistent login token (regenerates after successful verification).
     * @param int $userId The user ID.
     * @param string $oldSelector The selector of the old token.
     * @return string|false The new combined token, or false on failure.
     */
    public function refresh($userId, $oldSelector)
    {
        // Delete old token
        $this->deleteWhere(['selector' => $oldSelector]);
        
        // Generate new token
        return $this->generateToken($userId);
    }

    /**
     * Deletes persistent logins by selector.
     * @param string $selector The selector to delete.
     * @return bool
     */
    public function deleteBySelector($selector)
    {
        return $this->deleteWhere(['selector' => $selector]);
    }

    /**
     * Deletes all persistent logins for a user.
     * @param int $userId The user ID.
     * @return bool
     */
    public function deleteByUserId($userId)
    {
        return $this->deleteWhere(['user_id' => $userId]);
    }

    /**
     * Cleans up expired persistent logins.
     * @return bool
     */
    public function cleanExpired()
    {
        return $this->deleteWhere(['expires_at <' => time()]); // Assuming BaseModel can handle '<' in filter
    }
}
