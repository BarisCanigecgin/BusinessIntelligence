# Luxury Watch Business Intelligence System

Lüks saat firması için kapsamlı Business Intelligence ve analitik sistemi.

## Özellikler

- 📊 Kapsamlı satış analitikleri
- 👥 Müşteri segmentasyonu ve analizi
- 📦 Envanter yönetimi ve optimizasyonu
- 💰 Finansal raporlama
- 🎯 Performans göstergeleri (KPI)
- 📈 Trend analizi ve tahminleme
- 🔒 Güvenli veri erişimi
- 🌐 RESTful API desteği

## Teknolojiler

- **Backend**: PHP 8.0+, PDO
- **Veritabanı**: MySQL/MariaDB
- **Güvenlik**: Environment variables, prepared statements
- **Mimari**: Modüler, PSR-4 autoloading
- **Test**: PHPUnit
- **Analiz**: PHPStan

## Kurulum

1. Projeyi klonlayın:
```bash
git clone <repository-url>
cd BusinessIntelligence
```

2. Bağımlılıkları yükleyin:
```bash
composer install
```

3. Environment dosyasını oluşturun:
```bash
cp .env.example .env
```

4. `.env` dosyasını düzenleyin ve veritabanı bilgilerinizi girin.

5. Veritabanını kurun:
```bash
composer run install-db
```

## Proje Yapısı

```
src/
├── Core/           # Temel sınıflar
├── Database/       # Veritabanı katmanı
├── Models/         # Veri modelleri
├── Analytics/      # Analitik modülleri
├── Dashboard/      # Dashboard bileşenleri
├── Reports/        # Raporlama sistemi
├── API/           # API endpoints
└── Utils/         # Yardımcı sınıflar
```

## Kullanım

### API Endpoints

- `GET /api/v1/sales/analytics` - Satış analitikleri
- `GET /api/v1/customers/segments` - Müşteri segmentleri
- `GET /api/v1/inventory/status` - Envanter durumu
- `GET /api/v1/dashboard/kpis` - Ana KPI'lar

### Dashboard

Dashboard'a erişim için `public/dashboard.php` dosyasını kullanın.

## Geliştirme

### Test Çalıştırma
```bash
composer test
```

### Kod Analizi
```bash
composer analyze
```

## Lisans

MIT License
