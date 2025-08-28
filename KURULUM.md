# Luxury Watch BI - Kurulum Rehberi

## 🎯 Sistem Gereksinimleri

- PHP 8.0 veya üzeri
- MySQL 5.7 veya üzeri
- Web sunucusu (Apache/Nginx)
- PDO MySQL extension

## 📋 Kurulum Adımları

### 1. Veritabanı Bilgileriniz
✅ **Veritabanı Adı:** knowhowpilot_BI
✅ **Kullanıcı Adı:** knowhowpilot_admin  
✅ **Şifre:** Sbrkmtn1071@1453

### 2. Dosyalar Hazır
Tüm dosyalar oluşturuldu ve veritabanı bilgileriniz `.env` dosyasına kaydedildi.

### 3. Veritabanı Kurulumu

#### Otomatik Kurulum (Önerilen):
```bash
php scripts/install_database.php
```

#### Manuel Kurulum:
1. `database/schema.sql` dosyasını MySQL'de çalıştırın
2. `database/sample_data.sql` dosyasını MySQL'de çalıştırın

### 4. Web Sunucusu Ayarları

#### Apache için:
- Ana proje klasörünü DocumentRoot olarak ayarlayın
- `.htaccess` dosyası mod_rewrite gerektirir
- Örnek: `C:\Users\LENOVO\Desktop\BI\BusinessIntelligence`

#### Nginx için:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/BusinessIntelligence;
    index dashboard.php;

    location / {
        try_files $uri $uri/ /dashboard.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index dashboard.php;
        include fastcgi_params;
    }
}
```

#### XAMPP/WAMP için:
- Proje klasörünü `htdocs` içine kopyalayın
- Örnek: `C:\xampp\htdocs\BusinessIntelligence\`
- Erişim: `http://localhost/BusinessIntelligence/`

## 🚀 Erişim Adresleri

### Dashboard
```
http://your-domain/dashboard.php
```

### Raporlar
```
http://your-domain/reports.php
```

### API Endpoints
```
http://your-domain/api/v1/sales/overview
http://your-domain/api/v1/customers/rfm-analysis
http://your-domain/api/v1/inventory/overview
```

## 📊 Örnek API Kullanımı

### Satış Genel Bakış
```bash
curl "http://your-domain/api/v1/sales/overview?date_range=30d"
```

### Müşteri Segmentasyonu
```bash
curl "http://your-domain/api/v1/customers/rfm-analysis"
```

### Envanter Durumu
```bash
curl "http://your-domain/api/v1/inventory/stock-levels"
```

## 🔧 Konfigürasyon

### .env Dosyası Ayarları
Gerekirse `.env` dosyasındaki ayarları düzenleyebilirsiniz:

```env
# Uygulama Ayarları
APP_NAME="Luxury Watch BI"
APP_ENV=production  # production için
APP_DEBUG=false     # production için

# Güvenlik
APP_SECRET_KEY=your_unique_secret_key
```

## 🛠️ Sorun Giderme

### Veritabanı Bağlantı Hatası
1. MySQL sunucusunun çalıştığını kontrol edin
2. Veritabanı bilgilerini doğrulayın
3. Kullanıcının gerekli izinlere sahip olduğunu kontrol edin

### Dosya İzin Hataları
```bash
chmod 755 public/
chmod 777 logs/
chmod 777 cache/
```

### Apache mod_rewrite Hatası
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 📈 Özellikler

### ✅ Tamamlanan Modüller
- 📊 Satış Analitikleri
- 👥 Müşteri Segmentasyonu  
- 📦 Envanter Yönetimi
- 📋 Raporlama Sistemi
- 🔌 RESTful API
- 📱 Responsive Dashboard

### 🎯 Analitik Yetenekleri
- RFM Müşteri Segmentasyonu
- Cohort Analizi
- ABC Envanter Analizi
- Satış Trend Analizi
- Inventory Turnover
- Customer Lifetime Value

## 🔒 Güvenlik

- PDO Prepared Statements
- Input Sanitization
- CORS Güvenliği
- Environment Variables
- SQL Injection Koruması

## 📞 Destek

Herhangi bir sorun yaşarsanız:
1. Log dosyalarını kontrol edin: `logs/app.log`
2. Veritabanı bağlantısını test edin: `php simple_test.php`
3. API health check: `http://your-domain/api/v1/health`

---

**🎉 Kurulum tamamlandığında lüks saat firmanız için güçlü bir BI sisteminiz hazır olacak!**
