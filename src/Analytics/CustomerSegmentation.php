<?php

namespace LuxuryWatch\BI\Analytics;

use LuxuryWatch\BI\Core\Analytics;
use LuxuryWatch\BI\Database\Connection;
use LuxuryWatch\BI\Database\QueryBuilder;

/**
 * Customer Segmentation Analytics
 * Provides advanced customer analysis and segmentation capabilities
 */
class CustomerSegmentation extends Analytics
{
    /**
     * Perform RFM (Recency, Frequency, Monetary) analysis
     */
    public function getRfmAnalysis(array $filters = []): array
    {
        $cacheKey = 'rfm_analysis_' . md5(serialize($filters));
        $cached = $this->getCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $analysisDate = $filters['analysis_date'] ?? date('Y-m-d');
        
        // Get customer RFM data
        $query = new QueryBuilder('customers');
        $query->select([
            'customers.id',
            'customers.customer_code',
            'customers.first_name',
            'customers.last_name',
            'customers.customer_tier',
            'customers.total_spent as monetary',
            'customers.total_orders as frequency',
            'DATEDIFF(:analysis_date, COALESCE(customers.last_purchase_date, customers.registration_date)) as recency_days'
        ])
        ->whereRaw('customers.total_orders > 0');
        
        $customers = Connection::fetchAll($query->toSql(), array_merge($query->getParams(), ['analysis_date' => $analysisDate]));
        
        if (empty($customers)) {
            return ['segments' => [], 'summary' => []];
        }
        
        // Calculate RFM scores
        $rfmData = $this->calculateRfmScores($customers);
        
        // Segment customers based on RFM scores
        $segments = $this->segmentCustomersByRfm($rfmData);
        
        $result = [
            'segments' => $segments,
            'summary' => $this->getRfmSummary($segments),
            'analysis_date' => $analysisDate
        ];
        
        $this->cache($cacheKey, $result);
        return $result;
    }
    
    /**
     * Calculate RFM scores for customers
     */
    protected function calculateRfmScores(array $customers): array
    {
        // Extract values for percentile calculations
        $recencyValues = array_column($customers, 'recency_days');
        $frequencyValues = array_column($customers, 'frequency');
        $monetaryValues = array_column($customers, 'monetary');
        
        // Calculate quintiles (20%, 40%, 60%, 80%)
        $recencyQuintiles = [
            $this->percentile($recencyValues, 20),
            $this->percentile($recencyValues, 40),
            $this->percentile($recencyValues, 60),
            $this->percentile($recencyValues, 80)
        ];
        
        $frequencyQuintiles = [
            $this->percentile($frequencyValues, 20),
            $this->percentile($frequencyValues, 40),
            $this->percentile($frequencyValues, 60),
            $this->percentile($frequencyValues, 80)
        ];
        
        $monetaryQuintiles = [
            $this->percentile($monetaryValues, 20),
            $this->percentile($monetaryValues, 40),
            $this->percentile($monetaryValues, 60),
            $this->percentile($monetaryValues, 80)
        ];
        
        // Assign scores to each customer
        foreach ($customers as &$customer) {
            // Recency score (lower days = higher score)
            $customer['recency_score'] = $this->getQuintileScore($customer['recency_days'], $recencyQuintiles, true);
            
            // Frequency score (higher frequency = higher score)
            $customer['frequency_score'] = $this->getQuintileScore($customer['frequency'], $frequencyQuintiles, false);
            
            // Monetary score (higher monetary = higher score)
            $customer['monetary_score'] = $this->getQuintileScore($customer['monetary'], $monetaryQuintiles, false);
            
            // Combined RFM score
            $customer['rfm_score'] = $customer['recency_score'] . $customer['frequency_score'] . $customer['monetary_score'];
        }
        
        return $customers;
    }
    
    /**
     * Get quintile score for a value
     */
    protected function getQuintileScore(float $value, array $quintiles, bool $reverse = false): int
    {
        $score = 1;
        
        foreach ($quintiles as $threshold) {
            if ($value > $threshold) {
                $score++;
            } else {
                break;
            }
        }
        
        return $reverse ? (6 - $score) : $score;
    }
    
    /**
     * Segment customers based on RFM scores
     */
    protected function segmentCustomersByRfm(array $customers): array
    {
        $segments = [
            'Champions' => [],
            'Loyal Customers' => [],
            'Potential Loyalists' => [],
            'New Customers' => [],
            'Promising' => [],
            'Need Attention' => [],
            'About to Sleep' => [],
            'At Risk' => [],
            'Cannot Lose Them' => [],
            'Hibernating' => [],
            'Lost' => []
        ];
        
        foreach ($customers as $customer) {
            $r = $customer['recency_score'];
            $f = $customer['frequency_score'];
            $m = $customer['monetary_score'];
            
            $segment = $this->determineRfmSegment($r, $f, $m);
            $segments[$segment][] = $customer;
        }
        
        return $segments;
    }
    
    /**
     * Determine RFM segment based on scores
     */
    protected function determineRfmSegment(int $r, int $f, int $m): string
    {
        if ($r >= 5 && $f >= 5 && $m >= 5) return 'Champions';
        if ($r >= 4 && $f >= 4 && $m >= 4) return 'Loyal Customers';
        if ($r >= 4 && $f >= 3 && $m >= 3) return 'Potential Loyalists';
        if ($r >= 4 && $f <= 2 && $m <= 2) return 'New Customers';
        if ($r >= 3 && $f <= 2 && $m <= 2) return 'Promising';
        if ($r >= 3 && $f >= 3 && $m >= 3) return 'Need Attention';
        if ($r >= 2 && $f >= 2 && $m >= 2) return 'About to Sleep';
        if ($r <= 2 && $f >= 3 && $m >= 3) return 'At Risk';
        if ($r <= 2 && $f >= 4 && $m >= 4) return 'Cannot Lose Them';
        if ($r <= 2 && $f <= 2 && $m >= 2) return 'Hibernating';
        
        return 'Lost';
    }
    
    /**
     * Get RFM analysis summary
     */
    protected function getRfmSummary(array $segments): array
    {
        $summary = [];
        $totalCustomers = array_sum(array_map('count', $segments));
        
        foreach ($segments as $segmentName => $customers) {
            $count = count($customers);
            $totalRevenue = array_sum(array_column($customers, 'monetary'));
            $avgRevenue = $count > 0 ? $totalRevenue / $count : 0;
            
            $summary[$segmentName] = [
                'count' => $count,
                'percentage' => $totalCustomers > 0 ? ($count / $totalCustomers) * 100 : 0,
                'total_revenue' => $totalRevenue,
                'avg_revenue' => $avgRevenue
            ];
        }
        
        return $summary;
    }
    
    /**
     * Get customer lifetime value analysis
     */
    public function getCustomerLifetimeValue(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $query = new QueryBuilder('customers');
        $query->select([
            'customers.id',
            'customers.customer_code',
            'customers.first_name',
            'customers.last_name',
            'customers.customer_tier',
            'customers.registration_date',
            'customers.total_spent',
            'customers.total_orders',
            'DATEDIFF(:end_date, customers.registration_date) as customer_age_days',
            'customers.total_spent / GREATEST(customers.total_orders, 1) as avg_order_value',
            'customers.total_orders / GREATEST(DATEDIFF(:end_date, customers.registration_date) / 365, 0.1) as purchase_frequency'
        ]);
        
        $customers = Connection::fetchAll(
            $query->toSql(), 
            array_merge($query->getParams(), ['end_date' => $dateRange['end']])
        );
        
        foreach ($customers as &$customer) {
            // Calculate predicted CLV using simplified formula
            $avgOrderValue = $customer['avg_order_value'];
            $purchaseFrequency = $customer['purchase_frequency'];
            $customerLifespan = max($customer['customer_age_days'] / 365, 0.5); // At least 6 months
            
            $customer['predicted_clv'] = $avgOrderValue * $purchaseFrequency * $customerLifespan;
            $customer['clv_segment'] = $this->getClvSegment($customer['predicted_clv']);
        }
        
        return $customers;
    }
    
    /**
     * Determine CLV segment
     */
    protected function getClvSegment(float $clv): string
    {
        if ($clv >= 500000) return 'High Value';
        if ($clv >= 200000) return 'Medium-High Value';
        if ($clv >= 100000) return 'Medium Value';
        if ($clv >= 50000) return 'Low-Medium Value';
        
        return 'Low Value';
    }
    
    /**
     * Get customer churn analysis
     */
    public function getChurnAnalysis(array $filters = []): array
    {
        $analysisDate = $filters['analysis_date'] ?? date('Y-m-d');
        $churnThresholdDays = $filters['churn_threshold_days'] ?? 180; // 6 months
        
        $query = new QueryBuilder('customers');
        $query->select([
            'customers.id',
            'customers.customer_code',
            'customers.first_name',
            'customers.last_name',
            'customers.customer_tier',
            'customers.total_spent',
            'customers.total_orders',
            'customers.last_purchase_date',
            'DATEDIFF(:analysis_date, COALESCE(customers.last_purchase_date, customers.registration_date)) as days_since_last_purchase'
        ])
        ->whereRaw('customers.total_orders > 0');
        
        $customers = Connection::fetchAll(
            $query->toSql(),
            array_merge($query->getParams(), ['analysis_date' => $analysisDate])
        );
        
        $churnAnalysis = [
            'active' => [],
            'at_risk' => [],
            'churned' => []
        ];
        
        foreach ($customers as $customer) {
            $daysSinceLastPurchase = $customer['days_since_last_purchase'];
            
            if ($daysSinceLastPurchase <= $churnThresholdDays / 3) {
                $churnAnalysis['active'][] = $customer;
            } elseif ($daysSinceLastPurchase <= $churnThresholdDays) {
                $churnAnalysis['at_risk'][] = $customer;
            } else {
                $churnAnalysis['churned'][] = $customer;
            }
        }
        
        return [
            'segments' => $churnAnalysis,
            'summary' => [
                'active_count' => count($churnAnalysis['active']),
                'at_risk_count' => count($churnAnalysis['at_risk']),
                'churned_count' => count($churnAnalysis['churned']),
                'churn_rate' => count($customers) > 0 ? (count($churnAnalysis['churned']) / count($customers)) * 100 : 0
            ],
            'threshold_days' => $churnThresholdDays
        ];
    }
    
    /**
     * Get customer cohort analysis
     */
    public function getCohortAnalysis(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        
        // Get customer registration cohorts
        $query = new QueryBuilder('customers');
        $query->select([
            'DATE_FORMAT(registration_date, "%Y-%m") as cohort_month',
            'customers.id as customer_id',
            'registration_date'
        ])
        ->whereBetween('registration_date', $startDate, $endDate)
        ->orderBy('registration_date', 'ASC');
        
        $cohortCustomers = $query->get();
        
        // Get purchase data for cohort analysis
        $customerIds = array_column($cohortCustomers, 'customer_id');
        
        if (empty($customerIds)) {
            return ['cohorts' => [], 'summary' => []];
        }
        
        $purchaseQuery = new QueryBuilder('sales_orders');
        $purchaseQuery->select([
            'customer_id',
            'DATE_FORMAT(order_date, "%Y-%m") as purchase_month',
            'COUNT(*) as orders',
            'SUM(total_amount) as revenue'
        ])
        ->whereIn('customer_id', $customerIds)
        ->where('status', '!=', 'Cancelled')
        ->groupBy(['customer_id', 'purchase_month']);
        
        $purchases = $purchaseQuery->get();
        
        // Build cohort table
        $cohorts = $this->buildCohortTable($cohortCustomers, $purchases);
        
        return [
            'cohorts' => $cohorts,
            'summary' => $this->getCohortSummary($cohorts)
        ];
    }
    
    /**
     * Build cohort retention table
     */
    protected function buildCohortTable(array $cohortCustomers, array $purchases): array
    {
        $cohorts = [];
        
        // Group customers by cohort month
        $customerCohorts = [];
        foreach ($cohortCustomers as $customer) {
            $cohortMonth = $customer['cohort_month'];
            $customerCohorts[$cohortMonth][] = $customer['customer_id'];
        }
        
        // Group purchases by customer and month
        $customerPurchases = [];
        foreach ($purchases as $purchase) {
            $customerPurchases[$purchase['customer_id']][$purchase['purchase_month']] = $purchase;
        }
        
        // Calculate retention rates
        foreach ($customerCohorts as $cohortMonth => $customerIds) {
            $cohortSize = count($customerIds);
            $cohorts[$cohortMonth] = [
                'cohort_month' => $cohortMonth,
                'cohort_size' => $cohortSize,
                'periods' => []
            ];
            
            // Calculate retention for each subsequent month
            $cohortDate = new \DateTime($cohortMonth . '-01');
            for ($period = 0; $period <= 12; $period++) {
                $periodDate = clone $cohortDate;
                $periodDate->add(new \DateInterval("P{$period}M"));
                $periodMonth = $periodDate->format('Y-m');
                
                $activeCustomers = 0;
                foreach ($customerIds as $customerId) {
                    if (isset($customerPurchases[$customerId][$periodMonth])) {
                        $activeCustomers++;
                    }
                }
                
                $retentionRate = $cohortSize > 0 ? ($activeCustomers / $cohortSize) * 100 : 0;
                
                $cohorts[$cohortMonth]['periods'][$period] = [
                    'period' => $period,
                    'month' => $periodMonth,
                    'active_customers' => $activeCustomers,
                    'retention_rate' => $retentionRate
                ];
            }
        }
        
        return $cohorts;
    }
    
    /**
     * Get cohort analysis summary
     */
    protected function getCohortSummary(array $cohorts): array
    {
        if (empty($cohorts)) {
            return [];
        }
        
        $totalCustomers = array_sum(array_column($cohorts, 'cohort_size'));
        $avgRetentionRates = [];
        
        // Calculate average retention rates across all cohorts
        for ($period = 0; $period <= 12; $period++) {
            $retentionRates = [];
            foreach ($cohorts as $cohort) {
                if (isset($cohort['periods'][$period])) {
                    $retentionRates[] = $cohort['periods'][$period]['retention_rate'];
                }
            }
            
            $avgRetentionRates[$period] = count($retentionRates) > 0 ? array_sum($retentionRates) / count($retentionRates) : 0;
        }
        
        return [
            'total_customers' => $totalCustomers,
            'total_cohorts' => count($cohorts),
            'avg_retention_rates' => $avgRetentionRates,
            'month_1_retention' => $avgRetentionRates[1] ?? 0,
            'month_6_retention' => $avgRetentionRates[6] ?? 0,
            'month_12_retention' => $avgRetentionRates[12] ?? 0
        ];
    }
    
    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        return [
            'start' => $filters['start_date'] ?? date('Y-m-d', strtotime('-1 year')),
            'end' => $filters['end_date'] ?? date('Y-m-d')
        ];
    }
}
