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
require 'database.php';

class Base
{
    private static $db;
    private static $user = null;

    // Initialize user from session
    public static function init() {
        self::$user = $_SESSION['user'] ?? null;
    }

    // ============================================================================
    // Database Methods
    // ============================================================================

    public static function getDB()
    {
        if (!self::$db) {
            self::$db = (new Database())->getConnection();
        }
        return self::$db;
    }

    // ============================================================================
    // Security Methods
    // ============================================================================

    /**
     * Redirect to URL
     */
    public static function redirect($url, $statusCode = 302) {
        header("Location: $url", true, $statusCode);
        exit();
    }

    /**
     * Login user and redirect
     */
    public static function login($user, $url = '/') {
        self::$user = $_SESSION['user'] = $user;
        session_regenerate_id(true);
        self::redirect($url);
    }

    /**
     * Logout and redirect
     */
    public static function logout($url = '/') {
        self::$user = null;
        unset($_SESSION['user']);
        session_destroy();
        self::redirect($url);
    }

    /**
     * Authorization check
     */
    public static function auth(...$roles) {
        if (!self::isLoggedIn()) {
            $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
            self::redirect('/login.php');
        }
        
        if (!empty($roles)) {
            $user_role = strtolower(self::$user->role ?? '');
            $required_roles = array_map('strtolower', $roles);
            
            if (!in_array($user_role, $required_roles)) {
                self::redirect('/unauthorized.php');
            }
        }
    }

    /**
     * Check login status
     */
    public static function isLoggedIn() {
        return self::$user !== null;
    }

    /**
     * Handle authentication for protected links
     */
    public static function checkAuth($redirect_url = '/') {
        if (self::isLoggedIn()) {
            self::redirect($redirect_url);
        } else {
            $_SESSION['return_url'] = $redirect_url;
            self::redirect('/login.php');
        }
    }




    // ============================================================================
    // product
    // ============================================================================
    
    public static function userHasPurchased($product_id) {
        if (!self::isLoggedIn()) {
            return false;
        }
    
        try {
            $db = self::getDB();
            $stmt = $db->prepare("SELECT 1 FROM order_items oi
                                JOIN orders o ON oi.order_id = o.order_id
                                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'");
            $stmt->execute([self::$user->id, $product_id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            self::logError($e->getMessage());
            return false;
        }
    }



    // ============================================================================
    // Request Handlers
    // ============================================================================

    public static function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    public static function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function get($key, $default = null) {
        $value = $_GET[$key] ?? $default;
        return is_array($value) ?
            array_map('self::sanitize', $value) :
            self::sanitize($value);
    }

    public static function post($key, $default = null) {
        $value = $_POST[$key] ?? $default;
        return is_array($value) ?
            array_map('self::sanitize', $value) :
            self::sanitize($value);
    }

    public static function request($key, $default = null) {
        $value = $_REQUEST[$key] ?? $default;
        return is_array($value) ?
            array_map('self::sanitize', $value) :
            self::sanitize($value);
    }

    // ============================================================================
    // Security Helpers
    // ============================================================================

    public static function sanitize($data) {
        if (is_null($data)) return null;
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    // ============================================================================
    // HTML Helpers
    // ============================================================================

    public static function render($template, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../templates/$template.php";
        return ob_get_clean();
    }

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

    public static function logError($error) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/error.log';
        $timestamp = date('[Y-m-d H:i:s]');
        error_log("$timestamp $error\n", 3, $logFile);
    }

    public static function handleException($e) {
        self::logError($e->getMessage());
        if (ini_get('display_errors')) {
            die('<div class="error">System Error: ' . self::sanitize($e->getMessage()) . '</div>');
        } else {
            die('An error occurred. Please try again later.');
        }
    }

    public static function notFound() {
        http_response_code(404);
        die(self::render('errors/404'));
    }

    public static function temp($key, $value = null)
    {
        if ($value !== null) {
            $_SESSION["temp_$key"] = $value;
        } else {
            $value = $_SESSION["temp_$key"] ?? null;
            unset($_SESSION["temp_$key"]);
            return $value;
        }
    }
}

// Initialize the Base class
Base::init();

// Register error handlers
set_exception_handler(['Base', 'handleException']);
set_error_handler(function ($level, $message, $file = '', $line = 0) {
    Base::logError("$message in $file on line $line");
});

// Timezone setup
date_default_timezone_set('Asia/Kuala_Lumpur');