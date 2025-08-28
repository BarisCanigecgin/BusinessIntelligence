<?php
/**
 * Main Dashboard Interface
 * Interactive BI Dashboard for Luxury Watch Company
 */

require_once 'src/bootstrap.php';

use LuxuryWatch\BI\Analytics\SalesAnalytics;
use LuxuryWatch\BI\Analytics\CustomerSegmentation;
use LuxuryWatch\BI\Analytics\InventoryAnalytics;

// Get dashboard data
$salesAnalytics = new SalesAnalytics();
$customerAnalytics = new CustomerSegmentation();
$inventoryAnalytics = new InventoryAnalytics();

$filters = sanitizeInput($_GET);
$dateRange = $filters['date_range'] ?? '30d';

try {
    $salesOverview = $salesAnalytics->getSalesOverview($filters);
    $inventoryOverview = $inventoryAnalytics->getInventoryOverview($filters);
    $topProducts = $salesAnalytics->getTopProducts($filters, 5);
    $salesTrend = $salesAnalytics->getSalesTrend($filters);
} catch (Exception $e) {
    handleError($e, false);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Config::get('APP_NAME') ?> - Dashboard | KnowHow Pilot</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
        }
        
        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .kpi-change {
            font-size: 0.9rem;
        }
        
        .kpi-change.positive {
            color: #28a745;
        }
        
        .kpi-change.negative {
            color: #dc3545;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: #2c3e50 !important;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-gem me-2"></i>
                <?= Config::get('APP_NAME') ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-calendar me-1"></i>
                        <?= ucfirst(str_replace('_', ' ', $dateRange)) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?date_range=1d">Bugün</a></li>
                        <li><a class="dropdown-item" href="?date_range=7d">Son 7 Gün</a></li>
                        <li><a class="dropdown-item" href="?date_range=30d">Son 30 Gün</a></li>
                        <li><a class="dropdown-item" href="?date_range=90d">Son 90 Gün</a></li>
                        <li><a class="dropdown-item" href="?date_range=1y">Son 1 Yıl</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h6 class="text-light mb-3">MENÜ</h6>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="#overview">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Genel Bakış
                            </a>
                            <a class="nav-link" href="#sales">
                                <i class="fas fa-chart-line me-2"></i>
                                Satış Analizi
                            </a>
                            <a class="nav-link" href="#customers">
                                <i class="fas fa-users me-2"></i>
                                Müşteri Analizi
                            </a>
                            <a class="nav-link" href="#inventory">
                                <i class="fas fa-boxes me-2"></i>
                                Envanter Analizi
                            </a>
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-file-alt me-2"></i>
                                Raporlar
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- KPI Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card kpi-card">
                            <div class="card-body text-center">
                                <i class="fas fa-lira-sign fa-2x mb-2"></i>
                                <div class="kpi-value"><?= formatCurrency($salesOverview['total_revenue']['value']) ?></div>
                                <div class="kpi-label">Toplam Satış</div>
                                <div class="kpi-change <?= $salesOverview['total_revenue']['change'] >= 0 ? 'positive' : 'negative' ?>">
                                    <i class="fas fa-arrow-<?= $salesOverview['total_revenue']['change'] >= 0 ? 'up' : 'down' ?>"></i>
                                    <?= formatPercentage(abs($salesOverview['total_revenue']['change'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card kpi-card">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <div class="kpi-value"><?= formatNumber($salesOverview['total_orders']['value']) ?></div>
                                <div class="kpi-label">Toplam Sipariş</div>
                                <div class="kpi-change <?= $salesOverview['total_orders']['change'] >= 0 ? 'positive' : 'negative' ?>">
                                    <i class="fas fa-arrow-<?= $salesOverview['total_orders']['change'] >= 0 ? 'up' : 'down' ?>"></i>
                                    <?= formatPercentage(abs($salesOverview['total_orders']['change'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card kpi-card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <div class="kpi-value"><?= formatCurrency($salesOverview['average_order_value']['value']) ?></div>
                                <div class="kpi-label">Ortalama Sipariş</div>
                                <div class="kpi-change <?= $salesOverview['average_order_value']['change'] >= 0 ? 'positive' : 'negative' ?>">
                                    <i class="fas fa-arrow-<?= $salesOverview['average_order_value']['change'] >= 0 ? 'up' : 'down' ?>"></i>
                                    <?= formatPercentage(abs($salesOverview['average_order_value']['change'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card kpi-card">
                            <div class="card-body text-center">
                                <i class="fas fa-boxes fa-2x mb-2"></i>
                                <div class="kpi-value"><?= formatNumber($inventoryOverview['total_products']) ?></div>
                                <div class="kpi-label">Toplam Ürün</div>
                                <div class="kpi-change">
                                    <small>Stok Değeri: <?= formatCurrency($inventoryOverview['total_stock_value']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Satış Trendi
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-star me-2"></i>
                                    En Çok Satan Ürünler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Ürün</th>
                                                <th>Satış</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topProducts as $product): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($product['brand_name']) ?></small><br>
                                                    <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?= formatCurrency($product['revenue']) ?></strong><br>
                                                    <small class="text-muted"><?= $product['units_sold'] ?> adet</small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Alerts -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Envanter Uyarıları
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="alert alert-warning">
                                            <strong><?= $inventoryOverview['low_stock_items'] ?></strong> ürün düşük stokta
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-danger">
                                            <strong><?= $inventoryOverview['out_of_stock_items'] ?></strong> ürün stokta yok
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-info">
                                            <strong><?= $inventoryOverview['overstock_items'] ?></strong> ürün fazla stokta
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sales Trend Chart
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        const salesTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($salesTrend['data'], 'period')) ?>,
                datasets: [{
                    label: 'Satış (₺)',
                    data: <?= json_encode(array_column($salesTrend['data'], 'revenue')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Sipariş Sayısı',
                    data: <?= json_encode(array_column($salesTrend['data'], 'orders')) ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });

        // Auto refresh every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
