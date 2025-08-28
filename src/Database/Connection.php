<?php

namespace LuxuryWatch\BI\Database;

use PDO;
use PDOException;
use Exception;

/**
 * Secure PDO Database Connection Manager
 * Handles database connections with security best practices
 */
class Connection
{
    private static ?PDO $instance = null;
    private static array $config = [];
    
    /**
     * Initialize database configuration
     */
    public static function init(array $config): void
    {
        self::$config = array_merge([
            'host' => 'localhost',
            'port' => 3306,
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ], $config);
    }
    
    /**
     * Get singleton PDO instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private static function connect(): void
    {
        if (empty(self::$config)) {
            throw new Exception('Database configuration not initialized. Call Connection::init() first.');
        }
        
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::$config['host'],
            self::$config['port'],
            self::$config['database'],
            self::$config['charset']
        );
        
        try {
            self::$instance = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                self::$config['options']
            );
            
            // Set timezone
            if (isset(self::$config['timezone'])) {
                self::$instance->exec("SET time_zone = '" . self::$config['timezone'] . "'");
            }
            
        } catch (PDOException $e) {
            // Log error securely without exposing credentials
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please check configuration.');
        }
    }
    
    /**
     * Execute a prepared statement with parameters
     */
    public static function execute(string $sql, array $params = []): \PDOStatement
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last inserted ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }
    
    /**
     * Execute transaction with callback
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getInstance();
        
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Check if connection is alive
     */
    public static function isConnected(): bool
    {
        try {
            if (self::$instance === null) {
                return false;
            }
            
            self::$instance->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
    
    /**
     * Get connection info for debugging
     */
    public static function getConnectionInfo(): array
    {
        if (self::$instance === null) {
            return ['status' => 'Not connected'];
        }
        
        return [
            'status' => 'Connected',
            'server_info' => self::$instance->getAttribute(PDO::ATTR_SERVER_INFO),
            'client_version' => self::$instance->getAttribute(PDO::ATTR_CLIENT_VERSION),
            'connection_status' => self::$instance->getAttribute(PDO::ATTR_CONNECTION_STATUS)
        ];
    }
}
