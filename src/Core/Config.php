<?php

namespace LuxuryWatch\BI\Core;

use Exception;

/**
 * Configuration Manager
 * Handles application configuration and environment variables
 */
class Config
{
    private static array $config = [];
    private static bool $loaded = false;
    
    /**
     * Load configuration from .env file
     */
    public static function load(string $envPath = null): void
    {
        if (self::$loaded) {
            return;
        }
        
        $envPath = $envPath ?: dirname(__DIR__, 2) . '/.env';
        
        if (!file_exists($envPath)) {
            throw new Exception(".env file not found at: {$envPath}");
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                self::$config[$key] = $value;
                
                // Also set as environment variable
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get configuration value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }
        
        // Check environment variables first
        if (isset($_ENV[$key])) {
            return self::castValue($_ENV[$key]);
        }
        
        // Then check loaded config
        if (isset(self::$config[$key])) {
            return self::castValue(self::$config[$key]);
        }
        
        return $default;
    }
    
    /**
     * Set configuration value
     */
    public static function set(string $key, mixed $value): void
    {
        self::$config[$key] = $value;
        $_ENV[$key] = $value;
    }
    
    /**
     * Get database configuration
     */
    public static function getDatabase(): array
    {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'port' => (int) self::get('DB_PORT', 3306),
            'database' => self::get('DB_NAME'),
            'username' => self::get('DB_USERNAME'),
            'password' => self::get('DB_PASSWORD'),
            'charset' => self::get('DB_CHARSET', 'utf8mb4'),
            'timezone' => self::get('APP_TIMEZONE', '+00:00')
        ];
    }
    
    /**
     * Get application configuration
     */
    public static function getApp(): array
    {
        return [
            'name' => self::get('APP_NAME', 'Luxury Watch BI'),
            'env' => self::get('APP_ENV', 'production'),
            'debug' => self::get('APP_DEBUG', false),
            'timezone' => self::get('APP_TIMEZONE', 'Europe/Istanbul'),
            'secret_key' => self::get('APP_SECRET_KEY'),
            'jwt_secret' => self::get('JWT_SECRET'),
            'encryption_key' => self::get('ENCRYPTION_KEY')
        ];
    }
    
    /**
     * Get email configuration
     */
    public static function getEmail(): array
    {
        return [
            'host' => self::get('MAIL_HOST'),
            'port' => (int) self::get('MAIL_PORT', 587),
            'username' => self::get('MAIL_USERNAME'),
            'password' => self::get('MAIL_PASSWORD'),
            'encryption' => self::get('MAIL_ENCRYPTION', 'tls'),
            'from_address' => self::get('MAIL_FROM_ADDRESS'),
            'from_name' => self::get('MAIL_FROM_NAME')
        ];
    }
    
    /**
     * Get API configuration
     */
    public static function getApi(): array
    {
        return [
            'version' => self::get('API_VERSION', 'v1'),
            'rate_limit' => (int) self::get('API_RATE_LIMIT', 1000),
            'cache_ttl' => (int) self::get('API_CACHE_TTL', 3600)
        ];
    }
    
    /**
     * Get cache configuration
     */
    public static function getCache(): array
    {
        return [
            'driver' => self::get('CACHE_DRIVER', 'file'),
            'ttl' => (int) self::get('CACHE_TTL', 3600)
        ];
    }
    
    /**
     * Get logging configuration
     */
    public static function getLogging(): array
    {
        return [
            'level' => self::get('LOG_LEVEL', 'info'),
            'file' => self::get('LOG_FILE', 'logs/app.log')
        ];
    }
    
    /**
     * Get report configuration
     */
    public static function getReports(): array
    {
        return [
            'cache_ttl' => (int) self::get('REPORT_CACHE_TTL', 1800),
            'dashboard_refresh_interval' => (int) self::get('DASHBOARD_REFRESH_INTERVAL', 300)
        ];
    }
    
    /**
     * Check if application is in debug mode
     */
    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG', false);
    }
    
    /**
     * Check if application is in production
     */
    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }
    
    /**
     * Cast string values to appropriate types
     */
    private static function castValue(string $value): mixed
    {
        // Boolean values
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }
        
        // Numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }
        
        // Null values
        if (strtolower($value) === 'null') {
            return null;
        }
        
        return $value;
    }
    
    /**
     * Validate required configuration
     */
    public static function validate(): array
    {
        $errors = [];
        
        $required = [
            'DB_HOST' => 'Database host is required',
            'DB_NAME' => 'Database name is required',
            'DB_USERNAME' => 'Database username is required',
            'DB_PASSWORD' => 'Database password is required',
            'APP_SECRET_KEY' => 'Application secret key is required'
        ];
        
        foreach ($required as $key => $message) {
            if (empty(self::get($key))) {
                $errors[] = $message;
            }
        }
        
        return $errors;
    }
    
    /**
     * Get all configuration
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config;
    }
    
    /**
     * Clear configuration cache
     */
    public static function clear(): void
    {
        self::$config = [];
        self::$loaded = false;
    }
}
