<?php

namespace LuxuryWatch\BI\Analytics;

use LuxuryWatch\BI\Core\Analytics;
use LuxuryWatch\BI\Database\Connection;
use LuxuryWatch\BI\Database\QueryBuilder;

/**
 * Sales Analytics Module
 * Provides comprehensive sales performance analysis
 */
class SalesAnalytics extends Analytics
{
    /**
     * Get sales overview for dashboard
     */
    public function getSalesOverview(array $filters = []): array
    {
        $cacheKey = 'sales_overview_' . md5(serialize($filters));
        $cached = $this->getCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $dateRange = $this->getDateRange($filters);
        $previousDateRange = $this->getPreviousDateRange($dateRange);
        
        // Current period metrics
        $currentMetrics = $this->getSalesMetrics($dateRange, $filters);
        
        // Previous period metrics for comparison
        $previousMetrics = $this->getSalesMetrics($previousDateRange, $filters);
        
        $overview = [
            'total_revenue' => [
                'value' => $currentMetrics['total_revenue'],
                'previous' => $previousMetrics['total_revenue'],
                'change' => $this->percentageChange($previousMetrics['total_revenue'], $currentMetrics['total_revenue'])
            ],
            'total_orders' => [
                'value' => $currentMetrics['total_orders'],
                'previous' => $previousMetrics['total_orders'],
                'change' => $this->percentageChange($previousMetrics['total_orders'], $currentMetrics['total_orders'])
            ],
            'average_order_value' => [
                'value' => $currentMetrics['avg_order_value'],
                'previous' => $previousMetrics['avg_order_value'],
                'change' => $this->percentageChange($previousMetrics['avg_order_value'], $currentMetrics['avg_order_value'])
            ],
            'conversion_rate' => [
                'value' => $currentMetrics['conversion_rate'],
                'previous' => $previousMetrics['conversion_rate'],
                'change' => $this->percentageChange($previousMetrics['conversion_rate'], $currentMetrics['conversion_rate'])
            ]
        ];
        
        $this->cache($cacheKey, $overview);
        return $overview;
    }
    
    /**
     * Get sales metrics for a specific date range
     */
    protected function getSalesMetrics(array $dateRange, array $filters = []): array
    {
        $query = new QueryBuilder('sales_orders');
        $query->select([
            'COUNT(*) as total_orders',
            'SUM(total_amount) as total_revenue',
            'AVG(total_amount) as avg_order_value'
        ])
        ->whereBetween('order_date', $dateRange['start'], $dateRange['end'])
        ->where('status', '!=', 'Cancelled');
        
        // Apply additional filters
        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        
        if (isset($filters['customer_tier'])) {
            $query->join('customers', 'customers.id = sales_orders.customer_id')
                  ->where('customers.customer_tier', $filters['customer_tier']);
        }
        
        $result = $query->first();
        
        // Calculate conversion rate (simplified - would need visitor data)
        $conversionRate = 2.5; // Placeholder
        
        return [
            'total_orders' => (int) ($result['total_orders'] ?? 0),
            'total_revenue' => (float) ($result['total_revenue'] ?? 0),
            'avg_order_value' => (float) ($result['avg_order_value'] ?? 0),
            'conversion_rate' => $conversionRate
        ];
    }
    
    /**
     * Get sales trend data
     */
    public function getSalesTrend(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        $groupBy = $filters['group_by'] ?? 'day';
        
        $dateFormat = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };
        
        $query = new QueryBuilder('sales_orders');
        $query->select([
            "DATE_FORMAT(order_date, '{$dateFormat}') as period",
            'COUNT(*) as orders',
            'SUM(total_amount) as revenue',
            'AVG(total_amount) as avg_order_value'
        ])
        ->whereBetween('order_date', $dateRange['start'], $dateRange['end'])
        ->where('status', '!=', 'Cancelled')
        ->groupBy(['period'])
        ->orderBy('period', 'ASC');
        
        $results = $query->get();
        
        // Calculate trend
        $revenueValues = array_column($results, 'revenue');
        $trend = $this->calculateTrend($results, 'period', 'revenue');
        
        return [
            'data' => $results,
            'trend' => $trend,
            'summary' => [
                'total_periods' => count($results),
                'avg_revenue_per_period' => array_sum($revenueValues) / max(count($revenueValues), 1),
                'trend_direction' => $trend['slope'] > 0 ? 'up' : ($trend['slope'] < 0 ? 'down' : 'stable')
            ]
        ];
    }
    
    /**
     * Get top performing products
     */
    public function getTopProducts(array $filters = [], int $limit = 10): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $query = new QueryBuilder('sales_order_items');
        $query->select([
            'products.name as product_name',
            'products.sku',
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
        ->groupBy(['products.id', 'products.name', 'products.sku', 'brands.name'])
        ->orderBy('revenue', 'DESC')
        ->limit($limit);
        
        return $query->get();
    }
    
    /**
     * Get sales by brand analysis
     */
    public function getSalesByBrand(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $query = new QueryBuilder('sales_order_items');
        $query->select([
            'brands.name as brand_name',
            'brands.luxury_tier',
            'COUNT(DISTINCT sales_orders.id) as orders',
            'SUM(sales_order_items.quantity) as units_sold',
            'SUM(sales_order_items.line_total) as revenue',
            'AVG(sales_order_items.unit_price) as avg_price'
        ])
        ->join('products', 'products.id = sales_order_items.product_id')
        ->join('brands', 'brands.id = products.brand_id')
        ->join('sales_orders', 'sales_orders.id = sales_order_items.order_id')
        ->whereBetween('sales_orders.order_date', $dateRange['start'], $dateRange['end'])
        ->where('sales_orders.status', '!=', 'Cancelled')
        ->groupBy(['brands.id', 'brands.name', 'brands.luxury_tier'])
        ->orderBy('revenue', 'DESC');
        
        $results = $query->get();
        
        // Calculate market share
        $totalRevenue = array_sum(array_column($results, 'revenue'));
        
        foreach ($results as &$brand) {
            $brand['market_share'] = $totalRevenue > 0 ? ($brand['revenue'] / $totalRevenue) * 100 : 0;
        }
        
        return $results;
    }
    
    /**
     * Get sales by store performance
     */
    public function getSalesByStore(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $query = new QueryBuilder('sales_orders');
        $query->select([
            'stores.name as store_name',
            'stores.type as store_type',
            'stores.city',
            'COUNT(*) as orders',
            'SUM(total_amount) as revenue',
            'AVG(total_amount) as avg_order_value',
            'COUNT(DISTINCT customer_id) as unique_customers'
        ])
        ->join('stores', 'stores.id = sales_orders.store_id')
        ->whereBetween('order_date', $dateRange['start'], $dateRange['end'])
        ->where('status', '!=', 'Cancelled')
        ->groupBy(['stores.id', 'stores.name', 'stores.type', 'stores.city'])
        ->orderBy('revenue', 'DESC');
        
        return $query->get();
    }
    
    /**
     * Get sales funnel analysis
     */
    public function getSalesFunnel(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        // This is a simplified funnel - in reality you'd track more stages
        $stages = [
            'visitors' => $this->getVisitorCount($dateRange), // Would come from web analytics
            'product_views' => $this->getProductViews($dateRange),
            'cart_additions' => $this->getCartAdditions($dateRange),
            'checkouts' => $this->getCheckoutCount($dateRange),
            'completed_orders' => $this->getCompletedOrders($dateRange)
        ];
        
        $funnel = [];
        $previousValue = null;
        
        foreach ($stages as $stage => $value) {
            $conversionRate = $previousValue ? ($value / $previousValue) * 100 : 100;
            
            $funnel[] = [
                'stage' => $stage,
                'value' => $value,
                'conversion_rate' => $conversionRate
            ];
            
            $previousValue = $value;
        }
        
        return $funnel;
    }
    
    /**
     * Get customer acquisition analysis
     */
    public function getCustomerAcquisition(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $query = new QueryBuilder('customers');
        $query->select([
            'DATE(registration_date) as date',
            'COUNT(*) as new_customers',
            'customer_tier',
            'COUNT(CASE WHEN total_orders > 0 THEN 1 END) as customers_with_orders'
        ])
        ->whereBetween('registration_date', $dateRange['start'], $dateRange['end'])
        ->groupBy(['DATE(registration_date)', 'customer_tier'])
        ->orderBy('date', 'ASC');
        
        return $query->get();
    }
    
    /**
     * Get seasonal analysis
     */
    public function getSeasonalAnalysis(array $filters = []): array
    {
        $year = $filters['year'] ?? date('Y');
        
        $query = new QueryBuilder('sales_orders');
        $query->select([
            'MONTH(order_date) as month',
            'MONTHNAME(order_date) as month_name',
            'COUNT(*) as orders',
            'SUM(total_amount) as revenue',
            'AVG(total_amount) as avg_order_value'
        ])
        ->whereRaw('YEAR(order_date) = :year', ['year' => $year])
        ->where('status', '!=', 'Cancelled')
        ->groupBy(['MONTH(order_date)', 'MONTHNAME(order_date)'])
        ->orderBy('month', 'ASC');
        
        $results = $query->get();
        
        // Calculate seasonal index
        $avgRevenue = array_sum(array_column($results, 'revenue')) / 12;
        
        foreach ($results as &$month) {
            $month['seasonal_index'] = $avgRevenue > 0 ? ($month['revenue'] / $avgRevenue) * 100 : 100;
        }
        
        return $results;
    }
    
    /**
     * Helper methods for funnel analysis
     */
    protected function getVisitorCount(array $dateRange): int
    {
        // Placeholder - would integrate with web analytics
        return 10000;
    }
    
    protected function getProductViews(array $dateRange): int
    {
        // Placeholder - would track product page views
        return 5000;
    }
    
    protected function getCartAdditions(array $dateRange): int
    {
        // Placeholder - would track cart events
        return 1500;
    }
    
    protected function getCheckoutCount(array $dateRange): int
    {
        $query = new QueryBuilder('sales_orders');
        return $query->whereBetween('order_date', $dateRange['start'], $dateRange['end'])
                    ->whereIn('status', ['Pending', 'Confirmed', 'Shipped', 'Delivered'])
                    ->count();
    }
    
    protected function getCompletedOrders(array $dateRange): int
    {
        $query = new QueryBuilder('sales_orders');
        return $query->whereBetween('order_date', $dateRange['start'], $dateRange['end'])
                    ->where('status', 'Delivered')
                    ->count();
    }
    
    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        $range = $filters['date_range'] ?? '30d';
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            return [
                'start' => $filters['start_date'],
                'end' => $filters['end_date']
            ];
        }
        
        $end = date('Y-m-d 23:59:59');
        
        $start = match ($range) {
            '1d' => date('Y-m-d 00:00:00'),
            '7d' => date('Y-m-d 00:00:00', strtotime('-7 days')),
            '30d' => date('Y-m-d 00:00:00', strtotime('-30 days')),
            '90d' => date('Y-m-d 00:00:00', strtotime('-90 days')),
            '1y' => date('Y-m-d 00:00:00', strtotime('-1 year')),
            'mtd' => date('Y-m-01 00:00:00'),
            'ytd' => date('Y-01-01 00:00:00'),
            default => date('Y-m-d 00:00:00', strtotime('-30 days'))
        };
        
        return ['start' => $start, 'end' => $end];
    }
    
    /**
     * Get previous period date range for comparison
     */
    protected function getPreviousDateRange(array $currentRange): array
    {
        $start = new \DateTime($currentRange['start']);
        $end = new \DateTime($currentRange['end']);
        $interval = $start->diff($end);
        
        $previousEnd = clone $start;
        $previousEnd->sub(new \DateInterval('P1D'));
        
        $previousStart = clone $previousEnd;
        $previousStart->sub($interval);
        
        return [
            'start' => $previousStart->format('Y-m-d H:i:s'),
            'end' => $previousEnd->format('Y-m-d H:i:s')
        ];
    }
}
