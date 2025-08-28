<?php

/**
 * Database Installation Script
 * Sets up the database schema and sample data
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

use LuxuryWatch\BI\Core\Config;
use LuxuryWatch\BI\Database\Connection;

echo "=== Luxury Watch BI Database Installation ===\n\n";

try {
    // Load configuration
    echo "Loading configuration...\n";
    Config::load();
    
    // Validate configuration
    $errors = Config::validate();
    if (!empty($errors)) {
        throw new Exception("Configuration errors:\n" . implode("\n", $errors));
    }
    
    echo "Configuration loaded successfully.\n\n";
    
    // Initialize database connection
    echo "Connecting to database...\n";
    Connection::init(Config::getDatabase());
    
    if (!Connection::isConnected()) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "Database connection established.\n\n";
    
    // Read and execute schema
    echo "Creating database schema...\n";
    $schemaFile = dirname(__DIR__) . '/database/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: {$schemaFile}");
    }
    
    $schema = file_get_contents($schemaFile);
    $statements = explode(';', $schema);
    
    $executedStatements = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                Connection::execute($statement);
                $executedStatements++;
            } catch (Exception $e) {
                // Skip comments and empty statements
                if (strpos($statement, '--') !== 0 && !empty($statement)) {
                    echo "Warning: Failed to execute statement: " . substr($statement, 0, 50) . "...\n";
                    echo "Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "Schema created successfully. Executed {$executedStatements} statements.\n\n";
    
    // Insert sample data
    echo "Inserting sample data...\n";
    $sampleDataFile = dirname(__DIR__) . '/database/sample_data.sql';
    
    if (file_exists($sampleDataFile)) {
        $sampleData = file_get_contents($sampleDataFile);
        $statements = explode(';', $sampleData);
        
        $insertedRecords = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && strpos($statement, 'INSERT') === 0) {
                try {
                    Connection::execute($statement);
                    $insertedRecords++;
                } catch (Exception $e) {
                    echo "Warning: Failed to insert sample data: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "Sample data inserted successfully. {$insertedRecords} insert statements executed.\n\n";
    } else {
        echo "Sample data file not found. Skipping sample data insertion.\n\n";
    }
    
    // Create additional sample sales orders
    echo "Generating additional sample data...\n";
    generateSampleSalesOrders();
    generateInventoryMovements();
    
    echo "Additional sample data generated.\n\n";
    
    // Verify installation
    echo "Verifying installation...\n";
    $verification = verifyInstallation();
    
    if ($verification['success']) {
        echo "✓ Installation verification successful!\n\n";
        echo "Database Statistics:\n";
        foreach ($verification['stats'] as $table => $count) {
            echo "  - {$table}: {$count} records\n";
        }
        echo "\n";
        
        echo "=== Installation Complete ===\n";
        echo "Your Luxury Watch BI system is ready to use!\n";
        echo "Access the dashboard at: http://your-domain/dashboard.php\n";
        echo "API endpoint: http://your-domain/api/\n\n";
        
    } else {
        echo "✗ Installation verification failed!\n";
        echo "Errors: " . implode(', ', $verification['errors']) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Installation failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your configuration and try again.\n";
    exit(1);
}

function generateSampleSalesOrders(): void
{
    echo "  Generating sample sales orders...\n";
    
    // Get customers and products
    $customers = Connection::fetchAll("SELECT id FROM customers LIMIT 10");
    $products = Connection::fetchAll("SELECT id, price FROM products LIMIT 10");
    $stores = Connection::fetchAll("SELECT id FROM stores LIMIT 5");
    
    if (empty($customers) || empty($products) || empty($stores)) {
        echo "  Skipping sales orders - missing base data\n";
        return;
    }
    
    $orderStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered'];
    $paymentMethods = ['Credit Card', 'Bank Transfer', 'Cash'];
    
    for ($i = 1; $i <= 50; $i++) {
        $customer = $customers[array_rand($customers)];
        $store = $stores[array_rand($stores)];
        $status = $orderStatuses[array_rand($orderStatuses)];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        
        $orderDate = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        
        // Insert order
        $orderSql = "INSERT INTO sales_orders (order_number, customer_id, store_id, order_date, status, payment_method, payment_status, subtotal, total_amount, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Paid', 0, 0, ?)";
        
        Connection::execute($orderSql, [
            $orderNumber,
            $customer['id'],
            $store['id'],
            $orderDate,
            $status,
            $paymentMethod,
            $orderDate
        ]);
        
        $orderId = Connection::lastInsertId();
        
        // Add order items
        $numItems = rand(1, 3);
        $orderTotal = 0;
        
        for ($j = 0; $j < $numItems; $j++) {
            $product = $products[array_rand($products)];
            $quantity = rand(1, 2);
            $unitPrice = $product['price'];
            $lineTotal = $quantity * $unitPrice;
            $orderTotal += $lineTotal;
            
            $itemSql = "INSERT INTO sales_order_items (order_id, product_id, quantity, unit_price, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            
            Connection::execute($itemSql, [
                $orderId,
                $product['id'],
                $quantity,
                $unitPrice,
                $lineTotal,
                $orderDate
            ]);
        }
        
        // Update order total
        Connection::execute("UPDATE sales_orders SET subtotal = ?, total_amount = ? WHERE id = ?", [
            $orderTotal,
            $orderTotal,
            $orderId
        ]);
    }
    
    echo "  Generated 50 sample sales orders\n";
}

function generateInventoryMovements(): void
{
    echo "  Generating inventory movements...\n";
    
    $products = Connection::fetchAll("SELECT id FROM products LIMIT 10");
    $locations = Connection::fetchAll("SELECT id FROM inventory_locations LIMIT 3");
    
    if (empty($products) || empty($locations)) {
        echo "  Skipping inventory movements - missing base data\n";
        return;
    }
    
    $movementTypes = ['IN', 'OUT', 'ADJUSTMENT'];
    $referenceTypes = ['Purchase', 'Sale', 'Adjustment'];
    
    for ($i = 0; $i < 100; $i++) {
        $product = $products[array_rand($products)];
        $location = $locations[array_rand($locations)];
        $movementType = $movementTypes[array_rand($movementTypes)];
        $referenceType = $referenceTypes[array_rand($referenceTypes)];
        
        $quantity = rand(1, 10);
        if ($movementType === 'OUT') {
            $quantity = -$quantity;
        }
        
        $movementDate = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
        
        $sql = "INSERT INTO inventory_movements (product_id, location_id, movement_type, quantity, reference_type, movement_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        Connection::execute($sql, [
            $product['id'],
            $location['id'],
            $movementType,
            abs($quantity),
            $referenceType,
            $movementDate,
            $movementDate
        ]);
    }
    
    echo "  Generated 100 inventory movements\n";
}

function verifyInstallation(): array
{
    $errors = [];
    $stats = [];
    
    // Check required tables
    $requiredTables = [
        'brands', 'categories', 'products', 'customers', 'stores',
        'sales_orders', 'sales_order_items', 'inventory', 'inventory_locations'
    ];
    
    foreach ($requiredTables as $table) {
        try {
            $result = Connection::fetchOne("SELECT COUNT(*) as count FROM {$table}");
            $stats[$table] = $result['count'];
            
            if ($result['count'] == 0) {
                $errors[] = "Table {$table} is empty";
            }
        } catch (Exception $e) {
            $errors[] = "Table {$table} not found or inaccessible";
        }
    }
    
    // Check database connection
    if (!Connection::isConnected()) {
        $errors[] = "Database connection failed";
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors,
        'stats' => $stats
    ];
}
