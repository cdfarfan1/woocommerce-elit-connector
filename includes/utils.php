<?php
/**
 * Utility functions for NewBytes Connector
 * 
 * @package NewBytes_Connector
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Logging utility class
 * 
 * Provides centralized logging functionality with multiple log levels
 * and integration with WordPress debug logging.
 * 
 * @since 1.0.0
 * @package NewBytes_Connector
 */
/**
 * NewBytes Logger Class
 *
 * Provides centralized logging functionality with different log levels
 * and file-based logging capabilities.
 *
 * @since 1.0.0
 */
class NB_Logger {
    
    /**
     * Path to the log file
     * 
     * @var string|null
     * @since 1.0.0
     */
    private static $log_file = null;
    
    /**
     * Initialize logger
     * 
     * Sets up the log file path in the WordPress uploads directory.
     * 
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        if (null === self::$log_file) {
            $upload_dir = wp_upload_dir();
            self::$log_file = $upload_dir['basedir'] . '/newbytes-connector.log';
        }
    }
    
    /**
     * Log message
     * 
     * Writes a formatted log entry to the log file and optionally
     * to the WordPress debug log.
     * 
     * @since 1.0.0
     * @param string $message The message to log
     * @param string $level   The log level (info, warning, error, debug)
     * @return void
     */
    public static function log($message, $level = 'info') {
        self::init();
        
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] [%s] %s" . PHP_EOL,
            $timestamp,
            strtoupper($level),
            $message
        );
        
        error_log($log_entry, 3, self::$log_file);
        
        // Also log to WordPress debug log if enabled
        if (WP_DEBUG_LOG) {
            error_log('NewBytes Connector: ' . $message);
        }
    }
    
    /**
     * Log error message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function error($message) {
        self::log($message, 'error');
    }
    
    /**
     * Log warning message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function warning($message) {
        self::log($message, 'warning');
    }
    
    /**
     * Log info message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function info($message) {
        self::log($message, 'info');
    }
    
    /**
     * Log debug message
     *
     * @since 1.0.0
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    public static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log($message, 'debug');
        }
    }
}

/**
 * Cache utility class
 * 
 * Provides a wrapper around WordPress object cache with
 * a dedicated cache group for the plugin.
 * 
 * @since 1.0.0
 * @package NewBytes_Connector
 */
class NB_Cache {
    
    /**
     * Cache group identifier
     * 
     * @var string
     * @since 1.0.0
     */
    private static $cache_group = 'newbytes_connector';
    
    /**
     * Get cached data
     * 
     * Retrieves data from the WordPress object cache.
     * 
     * @since 1.0.0
     * @param string $key The cache key
     * @return mixed      The cached data or false if not found
     */
    public static function get($key) {
        return wp_cache_get($key, self::$cache_group);
    }
    
    /**
     * Set cached data
     * 
     * Stores data in the WordPress object cache with expiration.
     * 
     * @since 1.0.0
     * @param string $key        The cache key
     * @param mixed  $data       The data to cache
     * @param int    $expiration Expiration time in seconds (default: 3600)
     * @return bool              True on success, false on failure
     */
    public static function set($key, $data, $expiration = 3600) {
        return wp_cache_set($key, $data, self::$cache_group, $expiration);
    }
    
    /**
     * Delete cached data
     * 
     * Removes data from the WordPress object cache.
     * 
     * @since 1.0.0
     * @param string $key The cache key to delete
     * @return bool       True on success, false on failure
     */
    public static function delete($key) {
        return wp_cache_delete($key, self::$cache_group);
    }
    
    /**
     * Flush all cache
     * 
     * Clears all cached data from the WordPress object cache.
     * 
     * @since 1.0.0
     * @return bool True on success, false on failure
     */
    public static function flush() {
        return wp_cache_flush();
    }
}

/**
 * Get authentication token with improved error handling and caching
 * 
 * Authenticates with the NewBytes API and retrieves an access token.
 * Implements caching to avoid unnecessary API calls.
 * 
 * @since 1.0.0
 * @return string|null The authentication token or null on failure
 */
function nb_get_token() {
    try {
        // Check cache first
        $cached_token = NB_Cache::get('auth_token');
        if ($cached_token) {
            return $cached_token;
        }
        
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'NewBytes-Connector/' . (defined('NB_PLUGIN_VERSION') ? NB_PLUGIN_VERSION : '1.0.0')
            ),
            'body' => json_encode(array(
                'user' => get_option('nb_user'),
                'password' => get_option('nb_password'),
                'mode' => 'wp-extension',
                'domain' => home_url()
            )),
            'timeout' => 30,
            'blocking' => true,
        );

        $api_url = defined('API_URL_NB') ? API_URL_NB : (defined('NB_API_URL') ? NB_API_URL : 'https://api.nb.com.ar/v1');
        $response = wp_remote_post($api_url . '/auth/login', $args);

        if (is_wp_error($response)) {
            $error_msg = 'Error en la solicitud de token: ' . $response->get_error_message();
            NB_Logger::error($error_msg);
            nb_show_error_message($error_msg);
            return null;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            $error_msg = 'Error HTTP ' . $response_code . ' al solicitar token';
            NB_Logger::error($error_msg);
            nb_show_error_message($error_msg);
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_msg = 'Error al decodificar JSON de la solicitud de token: ' . json_last_error_msg();
            NB_Logger::error($error_msg);
            nb_show_error_message($error_msg);
            return null;
        }

        if (isset($json['token'])) {
            // Cache token for 30 minutes
            NB_Cache::set('auth_token', $json['token'], 1800);
            NB_Logger::info('Token obtenido exitosamente');
            return $json['token'];
        }

        $error_msg = 'Token no encontrado en la respuesta: ' . json_encode($json);
        NB_Logger::error($error_msg);
        nb_show_error_message($error_msg);
        return null;
    } catch (Exception $e) {
        $error_msg = 'Excepción al obtener token: ' . $e->getMessage();
        NB_Logger::error($error_msg);
        echo esc_html($error_msg);
        return null;
    }
}

/**
 * Output JSON response with proper headers
 * 
 * Outputs a JSON response with proper content-type headers and terminates execution.
 * 
 * @since 1.0.0
 * @param mixed $data The data to output as JSON
 * @return void
 */
function output_response($data) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    wp_die();
}

/**
 * Show error message with improved styling and logging
 * 
 * Displays an error message in the WordPress admin with proper styling
 * and logs the error for debugging purposes.
 * 
 * @since 1.0.0
 * @param string $error The error message to display
 * @return void
 */
function nb_show_error_message($error) {
    NB_Logger::error($error);
    echo '<div class="notice notice-error"><p style="color: #d63638; font-weight: 500;">' . esc_html($error) . '</p></div>';
}

/**
 * Show success message
 * 
 * Displays a success message in the WordPress admin with proper styling
 * and logs the message for reference.
 * 
 * @since 1.0.0
 * @param string $message The success message to display
 * @return void
 */
function nb_show_success_message($message) {
    NB_Logger::info($message);
    echo '<div class="notice notice-success"><p style="color: #00a32a; font-weight: 500;">' . esc_html($message) . '</p></div>';
}

/**
 * Show last update with improved formatting
 * 
 * Displays the last synchronization update time with proper timezone
 * adjustment and JavaScript-based DOM manipulation.
 * 
 * @since 1.0.0
 * @return void
 */
function nb_show_last_update() {
    $last_update_raw = get_option('elit_last_update', get_option('nb_last_update'));
    
    if (empty($last_update_raw)) {
        $last_update = '--';
    } else {
        $timestamp = strtotime($last_update_raw . '-3 hours');
        $last_update = $timestamp ? date('d/m/Y H:i', $timestamp) : '--';
    }
    
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var element = document.getElementById("last_update");
        if (element) {
            element.innerText = "' . esc_js($last_update) . '";
        }
    });
    </script>';
}

/**
 * ELIT version of show last update
 */
function elit_show_last_update() {
    return nb_show_last_update();
}

/**
 * Sanitize and validate API credentials
 * 
 * Validates user credentials for API authentication,
 * checking for required fields and minimum lengths.
 * 
 * @since 1.0.0
 * @param string $user     The username to validate
 * @param string $password The password to validate
 * @return array           Array of validation errors (empty if valid)
 */
function nb_validate_credentials($user, $password) {
    $errors = array();
    
    if (empty($user)) {
        $errors[] = __('El usuario es requerido', 'newbytes-connector');
    }
    
    if (empty($password)) {
        $errors[] = __('La contraseña es requerida', 'newbytes-connector');
    }
    
    if (strlen($user) < 3) {
        $errors[] = __('El usuario debe tener al menos 3 caracteres', 'newbytes-connector');
    }
    
    if (strlen($password) < 6) {
        $errors[] = __('La contraseña debe tener al menos 6 caracteres', 'newbytes-connector');
    }
    
    return $errors;
}

/**
 * Format file size
 * 
 * Converts bytes to human-readable format (B, KB, MB, GB, TB).
 * 
 * @since 1.0.0
 * @param int $bytes The size in bytes
 * @return string    Formatted file size string
 */
function nb_format_file_size($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get system info for debugging
 * 
 * Collects system information including PHP version, WordPress version,
 * WooCommerce version, and various PHP configuration settings.
 * 
 * @since 1.0.0
 * @return array Array of system information
 */
function nb_get_system_info() {
    return array(
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'wc_version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
        'plugin_version' => defined('NB_PLUGIN_VERSION') ? NB_PLUGIN_VERSION : '1.0.0',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    );
}
