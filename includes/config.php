<?php
/**
 * NewBytes WooCommerce Connector - Security Configuration
 * 
 * @package NewBytes_WooCommerce_Connector
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Security Configuration Class
 *
 * Provides comprehensive security features including request validation,
 * input sanitization, rate limiting, and security event logging.
 *
 * @since 1.0.0
 */
class NB_Security_Config {
    
    /**
     * Initialize security configurations
     *
     * Sets up security hooks, filters, and actions for comprehensive
     * protection of the NewBytes WooCommerce Connector.
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        // Add security headers
        add_action('admin_init', array(__CLASS__, 'add_security_headers'));
        
        // Validate API requests
        add_filter('nb_validate_api_request', array(__CLASS__, 'validate_api_request'), 10, 2);
        
        // Sanitize input data
        add_filter('nb_sanitize_input', array(__CLASS__, 'sanitize_input_data'), 10, 2);
        
        // Rate limiting
        add_action('wp_ajax_nb_sync_products', array(__CLASS__, 'check_rate_limit'), 1);
        add_action('wp_ajax_nb_update_description_products', array(__CLASS__, 'check_rate_limit'), 1);
        
        // Log security events
        add_action('nb_security_event', array(__CLASS__, 'log_security_event'), 10, 3);
    }
    
    /**
     * Add security headers
     *
     * Adds HTTP security headers to admin pages to prevent XSS,
     * clickjacking, and content type sniffing attacks.
     *
     * @since 1.0.0
     * @return void
     */
    public static function add_security_headers() {
        if (is_admin() && strpos($_SERVER['REQUEST_URI'], 'newbytes') !== false) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    /**
     * Validate API requests
     *
     * Validates incoming API requests for proper authentication,
     * authorization, and data structure.
     *
     * @since 1.0.0
     * @param bool $is_valid Current validation status
     * @param array $request_data Request data to validate
     * @return bool True if request is valid, false otherwise
     */
    public static function validate_api_request($is_valid, $request_data) {
        // Check if user has proper capabilities
        if (!current_user_can('manage_woocommerce')) {
            NB_Logger::warning('Unauthorized API request attempt', array(
                'user_id' => get_current_user_id(),
                'ip' => self::get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ));
            return false;
        }
        
        // Validate request structure
        if (!is_array($request_data)) {
            NB_Logger::warning('Invalid API request structure');
            return false;
        }
        
        // Check for required fields
        $required_fields = array('action', 'nonce');
        foreach ($required_fields as $field) {
            if (!isset($request_data[$field]) || empty($request_data[$field])) {
                NB_Logger::warning('Missing required field in API request: ' . $field);
                return false;
            }
        }
        
        // Validate nonce
        if (!wp_verify_nonce($request_data['nonce'], 'nb_api_nonce')) {
            NB_Logger::warning('Invalid nonce in API request');
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize input data
     *
     * Recursively sanitizes input data based on field types and context.
     * Applies appropriate sanitization functions for different data types.
     *
     * @since 1.0.0
     * @param mixed $sanitized_data Previously sanitized data (unused)
     * @param mixed $raw_data Raw input data to sanitize
     * @return mixed Sanitized data
     */
    public static function sanitize_input_data($sanitized_data, $raw_data) {
        if (!is_array($raw_data)) {
            return sanitize_text_field($raw_data);
        }
        
        $sanitized = array();
        
        foreach ($raw_data as $key => $value) {
            $clean_key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$clean_key] = self::sanitize_input_data(array(), $value);
            } elseif (is_string($value)) {
                // Special handling for different types of data
                switch ($clean_key) {
                    case 'email':
                        $sanitized[$clean_key] = sanitize_email($value);
                        break;
                    case 'url':
                    case 'image_url':
                        $sanitized[$clean_key] = esc_url_raw($value);
                        break;
                    case 'description':
                    case 'content':
                        $sanitized[$clean_key] = wp_kses_post($value);
                        break;
                    case 'price':
                    case 'cost':
                        $sanitized[$clean_key] = floatval($value);
                        break;
                    case 'quantity':
                    case 'stock':
                        $sanitized[$clean_key] = intval($value);
                        break;
                    default:
                        $sanitized[$clean_key] = sanitize_text_field($value);
                        break;
                }
            } else {
                $sanitized[$clean_key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Check rate limiting
     *
     * Implements rate limiting to prevent abuse of API endpoints.
     * Tracks requests per user/IP combination and blocks excessive requests.
     *
     * @since 1.0.0
     * @return void Exits with JSON error if rate limit exceeded
     */
    public static function check_rate_limit() {
        $user_id = get_current_user_id();
        $ip = self::get_client_ip();
        $action = sanitize_text_field($_POST['action'] ?? '');
        
        // Create unique key for rate limiting
        $rate_key = 'nb_rate_limit_' . md5($user_id . '_' . $ip . '_' . $action);
        
        // Get current count
        $current_count = get_transient($rate_key);
        
        if ($current_count === false) {
            // First request in this time window
            set_transient($rate_key, 1, 300); // 5 minutes
        } else {
            // Check if limit exceeded
            $max_requests = self::get_rate_limit_for_action($action);
            
            if ($current_count >= $max_requests) {
                NB_Logger::warning('Rate limit exceeded', array(
                    'user_id' => $user_id,
                    'ip' => $ip,
                    'action' => $action,
                    'count' => $current_count
                ));
                
                wp_send_json_error(array(
                    'message' => __('Demasiadas solicitudes. Intenta de nuevo en unos minutos.', 'newbytes-connector'),
                    'code' => 'rate_limit_exceeded'
                ));
                exit;
            }
            
            // Increment counter
            set_transient($rate_key, $current_count + 1, 300);
        }
    }
    
    /**
     * Get rate limit for specific action
     *
     * Returns the maximum number of requests allowed for a specific action
     * within the rate limiting time window.
     *
     * @since 1.0.0
     * @param string $action Action name to get limit for
     * @return int Maximum requests allowed per time window
     */
    private static function get_rate_limit_for_action($action) {
        $limits = array(
            'nb_sync_products' => 10, // 10 requests per 5 minutes
            'nb_update_description_products' => 5, // 5 requests per 5 minutes
            'nb_delete_products' => 3, // 3 requests per 5 minutes
            'default' => 20
        );
        
        return $limits[$action] ?? $limits['default'];
    }
    
    /**
     * Log security events
     *
     * Records security-related events for monitoring and analysis.
     * Logs to both WordPress error log and database.
     *
     * @since 1.0.0
     * @param string $event_type Type of security event
     * @param string $message Event message
     * @param array $context Additional context data
     * @return void
     */
    public static function log_security_event($event_type, $message, $context = array()) {
        $security_log = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'event_type' => $event_type,
            'message' => $message,
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'context' => $context
        );
        
        // Log to WordPress error log
        error_log('NB Security Event: ' . json_encode($security_log));
        
        // Store in database for analysis
        self::store_security_log($security_log);
    }
    
    /**
     * Store security log in database
     *
     * @since 1.0.0
     * @param array $log_data Array containing security log data
     * @return void
     */
    private static function store_security_log($log_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nb_security_logs';
        
        // Create table if it doesn't exist
        self::create_security_logs_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $log_data['timestamp'],
                'event_type' => $log_data['event_type'],
                'message' => $log_data['message'],
                'user_id' => $log_data['user_id'],
                'ip_address' => $log_data['ip'],
                'user_agent' => $log_data['user_agent'],
                'context' => json_encode($log_data['context'])
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create security logs table
     *
     * Creates the security logs table in the database if it doesn't exist.
     * Used to store security events and audit trails.
     *
     * @since 1.0.0
     * @return void
     */
    private static function create_security_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nb_security_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            event_type varchar(50) NOT NULL,
            message text NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            context longtext,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get client IP address
     *
     * Attempts to get the real client IP address, considering various proxy headers.
     * Falls back to REMOTE_ADDR if no valid IP is found.
     *
     * @since 1.0.0
     * @return string Client IP address
     */
    public static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Validate API credentials
     *
     * Validates API credentials including username, password, and URL format.
     * Checks for HTTPS requirement in production environments.
     *
     * @since 1.0.0
     * @param string $username API username
     * @param string $password API password
     * @param string $api_url API URL
     * @return array Validation result with 'valid' boolean and optional 'error' message
     */
    public static function validate_api_credentials($username, $password, $api_url) {
        // Sanitize inputs
        $username = sanitize_text_field($username);
        $password = sanitize_text_field($password);
        $api_url = esc_url_raw($api_url);
        
        // Validate URL format
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            return array('valid' => false, 'error' => 'URL de API inválida');
        }
        
        // Check for HTTPS in production
        if (!is_ssl() && strpos($api_url, 'https://') !== 0 && !defined('WP_DEBUG') || !WP_DEBUG) {
            return array('valid' => false, 'error' => 'Se requiere HTTPS para conexiones API en producción');
        }
        
        // Validate username format
        if (empty($username) || strlen($username) < 3) {
            return array('valid' => false, 'error' => 'Nombre de usuario debe tener al menos 3 caracteres');
        }
        
        // Validate password strength
        if (empty($password) || strlen($password) < 8) {
            return array('valid' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres');
        }
        
        return array('valid' => true);
    }
    
    /**
     * Encrypt sensitive data
     *
     * Encrypts sensitive data using AES-256-CBC encryption.
     * Falls back to base64 encoding if OpenSSL is not available.
     *
     * @since 1.0.0
     * @param string $data Data to encrypt
     * @return string Encrypted data (base64 encoded)
     */
    public static function encrypt_data($data) {
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($data); // Fallback to base64
        }
        
        $key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     *
     * Decrypts data that was encrypted using encrypt_data method.
     * Falls back to base64 decoding if OpenSSL is not available.
     *
     * @since 1.0.0
     * @param string $encrypted_data Encrypted data to decrypt
     * @return string Decrypted data
     */
    public static function decrypt_data($encrypted_data) {
        if (!function_exists('openssl_decrypt')) {
            return base64_decode($encrypted_data); // Fallback from base64
        }
        
        $key = self::get_encryption_key();
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     *
     * Generates or retrieves the encryption key used for data encryption.
     * Creates a new key if one doesn't exist and stores it in WordPress options.
     *
     * @since 1.0.0
     * @return string SHA256 hashed encryption key
     */
    private static function get_encryption_key() {
        $key = get_option('nb_encryption_key');
        
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_option('nb_encryption_key', $key);
        }
        
        return hash('sha256', $key . SECURE_AUTH_KEY);
    }
}

// Initialize security configuration
NB_Security_Config::init();

/**
 * Security helper functions
 */

/**
 * Validate and sanitize product data
 *
 * Validates required fields and sanitizes product data for security.
 * Checks for proper data types and formats.
 *
 * @since 1.0.0
 * @param array $product_data Product data to validate
 * @return array Validation result with 'valid', 'errors', and 'sanitized_data'
 */
function nb_validate_product_data($product_data) {
    $required_fields = array('name', 'sku', 'price');
    $errors = array();
    
    // Check required fields
    foreach ($required_fields as $field) {
        if (!isset($product_data[$field]) || empty($product_data[$field])) {
            $errors[] = sprintf(__('Campo requerido faltante: %s', 'newbytes-connector'), $field);
        }
    }
    
    // Validate specific fields
    if (isset($product_data['price']) && (!is_numeric($product_data['price']) || $product_data['price'] < 0)) {
        $errors[] = __('El precio debe ser un número positivo', 'newbytes-connector');
    }
    
    if (isset($product_data['stock']) && (!is_numeric($product_data['stock']) || $product_data['stock'] < 0)) {
        $errors[] = __('El stock debe ser un número positivo', 'newbytes-connector');
    }
    
    if (isset($product_data['sku']) && !preg_match('/^[a-zA-Z0-9_-]+$/', $product_data['sku'])) {
        $errors[] = __('El SKU solo puede contener letras, números, guiones y guiones bajos', 'newbytes-connector');
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'sanitized_data' => apply_filters('nb_sanitize_input', array(), $product_data)
    );
}

/**
 * Check if current request is from admin area
 *
 * Determines if the current request is from WordPress admin area,
 * excluding AJAX and cron requests.
 *
 * @since 1.0.0
 * @return bool True if admin request, false otherwise
 */
function nb_is_admin_request() {
    return is_admin() && !wp_doing_ajax() && !wp_doing_cron();
}

/**
 * Check if current request is AJAX
 *
 * Checks if the current request is an AJAX request with valid nonce.
 *
 * @since 1.0.0
 * @return bool True if valid AJAX request, false otherwise
 */
function nb_is_ajax_request() {
    return wp_doing_ajax() && check_ajax_referer('nb_ajax_nonce', 'nonce', false);
}

/**
 * Generate secure nonce for API requests
 *
 * Creates a secure nonce token for API request validation.
 *
 * @since 1.0.0
 * @param string $action Action name for nonce generation
 * @return string Generated nonce token
 */
function nb_generate_api_nonce($action = 'nb_api_request') {
    return wp_create_nonce($action);
}

/**
 * Verify API nonce
 *
 * Verifies that the provided nonce is valid for the given action.
 *
 * @since 1.0.0
 * @param string $nonce Nonce token to verify
 * @param string $action Action name for nonce verification
 * @return bool|int False on failure, 1 or 2 on success
 */
function nb_verify_api_nonce($nonce, $action = 'nb_api_request') {
    return wp_verify_nonce($nonce, $action);
}