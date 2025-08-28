# 🚀 Hızlı Başlangıç - Luxury Watch BI

## ✅ Sistem Hazır!

Tüm dosyalar ana dizinde hazır durumda. Artık web sunucunuzda çalıştırabilirsiniz.

## 📁 Dosya Yapısı

```
BusinessIntelligence/
├── dashboard.php          # Ana dashboard
├── reports.php           # Raporlar sayfası
├── index.php            # Ana sayfa (dashboard'a yönlendirir)
├── api/                 # API endpoints
├── .htaccess           # Apache ayarları
├── .env                # Veritabanı ayarları (HAZIR!)
├── src/                # PHP sınıfları
├── database/           # SQL dosyaları
└── scripts/           # Kurulum scriptleri
```

## 🎯 3 Adımda Başlayın

### 1️⃣ Veritabanını Kurun
```bash
php scripts/install_database.php
```

### 2️⃣ Web Sunucusunu Ayarlayın

#### XAMPP Kullanıyorsanız:
1. Bu klasörü `C:\xampp\htdocs\` içine kopyalayın
2. XAMPP'ı başlatın (Apache + MySQL)
3. Tarayıcıda: `http://localhost/BusinessIntelligence/`

#### WAMP Kullanıyorsanız:
1. Bu klasörü `C:\wamp64\www\` içine kopyalayın
2. WAMP'ı başlatın
3. Tarayıcıda: `http://localhost/BusinessIntelligence/`

#### Canlı Sunucuda (knowhowpilot.com):
1. Dosyaları FTP ile yükleyin
2. Domain'i bu klasöre yönlendirin
3. Tarayıcıda: `https://knowhowpilot.com/`

### 3️⃣ Sistemi Kullanın

- **Dashboard:** `https://knowhowpilot.com/dashboard.php`
- **Raporlar:** `https://knowhowpilot.com/reports.php`
- **API Test:** `https://knowhowpilot.com/api/v1/health`

**Yerel Test için:**
- **Dashboard:** `http://localhost/BusinessIntelligence/dashboard.php`
- **Raporlar:** `http://localhost/BusinessIntelligence/reports.php`
- **API Test:** `http://localhost/BusinessIntelligence/api/v1/health`

## 🔧 Veritabanı Bilgileri

✅ **Otomatik Ayarlandı:**
- Veritabanı: `knowhowpilot_BI`
- Kullanıcı: `knowhowpilot_admin`
- Şifre: `Sbrkmtn1071@1453`

## 🆘 Sorun mu Var?

### Veritabanı Bağlantı Testi:
```bash
php simple_test.php
```

### Yaygın Sorunlar:

**"Database connection failed"**
- MySQL sunucusunun çalıştığını kontrol edin
- Veritabanı bilgilerini kontrol edin

**"404 Not Found"**
- Web sunucusunun çalıştığını kontrol edin
- Dosya yollarını kontrol edin

**"Internal Server Error"**
- `logs/app.log` dosyasını kontrol edin
- PHP error log'larını kontrol edin

## 📊 Ne Yapabilirsiniz?

### Dashboard'da:
- 📈 Satış trendleri
- 💰 Gelir analizi
- 👥 Müşteri metrikleri
- 📦 Envanter durumu

### Raporlarda:
- 📋 Satış özet raporu
- 👥 Müşteri analiz raporu
- 📦 Envanter durum raporu
- 📤 CSV/JSON export

### API ile:
- 🔌 RESTful endpoints
- 📊 JSON veri servisleri
- 🔄 Real-time veriler

## 🎉 Başarılar!

Sisteminiz hazır! Lüks saat firmanız için güçlü bir BI aracınız var.

**İlk adım:** Veritabanını kurun ve dashboard'ı açın! 🚀
