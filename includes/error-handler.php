<?php
/**
 * NewBytes WooCommerce Connector - Error Handler and Logging System
 * 
 * Este archivo contiene la clase NB_Error_Handler que maneja todos los errores
 * del plugin, incluyendo errores PHP, excepciones, errores fatales y errores AJAX.
 * Proporciona logging avanzado tanto a archivos como a base de datos.
 * 
 * @package NewBytes_WooCommerce_Connector
 * @subpackage Error_Handling
 * @version 1.0.0
 * @since 1.0.0
 * @author NewBytes Development Team
 * @link https://wa.me/message/XXXXXXX
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Enhanced Error Handler Class
 * 
 * Maneja todos los tipos de errores del plugin NewBytes WooCommerce Connector.
 * Implementa un sistema de logging robusto con rotación de archivos y
 * almacenamiento en base de datos para análisis posterior.
 * 
 * @class NB_Error_Handler
 * @package NewBytes_WooCommerce_Connector
 * @subpackage Error_Handling
 * @since 1.0.0
 * 
 * @property string $error_log_file Ruta del archivo de log de errores
 * @property int $max_log_size Tamaño máximo del archivo de log (10MB)
 * @property int $max_log_files Número máximo de archivos de log a mantener
 */
class NB_Error_Handler {
    
    private static $instance = null;
    private $error_log_file;
    private $max_log_size = 10485760; // 10MB
    private $max_log_files = 5;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize error handler
     */
    private function init() {
        // Set up log file path
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/newbytes/logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $this->error_log_file = $log_dir . '/newbytes-error.log';
        
        // Register error handlers
        add_action('wp_ajax_nb_handle_error', array($this, 'handle_ajax_error'));
        add_action('wp_ajax_nopriv_nb_handle_error', array($this, 'handle_ajax_error'));
        
        // Hook into WordPress error handling
        add_action('wp_die_handler', array($this, 'custom_wp_die_handler'));
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function(array($this, 'handle_fatal_error'));
        
        // Set custom error handler for PHP errors
        set_error_handler(array($this, 'handle_php_error'));
        
        // Set custom exception handler
        set_exception_handler(array($this, 'handle_exception'));
    }
    
    /**
     * Handle AJAX errors
     */
    public function handle_ajax_error() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nb_error_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $error_data = array(
            'message' => sanitize_text_field($_POST['message'] ?? ''),
            'file' => sanitize_text_field($_POST['file'] ?? ''),
            'line' => intval($_POST['line'] ?? 0),
            'stack' => sanitize_textarea_field($_POST['stack'] ?? ''),
            'url' => esc_url_raw($_POST['url'] ?? ''),
            'user_agent' => sanitize_text_field($_POST['user_agent'] ?? '')
        );
        
        $this->log_error('javascript', $error_data['message'], $error_data);
        
        wp_send_json_success('Error logged successfully');
    }
    
    /**
     * Handle PHP errors
     */
    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $error_types = array(
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        );
        
        $error_type = $error_types[$errno] ?? 'Unknown Error';
        
        // Only log NewBytes related errors or critical errors
        if (strpos($errfile, 'newbytes') !== false || in_array($errno, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
            $context = array(
                'file' => $errfile,
                'line' => $errline,
                'type' => $error_type,
                'errno' => $errno
            );
            
            $this->log_error('php', $errstr, $context);
        }
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle exceptions
     */
    public function handle_exception($exception) {
        $context = array(
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode()
        );
        
        $this->log_error('exception', $exception->getMessage(), $context);
        
        // If this is a NewBytes related exception, show user-friendly message
        if (strpos($exception->getFile(), 'newbytes') !== false) {
            if (wp_doing_ajax()) {
                wp_send_json_error(array(
                    'message' => __('Ha ocurrido un error interno. Por favor, contacta al administrador.', 'newbytes-connector'),
                    'code' => 'internal_error'
                ));
            } else {
                wp_die(
                    __('Ha ocurrido un error interno en el plugin NewBytes. Por favor, revisa los logs o contacta al soporte técnico.', 'newbytes-connector'),
                    __('Error del Plugin', 'newbytes-connector'),
                    array('back_link' => true)
                );
            }
        }
    }
    
    /**
     * Handle fatal errors
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
            // Only log if it's related to NewBytes
            if (strpos($error['file'], 'newbytes') !== false) {
                $context = array(
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => 'Fatal Error'
                );
                
                $this->log_error('fatal', $error['message'], $context);
            }
        }
    }
    
    /**
     * Custom WordPress die handler
     */
    public function custom_wp_die_handler($function) {
        return function($message, $title = '', $args = array()) use ($function) {
            // Log the wp_die call if it's related to NewBytes
            if (is_string($message) && (strpos($message, 'newbytes') !== false || strpos($message, 'NewBytes') !== false)) {
                $context = array(
                    'title' => $title,
                    'args' => $args,
                    'backtrace' => wp_debug_backtrace_summary()
                );
                
                $this->log_error('wp_die', $message, $context);
            }
            
            // Call the original handler
            return call_user_func($function, $message, $title, $args);
        };
    }
    
    /**
     * Registra un error en el archivo de log y la base de datos
     * 
     * @since 1.0.0
     * @param string $type Tipo de error (php, javascript, fatal, exception, etc.)
     * @param string $message Mensaje del error
     * @param array $context Contexto adicional del error (archivo, línea, stack trace, etc.)
     * @return void
     */
    public function log_error($type, $message, $context = array()) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip' => $this->get_client_ip(),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        );
        
        // Log to file
        $this->write_to_log_file($log_entry);
        
        // Log to database
        $this->write_to_database($log_entry);
        
        // Send email notification for critical errors
        if (in_array($type, array('fatal', 'exception', 'php')) && get_option('nb_email_critical_errors', false)) {
            $this->send_error_notification($log_entry);
        }
    }
    
    /**
     * Escribe el error en el archivo de log
     * 
     * @since 1.0.0
     * @param array $log_entry Entrada de log con toda la información del error
     * @return void
     */
    private function write_to_log_file($log_entry) {
        // Rotate log file if it's too large
        $this->rotate_log_file();
        
        $log_line = sprintf(
            "[%s] %s: %s | Context: %s | User: %d | IP: %s | Memory: %s\n",
            $log_entry['timestamp'],
            strtoupper($log_entry['type']),
            $log_entry['message'],
            json_encode($log_entry['context']),
            $log_entry['user_id'],
            $log_entry['ip'],
            size_format($log_entry['memory_usage'])
        );
        
        error_log($log_line, 3, $this->error_log_file);
    }
    
    /**
     * Escribe el error en la base de datos
     * 
     * @since 1.0.0
     * @param array $log_entry Entrada de log con toda la información del error
     * @return void
     */
    private function write_to_database($log_entry) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nb_error_logs';
        
        // Create table if it doesn't exist
        $this->create_error_logs_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $log_entry['timestamp'],
                'error_type' => $log_entry['type'],
                'message' => $log_entry['message'],
                'context' => json_encode($log_entry['context']),
                'user_id' => $log_entry['user_id'],
                'ip_address' => $log_entry['ip'],
                'url' => $log_entry['url'],
                'user_agent' => $log_entry['user_agent'],
                'memory_usage' => $log_entry['memory_usage'],
                'memory_peak' => $log_entry['memory_peak']
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d')
        );
    }
    
    /**
     * Crea la tabla de logs de errores en la base de datos
     * 
     * @since 1.0.0
     * @return void
     */
    private function create_error_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nb_error_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            error_type varchar(50) NOT NULL,
            message text NOT NULL,
            context longtext,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            url varchar(500),
            user_agent text,
            memory_usage bigint(20) DEFAULT 0,
            memory_peak bigint(20) DEFAULT 0,
            resolved tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY error_type (error_type),
            KEY user_id (user_id),
            KEY resolved (resolved)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Rota el archivo de log cuando excede el tamaño máximo
     * 
     * @since 1.0.0
     * @return void
     */
    private function rotate_log_file() {
        if (!file_exists($this->error_log_file)) {
            return;
        }
        
        if (filesize($this->error_log_file) > $this->max_log_size) {
            // Move current log to backup
            $backup_file = $this->error_log_file . '.' . date('Y-m-d-H-i-s');
            rename($this->error_log_file, $backup_file);
            
            // Clean up old log files
            $this->cleanup_old_logs();
        }
    }
    
    /**
     * Clean up old log files
     */
    private function cleanup_old_logs() {
        $log_dir = dirname($this->error_log_file);
        $log_files = glob($log_dir . '/newbytes-error.log.*');
        
        if (count($log_files) > $this->max_log_files) {
            // Sort by modification time
            usort($log_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $files_to_remove = array_slice($log_files, 0, count($log_files) - $this->max_log_files);
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Send error notification email
     */
    private function send_error_notification($log_entry) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] Error Crítico en NewBytes Plugin', 'newbytes-connector'), $site_name);
        
        $message = sprintf(
            __('Se ha producido un error crítico en el plugin NewBytes:\n\nTipo: %s\nMensaje: %s\nFecha: %s\nUsuario: %d\nIP: %s\nURL: %s\n\nContexto:\n%s', 'newbytes-connector'),
            $log_entry['type'],
            $log_entry['message'],
            $log_entry['timestamp'],
            $log_entry['user_id'],
            $log_entry['ip'],
            $log_entry['url'],
            print_r($log_entry['context'], true)
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
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
     * Get error statistics
     */
    public function get_error_stats($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nb_error_logs';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = array(
            'total_errors' => 0,
            'by_type' => array(),
            'by_day' => array(),
            'recent_errors' => array()
        );
        
        // Total errors
        $stats['total_errors'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE timestamp >= %s",
                $date_from
            )
        );
        
        // Errors by type
        $by_type = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT error_type, COUNT(*) as count 
                 FROM $table_name 
                 WHERE timestamp >= %s 
                 GROUP BY error_type 
                 ORDER BY count DESC",
                $date_from
            )
        );
        
        foreach ($by_type as $row) {
            $stats['by_type'][$row->error_type] = $row->count;
        }
        
        // Errors by day
        $by_day = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(timestamp) as date, COUNT(*) as count 
                 FROM $table_name 
                 WHERE timestamp >= %s 
                 GROUP BY DATE(timestamp) 
                 ORDER BY date DESC",
                $date_from
            )
        );
        
        foreach ($by_day as $row) {
            $stats['by_day'][$row->date] = $row->count;
        }
        
        // Recent errors
        $stats['recent_errors'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE timestamp >= %s 
                 ORDER BY timestamp DESC 
                 LIMIT 10",
                $date_from
            )
        );
        
        return $stats;
    }
    
    /**
     * Clear old error logs
     */
    public function clear_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nb_error_logs';
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < %s",
                $date_threshold
            )
        );
        
        return $deleted;
    }
}

// Initialize error handler
NB_Error_Handler::get_instance();

/**
 * Helper functions for error handling
 */

/**
 * Log a custom error
 */
function nb_log_error($type, $message, $context = array()) {
    $error_handler = NB_Error_Handler::get_instance();
    $error_handler->log_error($type, $message, $context);
}

/**
 * Handle API errors
 */
function nb_handle_api_error($response, $request_url, $request_data = array()) {
    if (is_wp_error($response)) {
        $context = array(
            'url' => $request_url,
            'request_data' => $request_data,
            'error_code' => $response->get_error_code(),
            'error_data' => $response->get_error_data()
        );
        
        nb_log_error('api', $response->get_error_message(), $context);
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code >= 400) {
        $context = array(
            'url' => $request_url,
            'request_data' => $request_data,
            'response_code' => $response_code,
            'response_body' => wp_remote_retrieve_body($response)
        );
        
        nb_log_error('api', 'HTTP Error: ' . $response_code, $context);
        return false;
    }
    
    return true;
}

/**
 * Validate and handle database errors
 */
function nb_handle_db_error($wpdb_error, $query = '', $context = array()) {
    if (!empty($wpdb_error)) {
        $error_context = array_merge($context, array(
            'query' => $query,
            'mysql_error' => $wpdb_error
        ));
        
        nb_log_error('database', 'Database Error: ' . $wpdb_error, $error_context);
        return false;
    }
    
    return true;
}

/**
 * Handle product sync errors
 */
function nb_handle_sync_error($error_message, $product_data = array(), $step = '') {
    $context = array(
        'product_data' => $product_data,
        'sync_step' => $step,
        'memory_usage' => memory_get_usage(true)
    );
    
    nb_log_error('sync', $error_message, $context);
}

/**
 * Get formatted error message for users
 */
function nb_get_user_friendly_error($error_type, $technical_message = '') {
    $user_messages = array(
        'api' => __('Error de conexión con el servidor. Por favor, intenta de nuevo más tarde.', 'newbytes-connector'),
        'database' => __('Error de base de datos. Por favor, contacta al administrador.', 'newbytes-connector'),
        'sync' => __('Error durante la sincronización de productos. Revisa la configuración.', 'newbytes-connector'),
        'validation' => __('Los datos proporcionados no son válidos. Por favor, revisa la información.', 'newbytes-connector'),
        'permission' => __('No tienes permisos para realizar esta acción.', 'newbytes-connector'),
        'rate_limit' => __('Demasiadas solicitudes. Por favor, espera un momento antes de intentar de nuevo.', 'newbytes-connector')
    );
    
    return $user_messages[$error_type] ?? __('Ha ocurrido un error inesperado.', 'newbytes-connector');
}