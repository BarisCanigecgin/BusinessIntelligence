<?php

/**
 * Simple Database Connection Test
 */

echo "=== Simple Database Connection Test ===\n\n";

// Database configuration
$host = 'localhost';
$dbname = 'knowhowpilot_BI';
$username = 'knowhowpilot_admin';
$password = 'Sbrkmtn1071@1453';

try {
    // Create PDO connection
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ Database connection successful!\n\n";
    
    // Test query
    $stmt = $pdo->query("SELECT DATABASE() as current_db, NOW() as current_time");
    $result = $stmt->fetch();
    
    echo "Database Info:\n";
    echo "  - Current Database: {$result['current_db']}\n";
    echo "  - Current Time: {$result['current_time']}\n\n";
    
    // Check existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    if (empty($tables)) {
        echo "No tables found. Database is ready for installation.\n\n";
        
        echo "=== Ready to Install ===\n";
        echo "Your database connection is working!\n";
        echo "You can now:\n";
        echo "1. Run the installation script\n";
        echo "2. Access the dashboard\n";
        echo "3. Use the API endpoints\n";
        
    } else {
        echo "Found " . count($tables) . " existing tables:\n";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "  - {$tableName}\n";
        }
        echo "\nDatabase already has tables. You may want to backup before installation.\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Please check:\n";
    echo "1. Database server is running\n";
    echo "2. Database name: {$dbname}\n";
    echo "3. Username: {$username}\n";
    echo "4. Password is correct\n";
    echo "5. User has proper permissions\n";
}

echo "\n=== Test Complete ===\n";
?>
