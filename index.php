<?php
// Check if PHP and MySQL are properly installed
if (!function_exists('mysqli_connect')) {
    // Redirect to the environment check page
    header('Location: check_environment.php');
    exit;
}

// Try to connect to MySQL
try {
    require_once 'config.php';
    $mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
    
    if ($mysqli->connect_error) {
        // Database connection error
        header('Location: check_environment.php');
        exit;
    }
    
    // Check if database exists
    $result = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if (!$result || $result->num_rows == 0) {
        // Database doesn't exist, redirect to setup
        header('Location: setup_database.php');
        exit;
    }
    
    $mysqli->close();
    
    // Everything is OK, redirect to the HTML index page
    header('Location: index.html');
    exit;
} catch (Exception $e) {
    // Any other error, redirect to environment check
    header('Location: check_environment.php');
    exit;
}
?>