<?php
/**
 * Hızlı Kurulum - PHP 7.4 Uyumlu
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Luxury Watch BI - Hızlı Kurulum</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

$step = isset($_GET['step']) ? $_GET['step'] : '1';

if ($step == '1') {
    echo "<h2>📋 Adım 1: Dosya Kontrolü ve Oluşturma</h2>";
    
    // .env dosyası oluştur
    $envContent = '# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=knowhowpilot_BI
DB_USERNAME=knowhowpilot_admin
DB_PASSWORD=Sbrkmtn1071@1453
DB_CHARSET=utf8mb4

# Application Configuration
APP_NAME="Luxury Watch BI"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Europe/Istanbul
APP_URL=https://knowhowpilot.com

# Security
APP_SECRET_KEY=lw_bi_secret_key_2024_secure_random_string
JWT_SECRET=jwt_secret_for_luxury_watch_bi_system
ENCRYPTION_KEY=encryption_key_for_data_protection
';

    if (file_put_contents('.env', $envContent)) {
        echo "<p style='color: green;'>✅ .env dosyası oluşturuldu</p>";
    } else {
        echo "<p style='color: red;'>❌ .env dosyası oluşturulamadı</p>";
    }
    
    // Gerekli klasörleri oluştur
    $dirs = ['logs', 'cache', 'vendor'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0777, true)) {
                echo "<p style='color: green;'>✅ {$dir} klasörü oluşturuldu</p>";
            } else {
                echo "<p style='color: red;'>❌ {$dir} klasörü oluşturulamadı</p>";
            }
        } else {
            echo "<p style='color: blue;'>📁 {$dir} klasörü zaten var</p>";
        }
    }
    
    // Basit autoloader oluştur
    $autoloaderContent = '<?php
// Basit Autoloader
spl_autoload_register(function ($class) {
    $prefix = "LuxuryWatch\\\\BI\\\\";
    $base_dir = __DIR__ . "/../src/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});
?>';

    if (!is_dir('vendor')) mkdir('vendor', 0777, true);
    if (file_put_contents('vendor/autoload.php', $autoloaderContent)) {
        echo "<p style='color: green;'>✅ Autoloader oluşturuldu</p>";
    }
    
    echo "<a href='?step=2' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Veritabanı Kurulumuna Geç</a>";
    
} elseif ($step == '2') {
    echo "<h2>⚙️ Adım 2: Veritabanı Kurulumu</h2>";
    
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=knowhowpilot_BI;charset=utf8mb4', 
                      'knowhowpilot_admin', 
                      'Sbrkmtn1071@1453',
                      array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        
        echo "<p>🔄 Tabloları oluşturuyor...</p>";
        
        // Brands tablosu
        $pdo->exec("CREATE TABLE IF NOT EXISTS brands (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            country VARCHAR(50),
            founded_year INT,
            luxury_tier ENUM('Ultra', 'High', 'Medium') DEFAULT 'High',
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "✅ Brands tablosu<br>";
        
        // Categories tablosu
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            parent_id INT NULL,
            description TEXT,
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id)
        )");
        echo "✅ Categories tablosu<br>";
        
        // Products tablosu
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sku VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(200) NOT NULL,
            brand_id INT,
            category_id INT,
            model VARCHAR(100),
            movement_type ENUM('Automatic', 'Mechanical', 'Quartz') DEFAULT 'Automatic',
            case_material VARCHAR(50),
            case_diameter DECIMAL(5,2),
            water_resistance INT,
            price DECIMAL(12,2) NOT NULL,
            cost DECIMAL(12,2) NOT NULL,
            weight DECIMAL(8,2),
            limited_edition BOOLEAN DEFAULT FALSE,
            status ENUM('Active', 'Inactive', 'Discontinued') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (brand_id) REFERENCES brands(id),
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )");
        echo "✅ Products tablosu<br>";
        
        // Customers tablosu
        $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_code VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            phone VARCHAR(20),
            birth_date DATE,
            gender ENUM('M', 'F', 'Other'),
            nationality VARCHAR(50),
            customer_tier ENUM('Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond') DEFAULT 'Bronze',
            total_spent DECIMAL(15,2) DEFAULT 0,
            total_orders INT DEFAULT 0,
            last_purchase_date DATE,
            registration_date DATE NOT NULL,
            status ENUM('Active', 'Inactive', 'VIP') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "✅ Customers tablosu<br>";
        
        // Stores tablosu
        $pdo->exec("CREATE TABLE IF NOT EXISTS stores (
            id INT PRIMARY KEY AUTO_INCREMENT,
            store_code VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(150) NOT NULL,
            type ENUM('Physical', 'Online', 'Boutique') DEFAULT 'Physical',
            address TEXT,
            city VARCHAR(100),
            country VARCHAR(100),
            manager_name VARCHAR(150),
            phone VARCHAR(20),
            email VARCHAR(150),
            opening_date DATE,
            status ENUM('Active', 'Inactive', 'Maintenance') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "✅ Stores tablosu<br>";
        
        // Sales Orders tablosu
        $pdo->exec("CREATE TABLE IF NOT EXISTS sales_orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            customer_id INT NOT NULL,
            store_id INT NOT NULL,
            order_date DATETIME NOT NULL,
            status ENUM('Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
            payment_method ENUM('Credit Card', 'Bank Transfer', 'Cash', 'Installment') DEFAULT 'Credit Card',
            payment_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
            subtotal DECIMAL(15,2) NOT NULL,
            tax_amount DECIMAL(15,2) DEFAULT 0,
            discount_amount DECIMAL(15,2) DEFAULT 0,
            shipping_amount DECIMAL(15,2) DEFAULT 0,
            total_amount DECIMAL(15,2) NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (store_id) REFERENCES stores(id)
        )");
        echo "✅ Sales Orders tablosu<br>";
        
        echo "<p style='color: green;'>🎉 Temel tablolar oluşturuldu!</p>";
        echo "<a href='?step=3' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Örnek Veri Ekle</a>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
    }
    
} elseif ($step == '3') {
    echo "<h2>📊 Adım 3: Örnek Veri Ekleme</h2>";
    
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=knowhowpilot_BI;charset=utf8mb4', 
                      'knowhowpilot_admin', 
                      'Sbrkmtn1071@1453',
                      array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        
        // Brands
        $pdo->exec("INSERT IGNORE INTO brands (name, country, founded_year, luxury_tier) VALUES 
                   ('Rolex', 'Switzerland', 1905, 'Ultra'),
                   ('Patek Philippe', 'Switzerland', 1839, 'Ultra'),
                   ('Audemars Piguet', 'Switzerland', 1875, 'Ultra'),
                   ('Omega', 'Switzerland', 1848, 'High'),
                   ('TAG Heuer', 'Switzerland', 1860, 'High')");
        echo "✅ Markalar eklendi<br>";
        
        // Categories
        $pdo->exec("INSERT IGNORE INTO categories (name, description) VALUES 
                   ('Luxury Watches', 'High-end timepieces'),
                   ('Sports Watches', 'Athletic and diving watches'),
                   ('Dress Watches', 'Elegant formal timepieces')");
        echo "✅ Kategoriler eklendi<br>";
        
        // Products
        $pdo->exec("INSERT IGNORE INTO products (sku, name, brand_id, category_id, price, cost) VALUES 
                   ('RLX-SUB-001', 'Submariner Date', 1, 2, 285000.00, 142500.00),
                   ('PP-CAL-001', 'Calatrava', 2, 3, 850000.00, 425000.00),
                   ('AP-ROO-001', 'Royal Oak Offshore', 3, 2, 750000.00, 375000.00),
                   ('OMG-SPD-001', 'Speedmaster Professional', 4, 2, 165000.00, 82500.00),
                   ('TAG-CAR-001', 'Carrera Chronograph', 5, 2, 95000.00, 47500.00)");
        echo "✅ Ürünler eklendi<br>";
        
        // Stores
        $pdo->exec("INSERT IGNORE INTO stores (store_code, name, type, city, country) VALUES 
                   ('ST-IST-001', 'İstanbul Merkez Mağaza', 'Physical', 'Istanbul', 'Turkey'),
                   ('ST-ANK-001', 'Ankara Çankaya Store', 'Physical', 'Ankara', 'Turkey'),
                   ('ST-ONL-001', 'Online Store', 'Online', 'Istanbul', 'Turkey')");
        echo "✅ Mağazalar eklendi<br>";
        
        // Customers
        $pdo->exec("INSERT IGNORE INTO customers (customer_code, first_name, last_name, email, customer_tier, registration_date) VALUES 
                   ('CUST-000001', 'Ahmet', 'Yılmaz', 'ahmet.yilmaz@email.com', 'Gold', '2024-01-20'),
                   ('CUST-000002', 'Elif', 'Kaya', 'elif.kaya@email.com', 'Platinum', '2024-02-10'),
                   ('CUST-000003', 'Mehmet', 'Demir', 'mehmet.demir@email.com', 'Silver', '2024-03-15')");
        echo "✅ Müşteriler eklendi<br>";
        
        echo "<p style='color: green;'>🎉 Örnek veriler başarıyla eklendi!</p>";
        echo "<a href='?step=4' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Kurulumu Tamamla</a>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
    }
    
} elseif ($step == '4') {
    echo "<h2>🎉 Kurulum Tamamlandı!</h2>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Luxury Watch BI Sistemi Hazır!</h3>";
    echo "<p>Veritabanı başarıyla kuruldu ve örnek veriler eklendi.</p>";
    echo "</div>";
    
    echo "<h3>🔗 Sisteminizi Test Edin:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>📊 Dashboard:</strong> <a href='dashboard.php' target='_blank'>dashboard.php</a></p>";
    echo "<p><strong>📋 Raporlar:</strong> <a href='reports.php' target='_blank'>reports.php</a></p>";
    echo "<p><strong>🔌 API Test:</strong> <a href='api/v1/health' target='_blank'>api/v1/health</a></p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>🔒 Güvenlik:</strong> Kurulum tamamlandıktan sonra bu quick_install.php dosyasını silin.";
    echo "</div>";
    
    echo "<h3>📈 Sistem Özellikleri:</h3>";
    echo "<ul>";
    echo "<li>✅ Satış analitikleri ve trendler</li>";
    echo "<li>✅ Müşteri segmentasyonu</li>";
    echo "<li>✅ Envanter yönetimi</li>";
    echo "<li>✅ Dinamik raporlama</li>";
    echo "<li>✅ RESTful API</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><small>KnowHow Pilot - Luxury Watch BI System | PHP " . PHP_VERSION . "</small></p>";
?>
