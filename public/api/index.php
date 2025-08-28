<?php

/**
 * API Entry Point
 * Main API router and endpoint definitions
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use LuxuryWatch\BI\API\Router;
use LuxuryWatch\BI\Analytics\SalesAnalytics;
use LuxuryWatch\BI\Analytics\CustomerSegmentation;
use LuxuryWatch\BI\Analytics\InventoryAnalytics;
use LuxuryWatch\BI\Core\Dashboard;

$router = new Router();

// Add authentication middleware (simplified)
$router->middleware(function() {
    // In a real application, you would validate API keys or JWT tokens here
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    
    if (!$apiKey && !Config::isDebug()) {
        jsonResponse(['error' => 'API key required'], 401);
        return false;
    }
    
    return true;
});

// =============================================
// SALES ANALYTICS ENDPOINTS
// =============================================

$router->get('/api/v1/sales/overview', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getSalesOverview($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/sales/trend', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getSalesTrend($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/sales/top-products', function($params) {
    $filters = sanitizeInput($_GET);
    $limit = (int) ($filters['limit'] ?? 10);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getTopProducts($filters, $limit),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/sales/by-brand', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getSalesByBrand($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/sales/by-store', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getSalesByStore($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/sales/funnel', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getSalesFunnel($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/sales/seasonal', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getSeasonalAnalysis($filters),
        'timestamp' => date('c')
    ];
});

// =============================================
// CUSTOMER ANALYTICS ENDPOINTS
// =============================================

$router->get('/api/v1/customers/rfm-analysis', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new CustomerSegmentation();
    
    return [
        'success' => true,
        'data' => $analytics->getRfmAnalysis($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/customers/lifetime-value', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new CustomerSegmentation();
    
    return [
        'success' => true,
        'data' => $analytics->getCustomerLifetimeValue($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/customers/churn-analysis', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new CustomerSegmentation();
    
    return [
        'success' => true,
        'data' => $analytics->getChurnAnalysis($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/customers/cohort-analysis', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new CustomerSegmentation();
    
    return [
        'success' => true,
        'data' => $analytics->getCohortAnalysis($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/customers/acquisition', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new SalesAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getCustomerAcquisition($filters),
        'timestamp' => date('c')
    ];
});

// =============================================
// INVENTORY ANALYTICS ENDPOINTS
// =============================================

$router->get('/api/v1/inventory/overview', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new InventoryAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getInventoryOverview($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/inventory/stock-levels', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new InventoryAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getStockLevels($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/inventory/turnover', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new InventoryAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getInventoryTurnover($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/inventory/abc-analysis', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new InventoryAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getAbcAnalysis($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/inventory/slow-moving', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new InventoryAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getSlowMovingStock($filters),
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/inventory/reorder-recommendations', function($params) {
    $filters = sanitizeInput($_GET);
    $analytics = new InventoryAnalytics();
    
    return [
        'success' => true,
        'data' => $analytics->getReorderRecommendations($filters),
        'timestamp' => date('c')
    ];
});

// =============================================
// DASHBOARD ENDPOINTS
// =============================================

$router->get('/api/v1/dashboard/kpis', function($params) {
    $filters = sanitizeInput($_GET);
    
    $salesAnalytics = new SalesAnalytics();
    $inventoryAnalytics = new InventoryAnalytics();
    
    $salesOverview = $salesAnalytics->getSalesOverview($filters);
    $inventoryOverview = $inventoryAnalytics->getInventoryOverview($filters);
    
    return [
        'success' => true,
        'data' => [
            'sales' => [
                'total_revenue' => $salesOverview['total_revenue'],
                'total_orders' => $salesOverview['total_orders'],
                'average_order_value' => $salesOverview['average_order_value']
            ],
            'inventory' => [
                'total_products' => $inventoryOverview['total_products'],
                'total_stock_value' => $inventoryOverview['total_stock_value'],
                'low_stock_items' => $inventoryOverview['low_stock_items'],
                'out_of_stock_items' => $inventoryOverview['out_of_stock_items']
            ]
        ],
        'timestamp' => date('c')
    ];
});

$router->get('/api/v1/dashboard/widgets/{widgetId}', function($params) {
    $widgetId = $params['widgetId'];
    $filters = sanitizeInput($_GET);
    
    // This would typically load widget configuration from database
    $dashboard = new Dashboard();
    
    // Example widget configurations
    $widgetConfigs = [
        'sales-trend' => [
            'type' => 'chart',
            'data_source' => ['query' => 'SELECT * FROM sales_trend'],
            'options' => ['chart_type' => 'line']
        ],
        'top-products' => [
            'type' => 'table',
            'data_source' => ['query' => 'SELECT * FROM top_products'],
            'options' => ['limit' => 10]
        ]
    ];
    
    if (!isset($widgetConfigs[$widgetId])) {
        throw new Exception('Widget not found', 404);
    }
    
    $dashboard->addWidget($widgetId, $widgetConfigs[$widgetId]);
    
    return [
        'success' => true,
        'data' => $dashboard->getWidgetData($widgetId),
        'timestamp' => date('c')
    ];
});

// =============================================
// UTILITY ENDPOINTS
// =============================================

$router->get('/api/v1/health', function($params) {
    return [
        'success' => true,
        'status' => 'healthy',
        'version' => '1.0.0',
        'timestamp' => date('c'),
        'database' => Connection::isConnected() ? 'connected' : 'disconnected'
    ];
});

$router->get('/api/v1/config', function($params) {
    return [
        'success' => true,
        'data' => [
            'app_name' => Config::get('APP_NAME'),
            'version' => '1.0.0',
            'timezone' => Config::get('APP_TIMEZONE'),
            'debug' => Config::isDebug()
        ],
        'timestamp' => date('c')
    ];
});

// Handle the request
$router->handle();
