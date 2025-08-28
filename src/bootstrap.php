<?php

/**
 * Bootstrap file for Luxury Watch BI System
 * Initializes the application and sets up dependencies
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Europe/Istanbul');

// Define application root
define('APP_ROOT', dirname(__DIR__));

// Autoload dependencies
require_once APP_ROOT . '/vendor/autoload.php';

use LuxuryWatch\BI\Core\Config;
use LuxuryWatch\BI\Database\Connection;

try {
    // Load configuration
    Config::load();
    
    // Validate configuration
    $configErrors = Config::validate();
    if (!empty($configErrors)) {
        throw new Exception('Configuration errors: ' . implode(', ', $configErrors));
    }
    
    // Initialize database connection
    Connection::init(Config::getDatabase());
    
    // Set application timezone
    $timezone = Config::get('APP_TIMEZONE', 'Europe/Istanbul');
    date_default_timezone_set($timezone);
    
    // Set error handling based on environment
    if (Config::isDebug()) {
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
    } else {
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
    }
    
    // Set error log file
    $logFile = APP_ROOT . '/' . Config::get('LOG_FILE', 'logs/app.log');
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    ini_set('error_log', $logFile);
    
} catch (Exception $e) {
    // Handle bootstrap errors
    http_response_code(500);
    
    if (Config::isDebug()) {
        echo "Bootstrap Error: " . $e->getMessage();
    } else {
        echo "Application initialization failed. Please check configuration.";
    }
    
    error_log("Bootstrap Error: " . $e->getMessage());
    exit(1);
}

/**
 * Helper function to get application instance
 */
function app(): array
{
    return [
        'name' => Config::get('APP_NAME'),
        'version' => '1.0.0',
        'env' => Config::get('APP_ENV'),
        'debug' => Config::isDebug(),
        'timezone' => Config::get('APP_TIMEZONE')
    ];
}

/**
 * Helper function to format currency
 */
function formatCurrency(float $amount, string $currency = 'TRY'): string
{
    $symbols = [
        'TRY' => '₺',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($amount, 2);
}

/**
 * Helper function to format percentage
 */
function formatPercentage(float $value, int $decimals = 2): string
{
    return number_format($value, $decimals) . '%';
}

/**
 * Helper function to format number
 */
function formatNumber(float $value, int $decimals = 0): string
{
    return number_format($value, $decimals);
}

/**
 * Helper function to get date range
 */
function getDateRange(string $range): array
{
    $end = date('Y-m-d');
    
    $start = match ($range) {
        'today' => date('Y-m-d'),
        'yesterday' => date('Y-m-d', strtotime('-1 day')),
        'last_7_days' => date('Y-m-d', strtotime('-7 days')),
        'last_30_days' => date('Y-m-d', strtotime('-30 days')),
        'last_90_days' => date('Y-m-d', strtotime('-90 days')),
        'this_month' => date('Y-m-01'),
        'last_month' => date('Y-m-01', strtotime('-1 month')),
        'this_year' => date('Y-01-01'),
        'last_year' => date('Y-01-01', strtotime('-1 year')),
        default => date('Y-m-d', strtotime('-30 days'))
    };
    
    return ['start' => $start, 'end' => $end];
}

/**
 * Helper function to sanitize input
 */
function sanitizeInput(mixed $input): mixed
{
    if (is_string($input)) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    return $input;
}

/**
 * Helper function to validate email
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Helper function to generate UUID
 */
function generateUuid(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Helper function to log messages
 */
function logMessage(string $level, string $message, array $context = []): void
{
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
    
    $logFile = APP_ROOT . '/' . Config::get('LOG_FILE', 'logs/app.log');
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Helper function to send JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Helper function to handle errors
 */
function handleError(Exception $e, bool $isApi = false): void
{
    $errorId = generateUuid();
    $message = Config::isDebug() ? $e->getMessage() : 'An error occurred';
    
    // Log the error
    logMessage('ERROR', "Error ID: {$errorId} - " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    if ($isApi) {
        jsonResponse([
            'error' => true,
            'message' => $message,
            'error_id' => $errorId
        ], 500);
    } else {
        http_response_code(500);
        echo "Error: {$message} (ID: {$errorId})";
        exit;
    }
}

/**
 * Set global error handler
 */
set_exception_handler(function($e) {
    handleError($e, false);
});

/**
 * Set global error handler for fatal errors
 */
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $exception = new Exception($error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
        handleError($exception, false);
    }
});

// Application successfully bootstrapped
logMessage('INFO', 'Application bootstrapped successfully');
