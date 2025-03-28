<?php
/**
 * Base Configuration and Helper Functions
 * Handles: Request methods, error handling, database setup, and HTML helpers
 */

// ============================================================================
// Error Reporting & Session Setup
// ============================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ============================================================================
// Database Configuration
// ============================================================================
require_once __DIR__ . '/Database.php';

class Base {
    private static $db;

    public static function getDB() {
        if (!self::$db) {
            self::$db = (new Database())->getConnection();
        }
        return self::$db;
    }

    // ============================================================================
    // Request Handlers
    // ============================================================================

    /**
     * Check if request is GET
     */
    public static function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Check if request is POST
     */
    public static function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Get sanitized GET parameter
     */
    public static function get($key, $default = null) {
        $value = $_GET[$key] ?? $default;
        return is_array($value) ? 
            array_map('self::sanitize', $value) : 
            self::sanitize($value);
    }

    /**
     * Get sanitized POST parameter
     */
    public static function post($key, $default = null) {
        $value = $_POST[$key] ?? $default;
        return is_array($value) ? 
            array_map('self::sanitize', $value) : 
            self::sanitize($value);
    }

    /**
     * Get sanitized REQUEST parameter (GET + POST)
     */
    public static function request($key, $default = null) {
        $value = $_REQUEST[$key] ?? $default;
        return is_array($value) ? 
            array_map('self::sanitize', $value) : 
            self::sanitize($value);
    }

    // ============================================================================
    // Security Helpers
    // ============================================================================

    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_null($data)) return null;
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate CSRF token
     */
    public static function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrf($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    // ============================================================================
    // HTML Helpers
    // ============================================================================

    /**
     * Render HTML with escaped variables
     */
    public static function render($template, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../templates/$template.php";
        return ob_get_clean();
    }

    /**
     * Redirect with optional flash message
     */
    public static function redirect($url, $flash = null) {
        if ($flash) {
            $_SESSION['flash'] = $flash;
        }
        header("Location: $url");
        exit();
    }

    /**
     * Display flash message
     */
    public static function flash() {
        if (!empty($_SESSION['flash'])) {
            $message = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return '<div class="flash-message">' . self::sanitize($message) . '</div>';
        }
        return '';
    }

    // ============================================================================
    // Error Handlers
    // ============================================================================

    /**
     * Log error to file
     */
    public static function logError($error) {
        $logFile = __DIR__ . '/../logs/error.log';
        $timestamp = date('[Y-m-d H:i:s]');
        error_log("$timestamp $error\n", 3, $logFile);
    }

    /**
     * Handle exceptions
     */
    public static function handleException($e) {
        self::logError($e->getMessage());
        if (ini_get('display_errors')) {
            die('<div class="error">System Error: ' . self::sanitize($e->getMessage()) . '</div>');
        } else {
            die('An error occurred. Please try again later.');
        }
    }

    /**
     * 404 Not Found handler
     */
    public static function notFound() {
        http_response_code(404);
        die(self::render('errors/404'));
    }
}

// Register error handlers
set_exception_handler(['Base', 'handleException']);
set_error_handler(function($level, $message, $file = '', $line = 0) {
    Base::logError("$message in $file on line $line");
});

// Timezone setup
date_default_timezone_set('Asia/Kuala_Lumpur');