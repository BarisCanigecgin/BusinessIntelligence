<?php

namespace LuxuryWatch\BI\Core;

use LuxuryWatch\BI\Database\Connection;
use LuxuryWatch\BI\Database\QueryBuilder;
use Exception;

/**
 * Core Analytics Engine
 * Provides analytical calculations and data processing capabilities
 */
class Analytics
{
    protected array $cache = [];
    protected int $cacheTtl = 3600; // 1 hour default
    
    /**
     * Calculate percentage change between two values
     */
    public function percentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return (($newValue - $oldValue) / abs($oldValue)) * 100;
    }
    
    /**
     * Calculate growth rate
     */
    public function growthRate(float $startValue, float $endValue, int $periods): float
    {
        if ($startValue <= 0 || $periods <= 0) {
            return 0;
        }
        
        return (pow($endValue / $startValue, 1 / $periods) - 1) * 100;
    }
    
    /**
     * Calculate moving average
     */
    public function movingAverage(array $values, int $window): array
    {
        $result = [];
        $count = count($values);
        
        for ($i = $window - 1; $i < $count; $i++) {
            $sum = 0;
            for ($j = $i - $window + 1; $j <= $i; $j++) {
                $sum += $values[$j];
            }
            $result[] = $sum / $window;
        }
        
        return $result;
    }
    
    /**
     * Calculate standard deviation
     */
    public function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / ($count - 1);
        
        return sqrt($variance);
    }
    
    /**
     * Calculate correlation coefficient between two datasets
     */
    public function correlation(array $x, array $y): float
    {
        $n = min(count($x), count($y));
        if ($n < 2) {
            return 0;
        }
        
        $sumX = array_sum(array_slice($x, 0, $n));
        $sumY = array_sum(array_slice($y, 0, $n));
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }
        
        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        return $denominator != 0 ? $numerator / $denominator : 0;
    }
    
    /**
     * Calculate percentiles
     */
    public function percentile(array $values, float $percentile): float
    {
        sort($values);
        $count = count($values);
        
        if ($count == 0) {
            return 0;
        }
        
        $index = ($percentile / 100) * ($count - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower == $upper) {
            return $values[$lower];
        }
        
        $weight = $index - $lower;
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }
    
    /**
     * Calculate time series trend
     */
    public function calculateTrend(array $data, string $dateField = 'date', string $valueField = 'value'): array
    {
        if (count($data) < 2) {
            return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
        }
        
        $n = count($data);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        foreach ($data as $i => $point) {
            $x = $i; // Use index as x value
            $y = $point[$valueField];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Calculate R-squared
        $meanY = $sumY / $n;
        $ssTotal = 0;
        $ssRes = 0;
        
        foreach ($data as $i => $point) {
            $y = $point[$valueField];
            $predicted = $slope * $i + $intercept;
            
            $ssTotal += pow($y - $meanY, 2);
            $ssRes += pow($y - $predicted, 2);
        }
        
        $rSquared = $ssTotal > 0 ? 1 - ($ssRes / $ssTotal) : 0;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => $rSquared
        ];
    }
    
    /**
     * Segment data into groups based on criteria
     */
    public function segment(array $data, array $criteria): array
    {
        $segments = [];
        
        foreach ($data as $item) {
            $segmentKey = $this->determineSegment($item, $criteria);
            
            if (!isset($segments[$segmentKey])) {
                $segments[$segmentKey] = [];
            }
            
            $segments[$segmentKey][] = $item;
        }
        
        return $segments;
    }
    
    /**
     * Determine which segment an item belongs to
     */
    protected function determineSegment(array $item, array $criteria): string
    {
        foreach ($criteria as $segmentName => $conditions) {
            $matches = true;
            
            foreach ($conditions as $field => $condition) {
                if (!$this->evaluateCondition($item[$field] ?? null, $condition)) {
                    $matches = false;
                    break;
                }
            }
            
            if ($matches) {
                return $segmentName;
            }
        }
        
        return 'Other';
    }
    
    /**
     * Evaluate a condition against a value
     */
    protected function evaluateCondition(mixed $value, array $condition): bool
    {
        $operator = $condition['operator'] ?? '=';
        $expected = $condition['value'];
        
        return match ($operator) {
            '=' => $value == $expected,
            '!=' => $value != $expected,
            '>' => $value > $expected,
            '>=' => $value >= $expected,
            '<' => $value < $expected,
            '<=' => $value <= $expected,
            'in' => in_array($value, (array) $expected),
            'not_in' => !in_array($value, (array) $expected),
            'between' => $value >= $expected[0] && $value <= $expected[1],
            'like' => str_contains(strtolower($value), strtolower($expected)),
            default => false
        };
    }
    
    /**
     * Calculate cohort analysis
     */
    public function cohortAnalysis(array $data, string $cohortField, string $periodField, string $valueField): array
    {
        $cohorts = [];
        
        // Group by cohort
        foreach ($data as $item) {
            $cohort = $item[$cohortField];
            $period = $item[$periodField];
            $value = $item[$valueField];
            
            if (!isset($cohorts[$cohort])) {
                $cohorts[$cohort] = [];
            }
            
            if (!isset($cohorts[$cohort][$period])) {
                $cohorts[$cohort][$period] = 0;
            }
            
            $cohorts[$cohort][$period] += $value;
        }
        
        return $cohorts;
    }
    
    /**
     * Cache analytical results
     */
    protected function cache(string $key, mixed $data): void
    {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get cached analytical results
     */
    protected function getCache(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }
        
        $cached = $this->cache[$key];
        
        if (time() - $cached['timestamp'] > $this->cacheTtl) {
            unset($this->cache[$key]);
            return null;
        }
        
        return $cached['data'];
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
    
    /**
     * Set cache TTL
     */
    public function setCacheTtl(int $seconds): void
    {
        $this->cacheTtl = $seconds;
    }
}
