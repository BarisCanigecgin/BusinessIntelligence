<?php

/**
 * Database Connection Test
 * Test the database connection with your credentials
 */

require_once 'src/bootstrap.php';

use LuxuryWatch\BI\Core\Config;
use LuxuryWatch\BI\Database\Connection;

echo "=== Database Connection Test ===\n\n";

try {
    // Load configuration
    echo "Loading configuration...\n";
    Config::load();
    
    $dbConfig = Config::getDatabase();
    echo "Database: {$dbConfig['database']}\n";
    echo "Host: {$dbConfig['host']}:{$dbConfig['port']}\n";
    echo "Username: {$dbConfig['username']}\n\n";
    
    // Test connection
    echo "Testing database connection...\n";
    Connection::init($dbConfig);
    
    if (Connection::isConnected()) {
        echo "✓ Database connection successful!\n\n";
        
        // Get connection info
        $info = Connection::getConnectionInfo();
        echo "Connection Details:\n";
        foreach ($info as $key => $value) {
            echo "  - {$key}: {$value}\n";
        }
        echo "\n";
        
        // Test a simple query
        echo "Testing query execution...\n";
        $result = Connection::fetchOne("SELECT DATABASE() as current_db, NOW() as current_time");
        echo "✓ Query successful!\n";
        echo "  - Current Database: {$result['current_db']}\n";
        echo "  - Current Time: {$result['current_time']}\n\n";
        
        // Check existing tables
        echo "Checking existing tables...\n";
        $tables = Connection::fetchAll("SHOW TABLES");
        
        if (empty($tables)) {
            echo "No tables found. Database is ready for installation.\n";
        } else {
            echo "Found " . count($tables) . " existing tables:\n";
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                echo "  - {$tableName}\n";
            }
        }
        
        echo "\n=== Connection Test Complete ===\n";
        echo "Your database is ready! You can now run the installation script.\n";
        echo "Run: php scripts/install_database.php\n";
        
    } else {
        echo "✗ Database connection failed!\n";
        echo "Please check your credentials and database server.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
}
?>
