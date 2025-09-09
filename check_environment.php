<?php
// Environment and Database Check Script
echo "<h1>Casino Website Environment Check</h1>";

// Check PHP version
echo "<h2>PHP Environment</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check required PHP extensions
$required_extensions = ['mysqli', 'pdo_mysql', 'json', 'session'];
echo "<h3>Required PHP Extensions:</h3>";
echo "<ul>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li>✅ $ext: Installed</li>";
    } else {
        echo "<li>❌ $ext: Not Installed</li>";
    }
}
echo "</ul>";

// Check MySQL connection
echo "<h2>MySQL Connection Test</h2>";

// Include config file if it exists
if (file_exists('config.php')) {
    require_once 'config.php';
    
    try {
        // Try to connect to MySQL server without selecting a database
        $mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
        
        if ($mysqli->connect_error) {
            echo "<p>❌ MySQL Connection Error: " . $mysqli->connect_error . "</p>";
            echo "<p>Please check that:</p>";
            echo "<ul>";
            echo "<li>MySQL server is running</li>";
            echo "<li>Username and password in config.php are correct</li>";
            echo "<li>Host in config.php is correct</li>";
            echo "</ul>";
        } else {
            echo "<p>✅ Successfully connected to MySQL server</p>";
            
            // Check if database exists
            $result = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
            
            if ($result && $result->num_rows > 0) {
                echo "<p>✅ Database '" . DB_NAME . "' exists</p>";
                
                // Select the database
                if ($mysqli->select_db(DB_NAME)) {
                    echo "<p>✅ Successfully selected database '" . DB_NAME . "'</p>";
                    
                    // Check if tables exist
                    $tables = ['users', 'vip_levels', 'user_vip_stats'];
                    echo "<h3>Required Tables:</h3>";
                    echo "<ul>";
                    foreach ($tables as $table) {
                        $result = $mysqli->query("SHOW TABLES LIKE '" . $table . "'");
                        if ($result && $result->num_rows > 0) {
                            echo "<li>✅ Table '" . $table . "' exists</li>";
                        } else {
                            echo "<li>❌ Table '" . $table . "' does not exist</li>";
                        }
                    }
                    echo "</ul>";
                } else {
                    echo "<p>❌ Failed to select database '" . DB_NAME . "'</p>";
                }
            } else {
                echo "<p>❌ Database '" . DB_NAME . "' does not exist</p>";
                echo "<p>You need to run the setup script: <a href='setup_database.php'>Setup Database</a></p>";
            }
            
            $mysqli->close();
        }
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Config file not found</p>";
}

// Next steps
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Make sure PHP and MySQL are installed</li>";
echo "<li>Start Apache and MySQL services</li>";
echo "<li>Run the <a href='setup_database.php'>Setup Database</a> script</li>";
echo "<li>Try accessing the <a href='index.html'>website</a></li>";
echo "</ol>";

echo "<h2>Recommended Software</h2>";
echo "<ul>";
echo "<li><a href='https://www.apachefriends.org/download.html' target='_blank'>XAMPP</a> - Easy to install Apache, MySQL, PHP package</li>";
echo "<li><a href='https://www.wampserver.com/en/download-wampserver-64bits/' target='_blank'>WAMP</a> - Windows, Apache, MySQL, PHP package</li>";
echo "</ul>";
?>