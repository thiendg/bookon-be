<?php
/**
 * Token Generator
 * Generates secure random tokens for authentication purposes
 */

class TokenGenerator {
    
    /**
     * Generate a random token
     * @param int $length Token length in bytes (default: 32)
     * @return string Hex encoded token
     */
    public static function generate($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash a token for storage
     * @param string $token Plain token
     * @return string Hashed token
     */
    public static function hash($token) {
        return hash('sha256', $token);
    }

    /**
     * Verify a token against a hash
     * @param string $token Plain token
     * @param string $hash Stored hash
     * @return bool
     */
    public static function verify($token, $hash) {
        return hash_equals($hash, self::hash($token));
    }

    /**
     * Generate selector and validator for persistent login
     * @return array ['selector' => string, 'validator' => string, 'validator_hash' => string]
     */
    public static function generatePersistentToken() {
        $selector = self::generate(16);
        $validator = self::generate(32);
        
        return [
            'selector' => $selector,
            'validator' => $validator,
            'validator_hash' => self::hash($validator)
        ];
    }

    /**
     * Create a combined token for cookie storage
     * @param string $selector
     * @param string $validator
     * @return string Combined token (selector:validator)
     */
    public static function combinePersistentToken($selector, $validator) {
        return $selector . ':' . $validator;
    }

    /**
     * Split a combined persistent token
     * @param string $token Combined token
     * @return array|null ['selector' => string, 'validator' => string] or null if invalid
     */
    public static function splitPersistentToken($token) {
        $parts = explode(':', $token, 2);
        
        if (count($parts) !== 2) {
            return null;
        }
        
        return [
            'selector' => $parts[0],
            'validator' => $parts[1]
        ];
    }
}
?>