<?php

namespace LuxuryWatch\BI\Core;

use LuxuryWatch\BI\Database\Connection;
use Exception;

/**
 * Report Generation and Management System
 * Handles report creation, scheduling, and export functionality
 */
class Report
{
    protected string $title = '';
    protected string $description = '';
    protected array $sections = [];
    protected array $parameters = [];
    protected array $filters = [];
    protected string $template = 'default';
    protected array $exportFormats = ['pdf', 'excel', 'csv', 'json'];
    
    public function __construct(string $title = '', string $description = '')
    {
        $this->title = $title;
        $this->description = $description;
    }
    
    /**
     * Set report title
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Set report description
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    /**
     * Add section to report
     */
    public function addSection(string $id, array $config): self
    {
        $this->sections[$id] = array_merge([
            'title' => 'Section',
            'type' => 'table',
            'data_source' => null,
            'template' => null,
            'options' => []
        ], $config);
        
        return $this;
    }
    
    /**
     * Add table section
     */
    public function addTableSection(string $id, string $title, array $dataSource, array $options = []): self
    {
        return $this->addSection($id, [
            'title' => $title,
            'type' => 'table',
            'data_source' => $dataSource,
            'options' => $options
        ]);
    }
    
    /**
     * Add chart section
     */
    public function addChartSection(string $id, string $title, array $dataSource, string $chartType = 'line', array $options = []): self
    {
        return $this->addSection($id, [
            'title' => $title,
            'type' => 'chart',
            'data_source' => $dataSource,
            'options' => array_merge(['chart_type' => $chartType], $options)
        ]);
    }
    
    /**
     * Add KPI section
     */
    public function addKpiSection(string $id, string $title, array $dataSource, array $options = []): self
    {
        return $this->addSection($id, [
            'title' => $title,
            'type' => 'kpi',
            'data_source' => $dataSource,
            'options' => $options
        ]);
    }
    
    /**
     * Add text section
     */
    public function addTextSection(string $id, string $title, string $content): self
    {
        return $this->addSection($id, [
            'title' => $title,
            'type' => 'text',
            'content' => $content
        ]);
    }
    
    /**
     * Set report parameter
     */
    public function setParameter(string $key, mixed $value): self
    {
        $this->parameters[$key] = $value;
        return $this;
    }
    
    /**
     * Set multiple parameters
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }
    
    /**
     * Add filter
     */
    public function addFilter(string $key, mixed $value): self
    {
        $this->filters[$key] = $value;
        return $this;
    }
    
    /**
     * Set template
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }
    
    /**
     * Generate report data
     */
    public function generate(): array
    {
        $reportData = [
            'title' => $this->title,
            'description' => $this->description,
            'generated_at' => date('Y-m-d H:i:s'),
            'parameters' => $this->parameters,
            'sections' => []
        ];
        
        foreach ($this->sections as $id => $section) {
            try {
                $reportData['sections'][$id] = $this->generateSection($section);
            } catch (Exception $e) {
                $reportData['sections'][$id] = [
                    'error' => true,
                    'message' => 'Failed to generate section: ' . $e->getMessage()
                ];
            }
        }
        
        return $reportData;
    }
    
    /**
     * Generate individual section
     */
    protected function generateSection(array $section): array
    {
        $sectionData = [
            'title' => $section['title'],
            'type' => $section['type'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        switch ($section['type']) {
            case 'table':
                $sectionData['data'] = $this->generateTableData($section);
                break;
                
            case 'chart':
                $sectionData['data'] = $this->generateChartData($section);
                break;
                
            case 'kpi':
                $sectionData['data'] = $this->generateKpiData($section);
                break;
                
            case 'text':
                $sectionData['content'] = $this->processTextContent($section['content']);
                break;
                
            default:
                throw new Exception("Unknown section type: {$section['type']}");
        }
        
        return $sectionData;
    }
    
    /**
     * Generate table data
     */
    protected function generateTableData(array $section): array
    {
        $dataSource = $section['data_source'];
        $sql = $this->buildQuery($dataSource);
        $results = Connection::fetchAll($sql['query'], $sql['params']);
        
        $options = $section['options'];
        
        // Apply sorting if specified
        if (isset($options['sort_by'])) {
            $sortField = $options['sort_by'];
            $sortDirection = $options['sort_direction'] ?? 'asc';
            
            usort($results, function($a, $b) use ($sortField, $sortDirection) {
                $comparison = $a[$sortField] <=> $b[$sortField];
                return $sortDirection === 'desc' ? -$comparison : $comparison;
            });
        }
        
        // Apply pagination if specified
        if (isset($options['limit'])) {
            $offset = $options['offset'] ?? 0;
            $results = array_slice($results, $offset, $options['limit']);
        }
        
        return [
            'headers' => $this->extractHeaders($results),
            'rows' => $results,
            'total_rows' => count($results),
            'options' => $options
        ];
    }
    
    /**
     * Generate chart data
     */
    protected function generateChartData(array $section): array
    {
        $dataSource = $section['data_source'];
        $sql = $this->buildQuery($dataSource);
        $results = Connection::fetchAll($sql['query'], $sql['params']);
        
        $chartType = $section['options']['chart_type'] ?? 'line';
        
        return [
            'type' => $chartType,
            'data' => $this->formatChartData($results, $chartType),
            'options' => $section['options']
        ];
    }
    
    /**
     * Generate KPI data
     */
    protected function generateKpiData(array $section): array
    {
        $dataSource = $section['data_source'];
        $sql = $this->buildQuery($dataSource);
        $result = Connection::fetchOne($sql['query'], $sql['params']);
        
        $value = $result['value'] ?? 0;
        $target = $section['options']['target'] ?? null;
        $format = $section['options']['format'] ?? 'number';
        
        $kpiData = [
            'value' => $value,
            'formatted_value' => $this->formatValue($value, $format),
            'target' => $target
        ];
        
        if ($target !== null) {
            $kpiData['achievement_rate'] = ($value / $target) * 100;
            $kpiData['status'] = $value >= $target ? 'achieved' : 'not_achieved';
        }
        
        return $kpiData;
    }
    
    /**
     * Process text content with parameter substitution
     */
    protected function processTextContent(string $content): string
    {
        foreach ($this->parameters as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Build SQL query from data source
     */
    protected function buildQuery(array $dataSource): array
    {
        $query = $dataSource['query'];
        $params = array_merge($this->parameters, $this->filters);
        
        // Apply date range if specified
        if (isset($dataSource['date_field']) && isset($this->parameters['date_range'])) {
            $dateRange = $this->parseDateRange($this->parameters['date_range']);
            $query .= " AND {$dataSource['date_field']} BETWEEN :start_date AND :end_date";
            $params['start_date'] = $dateRange['start'];
            $params['end_date'] = $dateRange['end'];
        }
        
        return ['query' => $query, 'params' => $params];
    }
    
    /**
     * Parse date range parameter
     */
    protected function parseDateRange(string $range): array
    {
        if (strpos($range, ' to ') !== false) {
            [$start, $end] = explode(' to ', $range);
            return ['start' => $start, 'end' => $end];
        }
        
        $end = date('Y-m-d');
        $start = match ($range) {
            'today' => date('Y-m-d'),
            'yesterday' => date('Y-m-d', strtotime('-1 day')),
            'last_7_days' => date('Y-m-d', strtotime('-7 days')),
            'last_30_days' => date('Y-m-d', strtotime('-30 days')),
            'this_month' => date('Y-m-01'),
            'last_month' => date('Y-m-01', strtotime('-1 month')),
            'this_year' => date('Y-01-01'),
            default => date('Y-m-d', strtotime('-30 days'))
        };
        
        return ['start' => $start, 'end' => $end];
    }
    
    /**
     * Extract headers from result set
     */
    protected function extractHeaders(array $results): array
    {
        if (empty($results)) {
            return [];
        }
        
        return array_keys($results[0]);
    }
    
    /**
     * Format chart data based on type
     */
    protected function formatChartData(array $data, string $chartType): array
    {
        return match ($chartType) {
            'line', 'area' => array_map(fn($item) => [
                'x' => $item['date'] ?? $item['period'],
                'y' => $item['value']
            ], $data),
            'bar', 'column' => [
                'categories' => array_column($data, 'category'),
                'values' => array_column($data, 'value')
            ],
            'pie' => array_map(fn($item) => [
                'name' => $item['category'],
                'value' => $item['value']
            ], $data),
            default => $data
        };
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
     * Export report to specified format
     */
    public function export(string $format = 'pdf'): array
    {
        if (!in_array($format, $this->exportFormats)) {
            throw new Exception("Unsupported export format: {$format}");
        }
        
        $reportData = $this->generate();
        
        return match ($format) {
            'json' => $this->exportToJson($reportData),
            'csv' => $this->exportToCsv($reportData),
            'excel' => $this->exportToExcel($reportData),
            'pdf' => $this->exportToPdf($reportData),
            default => throw new Exception("Export format not implemented: {$format}")
        };
    }
    
    /**
     * Export to JSON
     */
    protected function exportToJson(array $data): array
    {
        return [
            'content' => json_encode($data, JSON_PRETTY_PRINT),
            'mime_type' => 'application/json',
            'filename' => $this->generateFilename('json')
        ];
    }
    
    /**
     * Export to CSV (simplified - exports first table section)
     */
    protected function exportToCsv(array $data): array
    {
        $csv = '';
        
        foreach ($data['sections'] as $section) {
            if ($section['type'] === 'table' && isset($section['data']['rows'])) {
                $headers = $section['data']['headers'];
                $rows = $section['data']['rows'];
                
                $csv .= implode(',', $headers) . "\n";
                
                foreach ($rows as $row) {
                    $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
                }
                break;
            }
        }
        
        return [
            'content' => $csv,
            'mime_type' => 'text/csv',
            'filename' => $this->generateFilename('csv')
        ];
    }
    
    /**
     * Export to Excel (placeholder)
     */
    protected function exportToExcel(array $data): array
    {
        // This would require a library like PhpSpreadsheet
        throw new Exception('Excel export not implemented yet');
    }
    
    /**
     * Export to PDF (placeholder)
     */
    protected function exportToPdf(array $data): array
    {
        // This would require a library like TCPDF or DOMPDF
        throw new Exception('PDF export not implemented yet');
    }
    
    /**
     * Generate filename for export
     */
    protected function generateFilename(string $extension): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->title));
        $timestamp = date('Y-m-d_H-i-s');
        
        return "{$slug}_{$timestamp}.{$extension}";
    }
    
    /**
     * Get report configuration
     */
    public function getConfig(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'sections' => $this->sections,
            'parameters' => $this->parameters,
            'filters' => $this->filters,
            'template' => $this->template
        ];
    }
    
    /**
     * Load report from configuration
     */
    public function loadConfig(array $config): self
    {
        $this->title = $config['title'] ?? '';
        $this->description = $config['description'] ?? '';
        $this->sections = $config['sections'] ?? [];
        $this->parameters = $config['parameters'] ?? [];
        $this->filters = $config['filters'] ?? [];
        $this->template = $config['template'] ?? 'default';
        
        return $this;
    }
}
