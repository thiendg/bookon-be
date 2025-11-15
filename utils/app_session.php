<?php
/**
 * Session Handler
 * Manages PHP sessions and authentication state
 */

class AppSession {
    
    /**
     * Start session with secure settings
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax');
            
            // For production, enable this:
            // ini_set('session.cookie_secure', 1);
            
            session_start();
        }
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        self::start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    public static function getUserId() {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Set user session data
     */
    public static function setUser($userId, $userData = []) {
        self::start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $userData['email'] ?? null;
        $_SESSION['user_name'] = $userData['name'] ?? null;
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Get user session data
     */
    public static function getUser() {
        self::start();
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'logged_in_at' => $_SESSION['logged_in_at'] ?? null
        ];
    }

    /**
     * Destroy user session
     */
    public static function destroy() {
        self::start();
        
        // Unset all session variables
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }

    /**
     * Regenerate session ID (prevent session fixation)
     */
    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Get client IP address
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    /**
     * Get user agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
?>