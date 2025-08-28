<?php
/**
 * Reports Interface
 * Report generation and management system
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

use LuxuryWatch\BI\Core\Report;
use LuxuryWatch\BI\Analytics\SalesAnalytics;
use LuxuryWatch\BI\Analytics\CustomerSegmentation;
use LuxuryWatch\BI\Analytics\InventoryAnalytics;

$action = $_GET['action'] ?? 'list';
$reportType = $_GET['type'] ?? '';

// Handle report generation
if ($action === 'generate' && !empty($reportType)) {
    try {
        $filters = sanitizeInput($_GET);
        $report = new Report();
        
        switch ($reportType) {
            case 'sales_summary':
                $report = $this->generateSalesSummaryReport($filters);
                break;
            case 'customer_analysis':
                $report = $this->generateCustomerAnalysisReport($filters);
                break;
            case 'inventory_status':
                $report = $this->generateInventoryStatusReport($filters);
                break;
            default:
                throw new Exception('Geçersiz rapor türü');
        }
        
        $reportData = $report->generate();
        
        // Handle export
        if (isset($_GET['export'])) {
            $format = $_GET['export'];
            $exportData = $report->export($format);
            
            header('Content-Type: ' . $exportData['mime_type']);
            header('Content-Disposition: attachment; filename="' . $exportData['filename'] . '"');
            echo $exportData['content'];
            exit;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function generateSalesSummaryReport($filters): Report
{
    $report = new Report('Satış Özet Raporu', 'Detaylı satış performans analizi');
    
    $report->setParameters($filters);
    
    // Sales overview section
    $report->addKpiSection('sales_overview', 'Satış Genel Bakış', [
        'query' => 'SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
            FROM sales_orders 
            WHERE status != "Cancelled"'
    ]);
    
    // Top products section
    $report->addTableSection('top_products', 'En Çok Satan Ürünler', [
        'query' => 'SELECT 
            p.name as product_name,
            b.name as brand_name,
            SUM(soi.quantity) as units_sold,
            SUM(soi.line_total) as revenue
            FROM sales_order_items soi
            JOIN products p ON p.id = soi.product_id
            JOIN brands b ON b.id = p.brand_id
            JOIN sales_orders so ON so.id = soi.order_id
            WHERE so.status != "Cancelled"
            GROUP BY p.id, p.name, b.name
            ORDER BY revenue DESC
            LIMIT 20'
    ]);
    
    // Sales by brand section
    $report->addChartSection('sales_by_brand', 'Markaya Göre Satışlar', [
        'query' => 'SELECT 
            b.name as category,
            SUM(soi.line_total) as value
            FROM sales_order_items soi
            JOIN products p ON p.id = soi.product_id
            JOIN brands b ON b.id = p.brand_id
            JOIN sales_orders so ON so.id = soi.order_id
            WHERE so.status != "Cancelled"
            GROUP BY b.id, b.name
            ORDER BY value DESC'
    ], 'pie');
    
    return $report;
}

function generateCustomerAnalysisReport($filters): Report
{
    $report = new Report('Müşteri Analiz Raporu', 'Müşteri segmentasyonu ve davranış analizi');
    
    $report->setParameters($filters);
    
    // Customer overview
    $report->addKpiSection('customer_overview', 'Müşteri Genel Bakış', [
        'query' => 'SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN total_orders > 0 THEN 1 END) as active_customers,
            AVG(total_spent) as avg_customer_value
            FROM customers'
    ]);
    
    // Customer tier distribution
    $report->addTableSection('customer_tiers', 'Müşteri Seviye Dağılımı', [
        'query' => 'SELECT 
            customer_tier,
            COUNT(*) as customer_count,
            SUM(total_spent) as total_revenue,
            AVG(total_spent) as avg_spent
            FROM customers
            GROUP BY customer_tier
            ORDER BY total_revenue DESC'
    ]);
    
    return $report;
}

function generateInventoryStatusReport($filters): Report
{
    $report = new Report('Envanter Durum Raporu', 'Stok seviyeleri ve envanter analizi');
    
    $report->setParameters($filters);
    
    // Inventory overview
    $report->addKpiSection('inventory_overview', 'Envanter Genel Bakış', [
        'query' => 'SELECT 
            COUNT(*) as total_products,
            SUM(quantity_on_hand * cost_per_unit) as total_value,
            COUNT(CASE WHEN quantity_available <= reorder_level THEN 1 END) as low_stock_items
            FROM inventory'
    ]);
    
    // Stock levels by location
    $report->addTableSection('stock_by_location', 'Lokasyona Göre Stok', [
        'query' => 'SELECT 
            il.name as location_name,
            COUNT(*) as product_count,
            SUM(i.quantity_on_hand) as total_quantity,
            SUM(i.quantity_on_hand * i.cost_per_unit) as total_value
            FROM inventory i
            JOIN inventory_locations il ON il.id = i.location_id
            GROUP BY il.id, il.name
            ORDER BY total_value DESC'
    ]);
    
    return $report;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Config::get('APP_NAME') ?> - Raporlar</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .report-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            cursor: pointer;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
        }
        
        .report-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: #2c3e50 !important;
        }
        
        .btn-generate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        
        .btn-generate:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-gem me-2"></i>
                <?= Config::get('APP_NAME') ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>
                    Dashboard'a Dön
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <?php if ($action === 'list'): ?>
        <!-- Report List -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-file-alt me-2"></i>
                    Raporlar
                </h2>
                <p class="text-muted mb-4">İş zekası raporlarınızı oluşturun ve analiz edin</p>
            </div>
        </div>

        <div class="row">
            <!-- Sales Summary Report -->
            <div class="col-md-4 mb-4">
                <div class="card report-card h-100" onclick="generateReport('sales_summary')">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line report-icon text-primary"></i>
                        <h5 class="card-title">Satış Özet Raporu</h5>
                        <p class="card-text">Detaylı satış performans analizi, en çok satan ürünler ve trend analizi</p>
                        <div class="mt-auto">
                            <button class="btn btn-generate">
                                <i class="fas fa-play me-2"></i>
                                Rapor Oluştur
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Analysis Report -->
            <div class="col-md-4 mb-4">
                <div class="card report-card h-100" onclick="generateReport('customer_analysis')">
                    <div class="card-body text-center">
                        <i class="fas fa-users report-icon text-success"></i>
                        <h5 class="card-title">Müşteri Analiz Raporu</h5>
                        <p class="card-text">Müşteri segmentasyonu, yaşam boyu değer analizi ve davranış raporları</p>
                        <div class="mt-auto">
                            <button class="btn btn-generate">
                                <i class="fas fa-play me-2"></i>
                                Rapor Oluştur
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Status Report -->
            <div class="col-md-4 mb-4">
                <div class="card report-card h-100" onclick="generateReport('inventory_status')">
                    <div class="card-body text-center">
                        <i class="fas fa-boxes report-icon text-warning"></i>
                        <h5 class="card-title">Envanter Durum Raporu</h5>
                        <p class="card-text">Stok seviyeleri, yeniden sipariş önerileri ve envanter optimizasyonu</p>
                        <div class="mt-auto">
                            <button class="btn btn-generate">
                                <i class="fas fa-play me-2"></i>
                                Rapor Oluştur
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($action === 'generate' && isset($reportData)): ?>
        <!-- Generated Report -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-file-alt me-2"></i>
                        <?= htmlspecialchars($reportData['title']) ?>
                    </h2>
                    <div>
                        <a href="?action=generate&type=<?= $reportType ?>&export=json" class="btn btn-outline-primary me-2">
                            <i class="fas fa-download me-1"></i>
                            JSON İndir
                        </a>
                        <a href="?action=generate&type=<?= $reportType ?>&export=csv" class="btn btn-outline-success me-2">
                            <i class="fas fa-file-csv me-1"></i>
                            CSV İndir
                        </a>
                        <a href="reports.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Geri Dön
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <p class="text-muted"><?= htmlspecialchars($reportData['description']) ?></p>
                        <small class="text-muted">Oluşturulma: <?= $reportData['generated_at'] ?></small>
                        
                        <hr>

                        <?php foreach ($reportData['sections'] as $sectionId => $section): ?>
                        <div class="mb-5">
                            <h4><?= htmlspecialchars($section['title']) ?></h4>
                            
                            <?php if ($section['type'] === 'kpi'): ?>
                            <div class="row">
                                <?php foreach ($section['data'] as $key => $value): ?>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="text-primary"><?= is_numeric($value) ? formatNumber($value) : $value ?></h3>
                                            <p class="mb-0"><?= ucwords(str_replace('_', ' ', $key)) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php elseif ($section['type'] === 'table'): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <?php foreach ($section['data']['headers'] as $header): ?>
                                            <th><?= ucwords(str_replace('_', ' ', $header)) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($section['data']['rows'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $cell): ?>
                                            <td><?= is_numeric($cell) ? formatNumber($cell) : htmlspecialchars($cell) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif (isset($error)): ?>
        <!-- Error Display -->
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger">
                    <h4>Hata</h4>
                    <p><?= htmlspecialchars($error) ?></p>
                    <a href="reports.php" class="btn btn-secondary">Geri Dön</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function generateReport(type) {
            // Show loading
            const button = event.target.closest('.card').querySelector('.btn-generate');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Oluşturuluyor...';
            button.disabled = true;
            
            // Get date range from URL or use default
            const urlParams = new URLSearchParams(window.location.search);
            const dateRange = urlParams.get('date_range') || '30d';
            
            // Redirect to generate report
            window.location.href = `?action=generate&type=${type}&date_range=${dateRange}`;
        }
    </script>
</body>
</html>
