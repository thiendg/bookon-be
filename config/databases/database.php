<?php
require_once __DIR__ . '/../../utils/env-loader.php';
loadEnv(__DIR__ . '/../../.env');

class Database
{
    private static $conn = null;

    public static function getConnection()
    {
        if (self::$conn == null) {
            $host = $_ENV['DB_HOST'] ?? null;
            $db_name = $_ENV['DB_NAME'] ?? null;
            $username = $_ENV['DB_USER'] ?? null;
            $password = $_ENV['DB_PASS'] ?? null;
            $port = $_ENV['DB_PORT'] ?? null;
            $ssl_ca = __DIR__ . '/CA_certificate/ca.pem';

            if (!$host || !$db_name || !$username || !$password || !$port) {
                die(json_encode([
                    'success' => false,
                    'message' => 'Database environment variables are not set correctly.'
                ]));
            }

            $mysqli = mysqli_init();
            if (!$mysqli) {
                die(json_encode([
                    'success' => false,
                    'message' => 'mysqli_init failed'
                ]));
            }

            mysqli_ssl_set($mysqli, NULL, NULL, $ssl_ca, NULL, NULL);

            if (!mysqli_real_connect($mysqli, $host, $username, $password, $db_name, (int)$port, NULL, MYSQLI_CLIENT_SSL)) {
                $connect_error = mysqli_connect_error();
                $connect_errno = mysqli_connect_errno();
                die(json_encode([
                    'success' => false,
                    'message' => "Database connection failed: ($connect_errno) $connect_error"
                ]));
            }

            self::$conn = $mysqli;
        }
        return self::$conn;
    }
}
