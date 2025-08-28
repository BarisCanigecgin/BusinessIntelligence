<?php

namespace LuxuryWatch\BI\Database;

use Exception;

/**
 * SQL Query Builder for secure database operations
 */
class QueryBuilder
{
    private string $table = '';
    private array $select = ['*'];
    private array $joins = [];
    private array $where = [];
    private array $groupBy = [];
    private array $having = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $params = [];
    
    public function __construct(string $table = '')
    {
        $this->table = $table;
    }
    
    /**
     * Set table name
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    
    /**
     * Set SELECT fields
     */
    public function select(array|string $fields): self
    {
        $this->select = is_array($fields) ? $fields : [$fields];
        return $this;
    }
    
    /**
     * Add JOIN clause
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->joins[] = "{$type} JOIN {$table} ON {$condition}";
        return $this;
    }
    
    /**
     * Add LEFT JOIN clause
     */
    public function leftJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'LEFT');
    }
    
    /**
     * Add WHERE clause
     */
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = ':where_' . count($this->params);
        $this->where[] = "{$column} {$operator} {$placeholder}";
        $this->params[$placeholder] = $value;
        
        return $this;
    }
    
    /**
     * Add WHERE IN clause
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = ':wherein_' . count($this->params) . '_' . $i;
            $placeholders[] = $placeholder;
            $this->params[$placeholder] = $value;
        }
        
        $this->where[] = "{$column} IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }
    
    /**
     * Add WHERE BETWEEN clause
     */
    public function whereBetween(string $column, mixed $start, mixed $end): self
    {
        $startPlaceholder = ':between_start_' . count($this->params);
        $endPlaceholder = ':between_end_' . count($this->params);
        
        $this->where[] = "{$column} BETWEEN {$startPlaceholder} AND {$endPlaceholder}";
        $this->params[$startPlaceholder] = $start;
        $this->params[$endPlaceholder] = $end;
        
        return $this;
    }
    
    /**
     * Add WHERE LIKE clause
     */
    public function whereLike(string $column, string $pattern): self
    {
        $placeholder = ':like_' . count($this->params);
        $this->where[] = "{$column} LIKE {$placeholder}";
        $this->params[$placeholder] = $pattern;
        
        return $this;
    }
    
    /**
     * Add raw WHERE clause
     */
    public function whereRaw(string $condition, array $params = []): self
    {
        $this->where[] = $condition;
        $this->params = array_merge($this->params, $params);
        return $this;
    }
    
    /**
     * Add GROUP BY clause
     */
    public function groupBy(array|string $columns): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }
    
    /**
     * Add HAVING clause
     */
    public function having(string $condition, array $params = []): self
    {
        $this->having[] = $condition;
        $this->params = array_merge($this->params, $params);
        return $this;
    }
    
    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new Exception('Invalid order direction. Use ASC or DESC.');
        }
        
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }
    
    /**
     * Set LIMIT clause
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * Set OFFSET clause
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * Build SELECT query
     */
    public function toSql(): string
    {
        if (empty($this->table)) {
            throw new Exception('Table name is required');
        }
        
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->table;
        
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }
        
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        
        return $sql;
    }
    
    /**
     * Get query parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     * Execute query and return all results
     */
    public function get(): array
    {
        return Connection::fetchAll($this->toSql(), $this->params);
    }
    
    /**
     * Execute query and return first result
     */
    public function first(): ?array
    {
        $this->limit(1);
        return Connection::fetchOne($this->toSql(), $this->params);
    }
    
    /**
     * Get count of records
     */
    public function count(string $column = '*'): int
    {
        $originalSelect = $this->select;
        $this->select = ["COUNT({$column}) as count"];
        
        $result = Connection::fetchOne($this->toSql(), $this->params);
        $this->select = $originalSelect;
        
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Check if records exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }
    
    /**
     * Reset query builder
     */
    public function reset(): self
    {
        $this->select = ['*'];
        $this->joins = [];
        $this->where = [];
        $this->groupBy = [];
        $this->having = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->params = [];
        
        return $this;
    }
}
