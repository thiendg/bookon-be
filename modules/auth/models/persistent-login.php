<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';
require_once __DIR__ . '/../../../utils/token_generator.php';

class PersistentLoginModel extends BaseModel
{
    protected $tableName = 'persistent_logins';

    const EXPIRY_TIME = 7200;

    /**
     * Generates a new persistent login token and saves it to the database.
     * @param int $userId The ID of the user.
     * @return string|false The combined token (selector:validator) for the cookie, or false on failure.
     */
    public function generateToken($userId)
    {
        $tokens = TokenGenerator::generatePersistentToken();

        $data = [
            'user_id' => $userId,
            'selector' => $tokens['selector'],
            'validator_hash' => $tokens['validator_hash'],
            'expires_at' => time() + self::EXPIRY_TIME,
            'created_at' => time()
        ];

        if ($this->create($data)) {
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
        $parts = TokenGenerator::splitPersistentToken($combinedToken);

        if (!$parts) {
            return false;
        }

        $persistentLogin = $this->findOne(['selector' => $parts['selector']]);

        if (!$persistentLogin) {
            return false;
        }

        if ($persistentLogin['expires_at'] < time()) {
            $this->delete($persistentLogin[$this->primaryKey]);
            return false;
        }

        if (!TokenGenerator::verify($parts['validator'], $persistentLogin['validator_hash'])) {
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
        $this->deleteWhere(['selector' => $oldSelector]);

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
        return $this->deleteWhere(['expires_at <' => time()]);
    }
}
