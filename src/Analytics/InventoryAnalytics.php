<?php

namespace LuxuryWatch\BI\Analytics;

use LuxuryWatch\BI\Core\Analytics;
use LuxuryWatch\BI\Database\Connection;
use LuxuryWatch\BI\Database\QueryBuilder;

/**
 * Inventory Analytics Module
 * Provides comprehensive inventory analysis and optimization insights
 */
class InventoryAnalytics extends Analytics
{
    /**
     * Get inventory overview dashboard
     */
    public function getInventoryOverview(array $filters = []): array
    {
        $cacheKey = 'inventory_overview_' . md5(serialize($filters));
        $cached = $this->getCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $overview = [
            'total_products' => $this->getTotalProducts($filters),
            'total_stock_value' => $this->getTotalStockValue($filters),
            'low_stock_items' => $this->getLowStockCount($filters),
            'out_of_stock_items' => $this->getOutOfStockCount($filters),
            'overstock_items' => $this->getOverstockCount($filters),
            'inventory_turnover' => $this->getAverageInventoryTurnover($filters),
            'stock_accuracy' => $this->getStockAccuracy($filters)
        ];
        
        $this->cache($cacheKey, $overview);
        return $overview;
    }
    
    /**
     * Get current stock levels analysis
     */
    public function getStockLevels(array $filters = []): array
    {
        $query = new QueryBuilder('inventory');
        $query->select([
            'products.sku',
            'products.name as product_name',
            'brands.name as brand_name',
            'inventory_locations.name as location_name',
            'inventory.quantity_on_hand',
            'inventory.quantity_reserved',
            'inventory.quantity_available',
            'inventory.reorder_level',
            'inventory.max_stock_level',
            'inventory.cost_per_unit',
            '(inventory.quantity_on_hand * inventory.cost_per_unit) as stock_value',
            'CASE 
                WHEN inventory.quantity_available <= 0 THEN "Out of Stock"
                WHEN inventory.quantity_available <= inventory.reorder_level THEN "Low Stock"
                WHEN inventory.quantity_available >= inventory.max_stock_level THEN "Overstock"
                ELSE "Normal"
            END as stock_status'
        ])
        ->join('products', 'products.id = inventory.product_id')
        ->join('brands', 'brands.id = products.brand_id')
        ->join('inventory_locations', 'inventory_locations.id = inventory.location_id');
        
        // Apply filters
        if (isset($filters['location_id'])) {
            $query->where('inventory.location_id', $filters['location_id']);
        }
        
        if (isset($filters['brand_id'])) {
            $query->where('products.brand_id', $filters['brand_id']);
        }
        
        if (isset($filters['stock_status'])) {
            $query->whereRaw('CASE 
                WHEN inventory.quantity_available <= 0 THEN "Out of Stock"
                WHEN inventory.quantity_available <= inventory.reorder_level THEN "Low Stock"
                WHEN inventory.quantity_available >= inventory.max_stock_level THEN "Overstock"
                ELSE "Normal"
            END = :stock_status', ['stock_status' => $filters['stock_status']]);
        }
        
        $query->orderBy('stock_value', 'DESC');
        
        return $query->get();
    }
    
    /**
     * Get inventory turnover analysis
     */
    public function getInventoryTurnover(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        // Calculate turnover for each product
        $query = new QueryBuilder('inventory');
        $query->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'brands.name as brand_name',
            'inventory_locations.name as location_name',
            'inventory.quantity_on_hand',
            'inventory.cost_per_unit',
            'AVG(inventory.quantity_on_hand) as avg_inventory'
        ])
        ->join('products', 'products.id = inventory.product_id')
        ->join('brands', 'brands.id = products.brand_id')
        ->join('inventory_locations', 'inventory_locations.id = inventory.location_id')
        ->groupBy(['products.id', 'products.sku', 'products.name', 'brands.name', 'inventory_locations.name', 'inventory.quantity_on_hand', 'inventory.cost_per_unit']);
        
        $inventoryData = $query->get();
        
        // Get sales data for the same period
        $salesQuery = new QueryBuilder('sales_order_items');
        $salesQuery->select([
            'product_id',
            'SUM(quantity) as units_sold',
            'SUM(line_total) as revenue'
        ])
        ->join('sales_orders', 'sales_orders.id = sales_order_items.order_id')
        ->whereBetween('sales_orders.order_date', $dateRange['start'], $dateRange['end'])
        ->where('sales_orders.status', '!=', 'Cancelled')
        ->groupBy(['product_id']);
        
        $salesData = $salesQuery->get();
        
        // Create lookup array for sales data
        $salesLookup = [];
        foreach ($salesData as $sale) {
            $salesLookup[$sale['product_id']] = $sale;
        }
        
        // Calculate turnover metrics
        $turnoverData = [];
        foreach ($inventoryData as $item) {
            $productId = $item['product_id'];
            $sales = $salesLookup[$productId] ?? ['units_sold' => 0, 'revenue' => 0];
            
            $avgInventory = $item['avg_inventory'];
            $unitsSold = $sales['units_sold'];
            
            $turnoverRate = $avgInventory > 0 ? $unitsSold / $avgInventory : 0;
            $daysOfSupply = $turnoverRate > 0 ? 365 / $turnoverRate : 365;
            
            $turnoverData[] = array_merge($item, [
                'units_sold' => $unitsSold,
                'revenue' => $sales['revenue'],
                'turnover_rate' => $turnoverRate,
                'days_of_supply' => $daysOfSupply,
                'turnover_category' => $this->getTurnoverCategory($turnoverRate)
            ]);
        }
        
        // Sort by turnover rate
        usort($turnoverData, fn($a, $b) => $b['turnover_rate'] <=> $a['turnover_rate']);
        
        return $turnoverData;
    }
    
    /**
     * Get ABC analysis (Pareto analysis)
     */
    public function getAbcAnalysis(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        // Get product sales data
        $query = new QueryBuilder('sales_order_items');
        $query->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'brands.name as brand_name',
            'SUM(sales_order_items.quantity) as units_sold',
            'SUM(sales_order_items.line_total) as revenue',
            'AVG(sales_order_items.unit_price) as avg_price'
        ])
        ->join('products', 'products.id = sales_order_items.product_id')
        ->join('brands', 'brands.id = products.brand_id')
        ->join('sales_orders', 'sales_orders.id = sales_order_items.order_id')
        ->whereBetween('sales_orders.order_date', $dateRange['start'], $dateRange['end'])
        ->where('sales_orders.status', '!=', 'Cancelled')
        ->groupBy(['products.id', 'products.sku', 'products.name', 'brands.name'])
        ->orderBy('revenue', 'DESC');
        
        $products = $query->get();
        
        if (empty($products)) {
            return ['products' => [], 'summary' => []];
        }
        
        // Calculate cumulative percentages
        $totalRevenue = array_sum(array_column($products, 'revenue'));
        $cumulativeRevenue = 0;
        
        foreach ($products as &$product) {
            $cumulativeRevenue += $product['revenue'];
            $product['revenue_percentage'] = ($product['revenue'] / $totalRevenue) * 100;
            $product['cumulative_percentage'] = ($cumulativeRevenue / $totalRevenue) * 100;
            
            // Assign ABC category
            if ($product['cumulative_percentage'] <= 80) {
                $product['abc_category'] = 'A';
            } elseif ($product['cumulative_percentage'] <= 95) {
                $product['abc_category'] = 'B';
            } else {
                $product['abc_category'] = 'C';
            }
        }
        
        // Calculate summary
        $summary = $this->getAbcSummary($products);
        
        return [
            'products' => $products,
            'summary' => $summary,
            'analysis_period' => $dateRange
        ];
    }
    
    /**
     * Get slow-moving and dead stock analysis
     */
    public function getSlowMovingStock(array $filters = []): array
    {
        $analysisDate = $filters['analysis_date'] ?? date('Y-m-d');
        $slowMovingThreshold = $filters['slow_moving_days'] ?? 90;
        $deadStockThreshold = $filters['dead_stock_days'] ?? 180;
        
        $query = new QueryBuilder('inventory');
        $query->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'brands.name as brand_name',
            'inventory_locations.name as location_name',
            'inventory.quantity_on_hand',
            'inventory.cost_per_unit',
            '(inventory.quantity_on_hand * inventory.cost_per_unit) as stock_value',
            'inventory.last_count_date'
        ])
        ->join('products', 'products.id = inventory.product_id')
        ->join('brands', 'brands.id = products.brand_id')
        ->join('inventory_locations', 'inventory_locations.id = inventory.location_id')
        ->where('inventory.quantity_on_hand', '>', 0);
        
        $inventoryItems = $query->get();
        
        // Get recent sales for each product
        $productIds = array_column($inventoryItems, 'product_id');
        
        if (!empty($productIds)) {
            $salesQuery = new QueryBuilder('sales_order_items');
            $salesQuery->select([
                'product_id',
                'MAX(sales_orders.order_date) as last_sale_date',
                'SUM(quantity) as total_sold_90d'
            ])
            ->join('sales_orders', 'sales_orders.id = sales_order_items.order_id')
            ->whereIn('product_id', $productIds)
            ->whereRaw('sales_orders.order_date >= DATE_SUB(:analysis_date, INTERVAL 90 DAY)', ['analysis_date' => $analysisDate])
            ->where('sales_orders.status', '!=', 'Cancelled')
            ->groupBy(['product_id']);
            
            $salesData = $salesQuery->get();
            
            // Create lookup for sales data
            $salesLookup = [];
            foreach ($salesData as $sale) {
                $salesLookup[$sale['product_id']] = $sale;
            }
        } else {
            $salesLookup = [];
        }
        
        // Analyze each inventory item
        $slowMovingItems = [];
        $deadStockItems = [];
        
        foreach ($inventoryItems as $item) {
            $productId = $item['product_id'];
            $sales = $salesLookup[$productId] ?? null;
            
            $daysSinceLastSale = null;
            if ($sales && $sales['last_sale_date']) {
                $lastSaleDate = new \DateTime($sales['last_sale_date']);
                $analysisDateTime = new \DateTime($analysisDate);
                $daysSinceLastSale = $analysisDateTime->diff($lastSaleDate)->days;
            }
            
            $item['last_sale_date'] = $sales['last_sale_date'] ?? null;
            $item['days_since_last_sale'] = $daysSinceLastSale;
            $item['total_sold_90d'] = $sales['total_sold_90d'] ?? 0;
            
            if ($daysSinceLastSale === null || $daysSinceLastSale >= $deadStockThreshold) {
                $deadStockItems[] = $item;
            } elseif ($daysSinceLastSale >= $slowMovingThreshold) {
                $slowMovingItems[] = $item;
            }
        }
        
        return [
            'slow_moving' => $slowMovingItems,
            'dead_stock' => $deadStockItems,
            'summary' => [
                'slow_moving_count' => count($slowMovingItems),
                'slow_moving_value' => array_sum(array_column($slowMovingItems, 'stock_value')),
                'dead_stock_count' => count($deadStockItems),
                'dead_stock_value' => array_sum(array_column($deadStockItems, 'stock_value'))
            ],
            'thresholds' => [
                'slow_moving_days' => $slowMovingThreshold,
                'dead_stock_days' => $deadStockThreshold
            ]
        ];
    }
    
    /**
     * Get reorder recommendations
     */
    public function getReorderRecommendations(array $filters = []): array
    {
        $leadTimeDays = $filters['lead_time_days'] ?? 30;
        $safetyStockDays = $filters['safety_stock_days'] ?? 7;
        
        // Get products that need reordering
        $query = new QueryBuilder('inventory');
        $query->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'brands.name as brand_name',
            'inventory_locations.name as location_name',
            'inventory.quantity_on_hand',
            'inventory.quantity_reserved',
            'inventory.quantity_available',
            'inventory.reorder_level',
            'inventory.max_stock_level',
            'inventory.cost_per_unit'
        ])
        ->join('products', 'products.id = inventory.product_id')
        ->join('brands', 'brands.id = products.brand_id')
        ->join('inventory_locations', 'inventory_locations.id = inventory.location_id')
        ->whereRaw('inventory.quantity_available <= inventory.reorder_level');
        
        $lowStockItems = $query->get();
        
        // Calculate reorder recommendations
        $recommendations = [];
        foreach ($lowStockItems as $item) {
            $productId = $item['product_id'];
            
            // Get average daily sales
            $avgDailySales = $this->getAverageDailySales($productId, 90);
            
            // Calculate recommended order quantity
            $demandDuringLeadTime = $avgDailySales * $leadTimeDays;
            $safetyStock = $avgDailySales * $safetyStockDays;
            $reorderPoint = $demandDuringLeadTime + $safetyStock;
            
            $recommendedOrderQty = max(
                $item['max_stock_level'] - $item['quantity_on_hand'],
                $reorderPoint - $item['quantity_available']
            );
            
            $recommendations[] = array_merge($item, [
                'avg_daily_sales' => $avgDailySales,
                'demand_during_lead_time' => $demandDuringLeadTime,
                'safety_stock' => $safetyStock,
                'recommended_reorder_point' => $reorderPoint,
                'recommended_order_qty' => max(0, $recommendedOrderQty),
                'estimated_cost' => $recommendedOrderQty * $item['cost_per_unit'],
                'priority' => $this->getReorderPriority($item['quantity_available'], $item['reorder_level'], $avgDailySales)
            ]);
        }
        
        // Sort by priority
        usort($recommendations, fn($a, $b) => $this->comparePriority($a['priority'], $b['priority']));
        
        return $recommendations;
    }
    
    /**
     * Helper methods
     */
    protected function getTotalProducts(array $filters): int
    {
        $query = new QueryBuilder('products');
        return $query->where('status', 'Active')->count();
    }
    
    protected function getTotalStockValue(array $filters): float
    {
        $query = new QueryBuilder('inventory');
        $result = $query->select(['SUM(quantity_on_hand * cost_per_unit) as total_value'])->first();
        return (float) ($result['total_value'] ?? 0);
    }
    
    protected function getLowStockCount(array $filters): int
    {
        $query = new QueryBuilder('inventory');
        return $query->whereRaw('quantity_available <= reorder_level')->count();
    }
    
    protected function getOutOfStockCount(array $filters): int
    {
        $query = new QueryBuilder('inventory');
        return $query->where('quantity_available', '<=', 0)->count();
    }
    
    protected function getOverstockCount(array $filters): int
    {
        $query = new QueryBuilder('inventory');
        return $query->whereRaw('quantity_available >= max_stock_level')->count();
    }
    
    protected function getAverageInventoryTurnover(array $filters): float
    {
        // Simplified calculation - would need more complex logic for accurate turnover
        return 4.2; // Placeholder
    }
    
    protected function getStockAccuracy(array $filters): float
    {
        // Would require cycle count data
        return 95.5; // Placeholder
    }
    
    protected function getTurnoverCategory(float $turnoverRate): string
    {
        if ($turnoverRate >= 6) return 'Fast Moving';
        if ($turnoverRate >= 2) return 'Medium Moving';
        if ($turnoverRate >= 0.5) return 'Slow Moving';
        return 'Dead Stock';
    }
    
    protected function getAbcSummary(array $products): array
    {
        $summary = ['A' => 0, 'B' => 0, 'C' => 0];
        
        foreach ($products as $product) {
            $summary[$product['abc_category']]++;
        }
        
        $total = count($products);
        
        return [
            'category_A' => ['count' => $summary['A'], 'percentage' => ($summary['A'] / $total) * 100],
            'category_B' => ['count' => $summary['B'], 'percentage' => ($summary['B'] / $total) * 100],
            'category_C' => ['count' => $summary['C'], 'percentage' => ($summary['C'] / $total) * 100]
        ];
    }
    
    protected function getAverageDailySales(int $productId, int $days = 90): float
    {
        $query = new QueryBuilder('sales_order_items');
        $result = $query->select(['SUM(quantity) as total_sold'])
            ->join('sales_orders', 'sales_orders.id = sales_order_items.order_id')
            ->where('product_id', $productId)
            ->whereRaw('sales_orders.order_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)', ['days' => $days])
            ->where('sales_orders.status', '!=', 'Cancelled')
            ->first();
        
        $totalSold = (float) ($result['total_sold'] ?? 0);
        return $totalSold / $days;
    }
    
    protected function getReorderPriority(int $available, int $reorderLevel, float $avgDailySales): string
    {
        if ($available <= 0) return 'Critical';
        if ($available <= $reorderLevel * 0.5) return 'High';
        if ($available <= $reorderLevel) return 'Medium';
        return 'Low';
    }
    
    protected function comparePriority(string $a, string $b): int
    {
        $priorities = ['Critical' => 4, 'High' => 3, 'Medium' => 2, 'Low' => 1];
        return ($priorities[$b] ?? 0) - ($priorities[$a] ?? 0);
    }
    
    protected function getDateRange(array $filters): array
    {
        return [
            'start' => $filters['start_date'] ?? date('Y-m-d', strtotime('-90 days')),
            'end' => $filters['end_date'] ?? date('Y-m-d')
        ];
    }
}
