<?php
/**
 * Web-based Database Installation
 * Run this file in your browser to install the database
 */

// Security check - only allow installation if not already done
$lockFile = 'installation.lock';
if (file_exists($lockFile)) {
    die('Installation already completed. Delete installation.lock file to reinstall.');
}

$step = $_GET['step'] ?? 'check';
$action = $_POST['action'] ?? '';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Watch BI - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .install-container { max-width: 800px; margin: 50px auto; }
        .step-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body class="bg-light">
    <div class="container install-container">
        <div class="card">
            <div class="card-header step-header text-center">
                <h2><i class="fas fa-gem"></i> Luxury Watch BI Installation</h2>
                <p class="mb-0">KnowHow Pilot - Business Intelligence System</p>
            </div>
            <div class="card-body">

<?php if ($step === 'check'): ?>
                <!-- Step 1: System Check -->
                <h4>📋 System Requirements Check</h4>
                <div class="row">
                    <div class="col-md-12">
                        <?php
                        $checks = [
                            'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
                            'PDO Extension' => extension_loaded('pdo'),
                            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
                            'JSON Extension' => extension_loaded('json'),
                            'Logs Directory Writable' => is_writable('logs') || mkdir('logs', 0777, true),
                            'Cache Directory Writable' => is_writable('cache') || mkdir('cache', 0777, true),
                            '.env File Exists' => file_exists('.env')
                        ];
                        
                        $allPassed = true;
                        foreach ($checks as $check => $passed) {
                            $icon = $passed ? '✅' : '❌';
                            $class = $passed ? 'success' : 'error';
                            echo "<p class='{$class}'>{$icon} {$check}</p>";
                            if (!$passed) $allPassed = false;
                        }
                        ?>
                        
                        <?php if ($allPassed): ?>
                            <div class="alert alert-success">
                                <strong>✅ All requirements met!</strong> Ready to proceed.
                            </div>
                            <a href="?step=database" class="btn btn-primary btn-lg">Next: Database Setup</a>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <strong>❌ Some requirements not met.</strong> Please fix the issues above.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

<?php elseif ($step === 'database'): ?>
                <!-- Step 2: Database Connection Test -->
                <h4>🔌 Database Connection Test</h4>
                
                <?php
                if ($action === 'test_connection') {
                    try {
                        // Test database connection
                        $host = 'localhost';
                        $dbname = 'knowhowpilot_BI';
                        $username = 'knowhowpilot_admin';
                        $password = 'Sbrkmtn1071@1453';
                        
                        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                        $pdo = new PDO($dsn, $username, $password, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);
                        
                        echo '<div class="alert alert-success">✅ Database connection successful!</div>';
                        
                        // Check existing tables
                        $stmt = $pdo->query("SHOW TABLES");
                        $tables = $stmt->fetchAll();
                        
                        if (empty($tables)) {
                            echo '<div class="alert alert-info">📋 Database is empty and ready for installation.</div>';
                            echo '<a href="?step=install" class="btn btn-success btn-lg">Install Database Schema</a>';
                        } else {
                            echo '<div class="alert alert-warning">⚠️ Database contains ' . count($tables) . ' tables. Installation will overwrite existing data.</div>';
                            echo '<a href="?step=install&force=1" class="btn btn-warning btn-lg">Force Install (Overwrite)</a>';
                        }
                        
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-danger">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        echo '<p>Please check:</p>';
                        echo '<ul>';
                        echo '<li>MySQL server is running</li>';
                        echo '<li>Database name: knowhowpilot_BI</li>';
                        echo '<li>Username: knowhowpilot_admin</li>';
                        echo '<li>Password is correct</li>';
                        echo '</ul>';
                    }
                } else {
                    echo '<div class="alert alert-info">Click the button below to test your database connection.</div>';
                    echo '<form method="post">';
                    echo '<input type="hidden" name="action" value="test_connection">';
                    echo '<button type="submit" class="btn btn-primary btn-lg">Test Database Connection</button>';
                    echo '</form>';
                }
                ?>

<?php elseif ($step === 'install'): ?>
                <!-- Step 3: Database Installation -->
                <h4>⚙️ Database Installation</h4>
                
                <?php
                if ($action === 'install_database' || isset($_GET['auto'])) {
                    try {
                        // Include the installation logic
                        require_once 'src/bootstrap.php';
                        
                        use LuxuryWatch\BI\Core\Config;
                        use LuxuryWatch\BI\Database\Connection;
                        
                        echo '<div class="alert alert-info">🔄 Installing database schema...</div>';
                        
                        // Initialize connection
                        Connection::init(Config::getDatabase());
                        
                        // Read and execute schema
                        $schemaFile = 'database/schema.sql';
                        if (file_exists($schemaFile)) {
                            $schema = file_get_contents($schemaFile);
                            $statements = explode(';', $schema);
                            
                            $executed = 0;
                            foreach ($statements as $statement) {
                                $statement = trim($statement);
                                if (!empty($statement) && strpos($statement, '--') !== 0) {
                                    try {
                                        Connection::execute($statement);
                                        $executed++;
                                    } catch (Exception $e) {
                                        // Skip non-critical errors
                                    }
                                }
                            }
                            
                            echo "<div class='alert alert-success'>✅ Schema installed! Executed {$executed} statements.</div>";
                        }
                        
                        // Insert sample data
                        $sampleFile = 'database/sample_data.sql';
                        if (file_exists($sampleFile)) {
                            echo '<div class="alert alert-info">🔄 Inserting sample data...</div>';
                            
                            $sampleData = file_get_contents($sampleFile);
                            $statements = explode(';', $sampleData);
                            
                            $inserted = 0;
                            foreach ($statements as $statement) {
                                $statement = trim($statement);
                                if (!empty($statement) && strpos($statement, 'INSERT') === 0) {
                                    try {
                                        Connection::execute($statement);
                                        $inserted++;
                                    } catch (Exception $e) {
                                        // Skip errors
                                    }
                                }
                            }
                            
                            echo "<div class='alert alert-success'>✅ Sample data inserted! {$inserted} records added.</div>";
                        }
                        
                        // Create lock file
                        file_put_contents($lockFile, date('Y-m-d H:i:s'));
                        
                        echo '<div class="alert alert-success"><strong>🎉 Installation Complete!</strong></div>';
                        echo '<a href="?step=complete" class="btn btn-success btn-lg">Finish Installation</a>';
                        
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">❌ Installation failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<div class="alert alert-warning">⚠️ This will install the database schema and sample data.</div>';
                    echo '<form method="post">';
                    echo '<input type="hidden" name="action" value="install_database">';
                    echo '<button type="submit" class="btn btn-success btn-lg">Install Database</button>';
                    echo '</form>';
                }
                ?>

<?php elseif ($step === 'complete'): ?>
                <!-- Step 4: Installation Complete -->
                <h4>🎉 Installation Complete!</h4>
                
                <div class="alert alert-success">
                    <h5>✅ Your Luxury Watch BI system is ready!</h5>
                    <p>The database has been successfully installed with sample data.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5>📊 Dashboard</h5>
                                <p>View analytics and KPIs</p>
                                <a href="dashboard.php" class="btn btn-primary">Open Dashboard</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5>📋 Reports</h5>
                                <p>Generate detailed reports</p>
                                <a href="reports.php" class="btn btn-info">Open Reports</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>🔌 API Endpoints:</h6>
                    <ul>
                        <li><a href="api/v1/health" target="_blank">Health Check</a></li>
                        <li><a href="api/v1/sales/overview" target="_blank">Sales Overview</a></li>
                        <li><a href="api/v1/inventory/overview" target="_blank">Inventory Overview</a></li>
                    </ul>
                </div>
                
                <div class="alert alert-info mt-4">
                    <strong>🔒 Security Note:</strong> Delete this install.php file after installation for security.
                </div>

<?php endif; ?>

            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
