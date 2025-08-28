<?php
/**
 * Basit Kurulum Dosyası
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Luxury Watch BI - Basit Kurulum</h1>";

$step = $_GET['step'] ?? '1';

if ($step == '1') {
    echo "<h2>📋 Adım 1: Sistem Kontrolü</h2>";
    
    echo "<p>PHP Version: " . PHP_VERSION . "</p>";
    echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "</p>";
    
    echo "<h3>🔌 Veritabanı Testi:</h3>";
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=knowhowpilot_BI;charset=utf8mb4', 
                      'knowhowpilot_admin', 
                      'Sbrkmtn1071@1453',
                      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        echo "<p style='color: green;'>✅ Veritabanı bağlantısı başarılı!</p>";
        
        // Tabloları kontrol et
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        
        if (empty($tables)) {
            echo "<p>📋 Veritabanı boş, kuruluma hazır.</p>";
            echo "<a href='?step=2' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Kuruluma Başla</a>";
        } else {
            echo "<p>⚠️ Veritabanında " . count($tables) . " tablo var.</p>";
            echo "<a href='?step=2&force=1' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Yine de Kur</a>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Veritabanı hatası: " . $e->getMessage() . "</p>";
        echo "<p>Lütfen veritabanı bilgilerini kontrol edin.</p>";
    }
    
} elseif ($step == '2') {
    echo "<h2>⚙️ Adım 2: Veritabanı Kurulumu</h2>";
    
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=knowhowpilot_BI;charset=utf8mb4', 
                      'knowhowpilot_admin', 
                      'Sbrkmtn1071@1453',
                      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        echo "<p>🔄 Tabloları oluşturuyor...</p>";
        
        // Basit tablo oluşturma
        $tables = [
            "CREATE TABLE IF NOT EXISTS brands (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                country VARCHAR(50),
                founded_year INT,
                luxury_tier ENUM('Ultra', 'High', 'Medium') DEFAULT 'High',
                status ENUM('Active', 'Inactive') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS products (
                id INT PRIMARY KEY AUTO_INCREMENT,
                sku VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(200) NOT NULL,
                brand_id INT,
                price DECIMAL(12,2) NOT NULL,
                cost DECIMAL(12,2) NOT NULL,
                status ENUM('Active', 'Inactive') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (brand_id) REFERENCES brands(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS customers (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_code VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                phone VARCHAR(20),
                customer_tier ENUM('Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond') DEFAULT 'Bronze',
                total_spent DECIMAL(15,2) DEFAULT 0,
                total_orders INT DEFAULT 0,
                registration_date DATE NOT NULL,
                status ENUM('Active', 'Inactive', 'VIP') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $sql) {
            $pdo->exec($sql);
            echo "✅ Tablo oluşturuldu<br>";
        }
        
        // Örnek veri ekle
        echo "<p>📊 Örnek veriler ekleniyor...</p>";
        
        $pdo->exec("INSERT IGNORE INTO brands (name, country, founded_year, luxury_tier) VALUES 
                   ('Rolex', 'Switzerland', 1905, 'Ultra'),
                   ('Omega', 'Switzerland', 1848, 'High'),
                   ('TAG Heuer', 'Switzerland', 1860, 'High')");
        
        $pdo->exec("INSERT IGNORE INTO products (sku, name, brand_id, price, cost) VALUES 
                   ('RLX-001', 'Submariner Date', 1, 285000.00, 142500.00),
                   ('OMG-001', 'Speedmaster Professional', 2, 165000.00, 82500.00),
                   ('TAG-001', 'Carrera Chronograph', 3, 95000.00, 47500.00)");
        
        $pdo->exec("INSERT IGNORE INTO customers (customer_code, first_name, last_name, email, customer_tier, registration_date) VALUES 
                   ('CUST-001', 'Ahmet', 'Yılmaz', 'ahmet@test.com', 'Gold', '2024-01-15'),
                   ('CUST-002', 'Elif', 'Kaya', 'elif@test.com', 'Platinum', '2024-02-20'),
                   ('CUST-003', 'Mehmet', 'Demir', 'mehmet@test.com', 'Silver', '2024-03-10')");
        
        echo "<p style='color: green;'>🎉 Kurulum başarıyla tamamlandı!</p>";
        echo "<a href='?step=3' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Sistemi Test Et</a>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Kurulum hatası: " . $e->getMessage() . "</p>";
    }
    
} elseif ($step == '3') {
    echo "<h2>🎉 Adım 3: Kurulum Tamamlandı!</h2>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Sisteminiz Hazır!</h3>";
    echo "<p>Luxury Watch BI sistemi başarıyla kuruldu.</p>";
    echo "</div>";
    
    echo "<h3>🔗 Erişim Linkleri:</h3>";
    echo "<ul>";
    echo "<li><a href='dashboard.php' target='_blank'>📊 Dashboard</a></li>";
    echo "<li><a href='reports.php' target='_blank'>📋 Raporlar</a></li>";
    echo "<li><a href='api/v1/health' target='_blank'>🔌 API Health Check</a></li>";
    echo "</ul>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>🔒 Güvenlik:</strong> Kurulum tamamlandıktan sonra bu setup.php dosyasını silin.";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>KnowHow Pilot - Luxury Watch BI System</small></p>";
?>
