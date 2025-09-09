<?php
// Database Configuration File
// Modify these values according to your database setup

// Check if PHP and MySQL are installed
if (!function_exists('mysqli_connect')) {
    die("<h1>Error: PHP MySQL extension is not installed</h1>\n" .
        "<p>This website requires PHP with MySQL support and a MySQL server.</p>\n" .
        "<p>Please install XAMPP, WAMP, or another PHP/MySQL package:</p>\n" .
        "<ul>\n" .
        "<li><a href='https://www.apachefriends.org/download.html' target='_blank'>Download XAMPP</a></li>\n" .
        "<li><a href='https://www.wampserver.com/en/download-wampserver-64bits/' target='_blank'>Download WAMP</a></li>\n" .
        "</ul>\n" .
        "<p>After installation, start Apache and MySQL services, then try again.</p>");
}

// Database connection settings (env overrides supported)
// You can set DATABASE_URL (mysql://user:pass@host:3306/dbname) or individual vars: DB_HOST, DB_PORT, DB_NAME, DB_USERNAME/DB_USER, DB_PASSWORD/DB_PASS, DB_CHARSET
$__DB_URL = getenv('DATABASE_URL') ?: getenv('CLEARDB_DATABASE_URL') ?: getenv('JAWSDB_URL');
if ($__DB_URL) {
    $parts = parse_url($__DB_URL);
    define('DB_HOST', $parts['host'] ?? 'localhost');
    define('DB_PORT', isset($parts['port']) ? (int)$parts['port'] : null);
    define('DB_NAME', isset($parts['path']) ? ltrim($parts['path'], '/') : 'casino_db');
    define('DB_USERNAME', $parts['user'] ?? 'root');
    define('DB_PASSWORD', $parts['pass'] ?? '');
    define('DB_CHARSET', 'utf8mb4');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ? (int)getenv('DB_PORT') : null);
    define('DB_NAME', getenv('DB_NAME') ?: 'casino_db');
    define('DB_USERNAME', getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root'));
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: ''));
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
}

// Timezone setting
date_default_timezone_set('UTC');

// Error reporting (controlled by env DEBUG/APP_DEBUG; default true)
$__DEBUG = getenv('DEBUG') ?: getenv('APP_DEBUG');
define('DEBUG_MODE', $__DEBUG !== false ? filter_var($__DEBUG, FILTER_VALIDATE_BOOLEAN) : true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start session for error tracking
if (!session_id()) {
    session_start();
}

// Database connection function
function getDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";" . (DB_PORT ? ("port=" . DB_PORT . ";") : "") . "dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        // Optional persistent connections via env DB_PERSISTENT=true
        $persist = getenv('DB_PERSISTENT');
        if ($persist !== false && filter_var($persist, FILTER_VALIDATE_BOOLEAN)) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        
        // Clear any previous database error flag
        $_SESSION['database_error'] = false;
        
        // Set JavaScript session storage via cookie
        setcookie('database_error', 'false', 0, '/');
        
        return $pdo;
    } catch(PDOException $e) {
        // Set database error flag in session
        $_SESSION['database_error'] = true;
        
        // Set JavaScript session storage via cookie
        setcookie('database_error', 'true', 0, '/');
        
        if (DEBUG_MODE) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        } else {
            throw new Exception('Database connection failed');
        }
    }
}

// Helper function to check if database exists
function checkDatabaseExists() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
        return $stmt->fetch() !== false;
    } catch(PDOException $e) {
        return false;
    }
}

// Helper function to create database if it doesn't exist
function createDatabaseIfNotExists() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8 COLLATE utf8_general_ci");
        return true;
    } catch(PDOException $e) {
        return false;
    }
}
?>

