<?php
/**
 * Response Helper
 * Provides standardized JSON response methods
 */

class Response {
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        // If the data contains pagination info with totalItems, expose
        // a top-level `totalItem` for convenience (backwards-compatible).
        if (is_array($data) && isset($data['pagination']) && isset($data['pagination']['totalItems'])) {
            $response['totalItem'] = (int) $data['pagination']['totalItems'];
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Send error response
     */
    public static function error($message = 'An error occurred', $code = 400, $errors = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit();
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
}
?>