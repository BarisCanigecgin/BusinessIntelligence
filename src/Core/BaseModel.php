<?php

namespace LuxuryWatch\BI\Core;

use LuxuryWatch\BI\Database\Connection;
use LuxuryWatch\BI\Database\QueryBuilder;
use Exception;

/**
 * Base Model class for all data models
 * Provides common database operations and utilities
 */
abstract class BaseModel
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $dates = ['created_at', 'updated_at'];
    
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    
    /**
     * Get table name
     */
    public function getTable(): string
    {
        return $this->table;
    }
    
    /**
     * Get primary key
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }
    
    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Check if attribute is fillable
     */
    protected function isFillable(string $key): bool
    {
        return empty($this->fillable) || in_array($key, $this->fillable);
    }
    
    /**
     * Set attribute value
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
    
    /**
     * Get attribute value
     */
    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }
        
        return null;
    }
    
    /**
     * Cast attribute to appropriate type
     */
    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }
        
        $castType = $this->casts[$key];
        
        return match ($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array' => is_string($value) ? json_decode($value, true) : $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'date' => $value ? date('Y-m-d', strtotime($value)) : null,
            'datetime' => $value ? date('Y-m-d H:i:s', strtotime($value)) : null,
            default => $value
        };
    }
    
    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $attributes[$key] = $this->castAttribute($key, $value);
            }
        }
        
        return $attributes;
    }
    
    /**
     * Convert model to array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }
    
    /**
     * Convert model to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Save model to database
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }
    
    /**
     * Insert new record
     */
    protected function insert(): bool
    {
        $attributes = $this->getAttributesForSave();
        
        if (in_array('created_at', $this->dates)) {
            $attributes['created_at'] = date('Y-m-d H:i:s');
        }
        
        if (in_array('updated_at', $this->dates)) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $columns = array_keys($attributes);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            Connection::execute($sql, $attributes);
            
            if ($this->primaryKey && !isset($attributes[$this->primaryKey])) {
                $this->setAttribute($this->primaryKey, Connection::lastInsertId());
            }
            
            $this->exists = true;
            $this->original = $this->attributes;
            
            return true;
        } catch (Exception $e) {
            error_log('Model insert failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update existing record
     */
    protected function update(): bool
    {
        $attributes = $this->getAttributesForSave();
        
        if (in_array('updated_at', $this->dates)) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $setParts = array_map(fn($col) => "{$col} = :{$col}", array_keys($attributes));
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE {$this->primaryKey} = :pk_value";
        
        $attributes['pk_value'] = $this->getAttribute($this->primaryKey);
        
        try {
            Connection::execute($sql, $attributes);
            $this->original = $this->attributes;
            return true;
        } catch (Exception $e) {
            error_log('Model update failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get attributes for saving (excluding hidden and non-fillable)
     */
    protected function getAttributesForSave(): array
    {
        $attributes = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden) && $this->isFillable($key)) {
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Delete model from database
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :pk_value";
        
        try {
            Connection::execute($sql, ['pk_value' => $this->getAttribute($this->primaryKey)]);
            $this->exists = false;
            return true;
        } catch (Exception $e) {
            error_log('Model delete failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find model by primary key
     */
    public static function find(mixed $id): ?static
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = :id LIMIT 1";
        $result = Connection::fetchOne($sql, ['id' => $id]);
        
        if ($result) {
            $model = new static($result);
            $model->exists = true;
            $model->original = $model->attributes;
            return $model;
        }
        
        return null;
    }
    
    /**
     * Get query builder instance
     */
    public static function query(): QueryBuilder
    {
        $instance = new static();
        return new QueryBuilder($instance->table);
    }
    
    /**
     * Get all records
     */
    public static function all(): array
    {
        return static::query()->get();
    }
    
    /**
     * Create new model instance
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
    
    /**
     * Magic getter
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Magic isset
     */
    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }
}
