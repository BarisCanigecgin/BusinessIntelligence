<?php
// Basit test dosyası
echo "PHP çalışıyor! ✅<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Tarih: " . date('Y-m-d H:i:s') . "<br>";

// Veritabanı test
try {
    $pdo = new PDO('mysql:host=localhost;dbname=knowhowpilot_BI', 'knowhowpilot_admin', 'Sbrkmtn1071@1453');
    echo "Veritabanı bağlantısı: ✅ Başarılı<br>";
} catch (Exception $e) {
    echo "Veritabanı bağlantısı: ❌ Hata: " . $e->getMessage() . "<br>";
}

// Dosya kontrolü
echo "Dosyalar:<br>";
echo "- .env dosyası: " . (file_exists('.env') ? '✅' : '❌') . "<br>";
echo "- src klasörü: " . (is_dir('src') ? '✅' : '❌') . "<br>";
echo "- vendor klasörü: " . (is_dir('vendor') ? '✅' : '❌') . "<br>";

phpinfo();
?>
