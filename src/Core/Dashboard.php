<?php

namespace LuxuryWatch\BI\Core;

use LuxuryWatch\BI\Database\Connection;
use Exception;

/**
 * Dashboard Management System
 * Handles dashboard creation, widgets, and real-time data
 */
class Dashboard
{
    protected array $widgets = [];
    protected array $filters = [];
    protected string $timeRange = '30d';
    protected array $permissions = [];
    
    /**
     * Add widget to dashboard
     */
    public function addWidget(string $id, array $config): self
    {
        $this->widgets[$id] = array_merge([
            'type' => 'chart',
            'title' => 'Widget',
            'position' => ['x' => 0, 'y' => 0, 'width' => 6, 'height' => 4],
            'data_source' => null,
            'refresh_interval' => 300, // 5 minutes
            'filters' => [],
            'options' => []
        ], $config);
        
        return $this;
    }
    
    /**
     * Remove widget from dashboard
     */
    public function removeWidget(string $id): self
    {
        unset($this->widgets[$id]);
        return $this;
    }
    
    /**
     * Get widget configuration
     */
    public function getWidget(string $id): ?array
    {
        return $this->widgets[$id] ?? null;
    }
    
    /**
     * Get all widgets
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }
    
    /**
     * Set global filter
     */
    public function setFilter(string $key, mixed $value): self
    {
        $this->filters[$key] = $value;
        return $this;
    }
    
    /**
     * Get filter value
     */
    public function getFilter(string $key): mixed
    {
        return $this->filters[$key] ?? null;
    }
    
    /**
     * Set time range filter
     */
    public function setTimeRange(string $range): self
    {
        $this->timeRange = $range;
        return $this;
    }
    
    /**
     * Get dashboard data for all widgets
     */
    public function getData(): array
    {
        $data = [];
        
        foreach ($this->widgets as $id => $widget) {
            try {
                $data[$id] = $this->getWidgetData($id);
            } catch (Exception $e) {
                $data[$id] = [
                    'error' => true,
                    'message' => 'Failed to load widget data: ' . $e->getMessage()
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Get data for specific widget
     */
    public function getWidgetData(string $widgetId): array
    {
        $widget = $this->getWidget($widgetId);
        
        if (!$widget) {
            throw new Exception("Widget {$widgetId} not found");
        }
        
        $dataSource = $widget['data_source'];
        $mergedFilters = array_merge($this->filters, $widget['filters']);
        
        return match ($widget['type']) {
            'kpi' => $this->getKpiData($dataSource, $mergedFilters),
            'chart' => $this->getChartData($dataSource, $mergedFilters, $widget['options']),
            'table' => $this->getTableData($dataSource, $mergedFilters, $widget['options']),
            'map' => $this->getMapData($dataSource, $mergedFilters),
            'gauge' => $this->getGaugeData($dataSource, $mergedFilters),
            default => throw new Exception("Unknown widget type: {$widget['type']}")
        };
    }
    
    /**
     * Get KPI widget data
     */
    protected function getKpiData(array $dataSource, array $filters): array
    {
        $sql = $this->buildQuery($dataSource, $filters);
        $result = Connection::fetchOne($sql['query'], $sql['params']);
        
        $value = $result['value'] ?? 0;
        $previousValue = $this->getPreviousPeriodValue($dataSource, $filters);
        
        $analytics = new Analytics();
        $change = $analytics->percentageChange($previousValue, $value);
        
        return [
            'value' => $value,
            'previous_value' => $previousValue,
            'change' => $change,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'formatted_value' => $this->formatValue($value, $dataSource['format'] ?? 'number')
        ];
    }
    
    /**
     * Get chart widget data
     */
    protected function getChartData(array $dataSource, array $filters, array $options): array
    {
        $sql = $this->buildQuery($dataSource, $filters);
        $results = Connection::fetchAll($sql['query'], $sql['params']);
        
        $chartType = $options['chart_type'] ?? 'line';
        
        return [
            'type' => $chartType,
            'data' => $this->formatChartData($results, $chartType, $options),
            'options' => $options
        ];
    }
    
    /**
     * Get table widget data
     */
    protected function getTableData(array $dataSource, array $filters, array $options): array
    {
        $sql = $this->buildQuery($dataSource, $filters);
        $results = Connection::fetchAll($sql['query'], $sql['params']);
        
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 10;
        $offset = ($page - 1) * $perPage;
        
        // Add pagination to query
        $sql['query'] .= " LIMIT {$perPage} OFFSET {$offset}";
        $paginatedResults = Connection::fetchAll($sql['query'], $sql['params']);
        
        return [
            'data' => $paginatedResults,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($results),
                'total_pages' => ceil(count($results) / $perPage)
            ]
        ];
    }
    
    /**
     * Get map widget data
     */
    protected function getMapData(array $dataSource, array $filters): array
    {
        $sql = $this->buildQuery($dataSource, $filters);
        $results = Connection::fetchAll($sql['query'], $sql['params']);
        
        return [
            'type' => 'map',
            'data' => $results,
            'center' => $dataSource['center'] ?? [39.9334, 32.8597], // Ankara coordinates
            'zoom' => $dataSource['zoom'] ?? 6
        ];
    }
    
    /**
     * Get gauge widget data
     */
    protected function getGaugeData(array $dataSource, array $filters): array
    {
        $sql = $this->buildQuery($dataSource, $filters);
        $result = Connection::fetchOne($sql['query'], $sql['params']);
        
        $value = $result['value'] ?? 0;
        $min = $dataSource['min'] ?? 0;
        $max = $dataSource['max'] ?? 100;
        $target = $dataSource['target'] ?? $max * 0.8;
        
        return [
            'value' => $value,
            'min' => $min,
            'max' => $max,
            'target' => $target,
            'percentage' => (($value - $min) / ($max - $min)) * 100,
            'status' => $value >= $target ? 'good' : ($value >= $target * 0.7 ? 'warning' : 'critical')
        ];
    }
    
    /**
     * Build SQL query from data source configuration
     */
    protected function buildQuery(array $dataSource, array $filters): array
    {
        $query = $dataSource['query'];
        $params = [];
        
        // Apply time range filter
        if (isset($dataSource['date_field'])) {
            $dateRange = $this->parseDateRange($this->timeRange);
            $query .= " AND {$dataSource['date_field']} BETWEEN :start_date AND :end_date";
            $params['start_date'] = $dateRange['start'];
            $params['end_date'] = $dateRange['end'];
        }
        
        // Apply custom filters
        foreach ($filters as $key => $value) {
            if (isset($dataSource['filter_fields'][$key])) {
                $field = $dataSource['filter_fields'][$key];
                $query .= " AND {$field} = :{$key}";
                $params[$key] = $value;
            }
        }
        
        return ['query' => $query, 'params' => $params];
    }
    
    /**
     * Parse date range string to start and end dates
     */
    protected function parseDateRange(string $range): array
    {
        $end = date('Y-m-d H:i:s');
        
        $start = match ($range) {
            '1d' => date('Y-m-d H:i:s', strtotime('-1 day')),
            '7d' => date('Y-m-d H:i:s', strtotime('-7 days')),
            '30d' => date('Y-m-d H:i:s', strtotime('-30 days')),
            '90d' => date('Y-m-d H:i:s', strtotime('-90 days')),
            '1y' => date('Y-m-d H:i:s', strtotime('-1 year')),
            'mtd' => date('Y-m-01 00:00:00'),
            'ytd' => date('Y-01-01 00:00:00'),
            default => date('Y-m-d H:i:s', strtotime('-30 days'))
        };
        
        return ['start' => $start, 'end' => $end];
    }
    
    /**
     * Get previous period value for comparison
     */
    protected function getPreviousPeriodValue(array $dataSource, array $filters): float
    {
        // Implementation would depend on specific requirements
        // This is a simplified version
        return 0;
    }
    
    /**
     * Format chart data based on chart type
     */
    protected function formatChartData(array $data, string $chartType, array $options): array
    {
        return match ($chartType) {
            'line', 'area' => $this->formatTimeSeriesData($data),
            'bar', 'column' => $this->formatCategoryData($data),
            'pie', 'donut' => $this->formatPieData($data),
            'scatter' => $this->formatScatterData($data),
            default => $data
        };
    }
    
    /**
     * Format time series data
     */
    protected function formatTimeSeriesData(array $data): array
    {
        return array_map(fn($item) => [
            'x' => $item['date'] ?? $item['period'],
            'y' => $item['value']
        ], $data);
    }
    
    /**
     * Format category data
     */
    protected function formatCategoryData(array $data): array
    {
        return [
            'categories' => array_column($data, 'category'),
            'series' => [
                [
                    'name' => 'Value',
                    'data' => array_column($data, 'value')
                ]
            ]
        ];
    }
    
    /**
     * Format pie chart data
     */
    protected function formatPieData(array $data): array
    {
        return array_map(fn($item) => [
            'name' => $item['category'],
            'y' => $item['value']
        ], $data);
    }
    
    /**
     * Format scatter plot data
     */
    protected function formatScatterData(array $data): array
    {
        return array_map(fn($item) => [
            'x' => $item['x'],
            'y' => $item['y']
        ], $data);
    }
    
    /**
     * Format value based on type
     */
    protected function formatValue(mixed $value, string $format): string
    {
        return match ($format) {
            'currency' => '₺' . number_format($value, 2),
            'percentage' => number_format($value, 2) . '%',
            'number' => number_format($value),
            'decimal' => number_format($value, 2),
            default => (string) $value
        };
    }
    
    /**
     * Export dashboard configuration
     */
    public function export(): array
    {
        return [
            'widgets' => $this->widgets,
            'filters' => $this->filters,
            'time_range' => $this->timeRange,
            'permissions' => $this->permissions
        ];
    }
    
    /**
     * Import dashboard configuration
     */
    public function import(array $config): self
    {
        $this->widgets = $config['widgets'] ?? [];
        $this->filters = $config['filters'] ?? [];
        $this->timeRange = $config['time_range'] ?? '30d';
        $this->permissions = $config['permissions'] ?? [];
        
        return $this;
    }
}
