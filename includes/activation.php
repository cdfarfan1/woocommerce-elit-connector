<?php
/**
 * NewBytes WooCommerce Connector - Activation/Deactivation Functions
 * 
 * @package NewBytes_WooCommerce_Connector
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Plugin activation function with enhanced security checks
 *
 * Handles the complete plugin activation process including system requirements check,
 * database table creation, default options setup, and cron scheduling.
 *
 * @since 1.0.0
 * @throws Exception If activation fails
 * @return void
 */
function nb_activation() {
    try {
        // Log activation attempt
        error_log('NewBytes Plugin: Iniciando activación del plugin');
        
        // Check system requirements
        $requirements_check = nb_check_system_requirements();
        if (!$requirements_check['meets_requirements']) {
            $error_msg = 'Requisitos del sistema no cumplidos: ' . implode(', ', $requirements_check['errors']);
            error_log('NewBytes Plugin Error: ' . $error_msg);
            wp_die($error_msg, 'Error de Activación', array('back_link' => true));
        }
        
        // Check user capabilities
        if (!current_user_can('activate_plugins')) {
            error_log('NewBytes Plugin Error: Usuario sin permisos intentó activar el plugin');
            wp_die(__('No tienes permisos para activar plugins.', 'newbytes-connector'), 'Error de Permisos', array('back_link' => true));
        }
        
        // Create database tables if needed
        nb_create_database_tables();
        
        // Set default options with validation
        nb_set_default_options();
        
        // Schedule cron events
        nb_schedule_cron_events();
        
        // Create upload directories with proper permissions
        nb_create_upload_directories();
        
        // Generate encryption key for sensitive data
        nb_generate_encryption_key();
        
        // Set plugin version
        update_option('nb_plugin_version', NB_VERSION);
        
        // Log successful activation
        error_log('NewBytes Plugin: Activación completada exitosamente');
        
        // Trigger activation hook for extensions
        do_action('nb_plugin_activated');
        
    } catch (Exception $e) {
        $error_msg = 'Error durante la activación del plugin: ' . $e->getMessage();
        error_log('NewBytes Plugin Error: ' . $error_msg);
        wp_die($error_msg, 'Error de Activación', array('back_link' => true));
    }
}

/**
 * Plugin deactivation function with cleanup
 *
 * Handles plugin deactivation by clearing cron events, cache, and triggering
 * deactivation hooks for extensions.
 *
 * @since 1.0.0
 * @return void
 */
function nb_deactivation() {
    try {
        // Log deactivation attempt
        error_log('NewBytes Plugin: Iniciando desactivación del plugin');
        
        // Check user capabilities
        if (!current_user_can('deactivate_plugins')) {
            error_log('NewBytes Plugin Error: Usuario sin permisos intentó desactivar el plugin');
            return;
        }
        
        // Clear scheduled cron events
        nb_clear_cron_events();
        
        // Clear transients and cache
        nb_clear_plugin_cache();
        
        // Trigger deactivation hook for extensions
        do_action('nb_plugin_deactivated');
        
        // Log successful deactivation
        error_log('NewBytes Plugin: Desactivación completada exitosamente');
        
    } catch (Exception $e) {
        error_log('NewBytes Plugin Error en desactivación: ' . $e->getMessage());
    }
}

/**
 * Check system requirements
 *
 * Validates that the server environment meets all plugin requirements including
 * PHP version, WordPress version, WooCommerce, extensions, and permissions.
 *
 * @since 1.0.0
 * @return array Array with 'meets_requirements' boolean and 'errors' array
 */
function nb_check_system_requirements() {
    $errors = array();
    $meets_requirements = true;
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = sprintf(__('PHP %s o superior requerido. Versión actual: %s', 'newbytes-connector'), '7.4', PHP_VERSION);
        $meets_requirements = false;
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '5.0', '<')) {
        $errors[] = sprintf(__('WordPress %s o superior requerido. Versión actual: %s', 'newbytes-connector'), '5.0', $wp_version);
        $meets_requirements = false;
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        $errors[] = __('WooCommerce debe estar instalado y activado', 'newbytes-connector');
        $meets_requirements = false;
    } else {
        // Check WooCommerce version
        $wc_version = defined('WC_VERSION') ? WC_VERSION : '0.0.0';
        if (version_compare($wc_version, '4.0', '<')) {
            $errors[] = sprintf(__('WooCommerce %s o superior requerido. Versión actual: %s', 'newbytes-connector'), '4.0', $wc_version);
            $meets_requirements = false;
        }
    }
    
    // Check required PHP extensions
    $required_extensions = array('curl', 'json', 'mbstring');
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(__('Extensión PHP requerida: %s', 'newbytes-connector'), $extension);
            $meets_requirements = false;
        }
    }
    
    // Check memory limit
    $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
    $required_memory = 128 * 1024 * 1024; // 128MB
    if ($memory_limit < $required_memory) {
        $errors[] = sprintf(__('Memoria PHP insuficiente. Requerido: %s, Actual: %s', 'newbytes-connector'), 
            size_format($required_memory), 
            size_format($memory_limit)
        );
        $meets_requirements = false;
    }
    
    // Check file permissions
    $upload_dir = wp_upload_dir();
    if (!wp_is_writable($upload_dir['basedir'])) {
        $errors[] = __('Directorio de uploads no escribible', 'newbytes-connector');
        $meets_requirements = false;
    }
    
    return array(
        'meets_requirements' => $meets_requirements,
        'errors' => $errors
    );
}

/**
 * Create database tables
 *
 * Creates the necessary database tables for security logs and sync logs
 * with proper indexes and charset collation.
 *
 * @since 1.0.0
 * @global wpdb $wpdb WordPress database abstraction object
 * @return void
 */
/**
 * Create database tables
 *
 * Creates the necessary database tables for security logs and sync logs
 * with proper indexes and charset collation.
 *
 * @since 1.0.0
 * @global wpdb $wpdb WordPress database abstraction object
 * @return void
 */
function nb_create_database_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Security logs table
    $security_logs_table = $wpdb->prefix . 'nb_security_logs';
    $sql_security = "CREATE TABLE IF NOT EXISTS $security_logs_table (
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
    
    // Sync logs table
    $sync_logs_table = $wpdb->prefix . 'nb_sync_logs';
    $sql_sync = "CREATE TABLE IF NOT EXISTS $sync_logs_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        timestamp datetime NOT NULL,
        sync_type varchar(50) NOT NULL,
        status varchar(20) NOT NULL,
        products_processed int(11) DEFAULT 0,
        products_created int(11) DEFAULT 0,
        products_updated int(11) DEFAULT 0,
        products_deleted int(11) DEFAULT 0,
        duration float DEFAULT 0,
        memory_usage bigint(20) DEFAULT 0,
        error_message text,
        PRIMARY KEY (id),
        KEY timestamp (timestamp),
        KEY sync_type (sync_type),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_security);
    dbDelta($sql_sync);
}

/**
 * Set default options with validation
 *
 * Sets up default plugin options if they don't already exist.
 * Includes sync intervals, markup percentages, and security settings.
 *
 * @since 1.0.0
 * @return void
 */
/**
 * Set default options with validation
 *
 * Sets up default plugin options if they don't already exist.
 * Includes sync intervals, markup percentages, and security settings.
 *
 * @since 1.0.0
 * @return void
 */
function nb_set_default_options() {
    $default_options = array(
        'nb_sync_interval' => '3600', // 1 hour
        'nb_markup_percentage' => '35', // 35%
        'nb_max_products_per_batch' => '50',
        'nb_api_timeout' => '30',
        'nb_enable_logging' => '1',
        'nb_log_level' => 'info',
        'nb_cache_duration' => '300', // 5 minutes
        'nb_sku_prefix' => 'NB',
        'nb_enable_rate_limiting' => '1',
        'nb_max_api_requests_per_minute' => '60'
    );
    
    foreach ($default_options as $option_name => $default_value) {
        if (!get_option($option_name)) {
            add_option($option_name, $default_value);
        }
    }
}

/**
 * Schedule cron events
 *
 * Sets up WordPress cron events for automated tasks including
 * product sync, cleanup, and security audits.
 *
 * @since 1.0.0
 * @return void
 */
/**
 * Schedule cron events
 *
 * Sets up WordPress cron events for automated tasks including
 * product sync, cleanup, and security audits.
 *
 * @since 1.0.0
 * @return void
 */
function nb_schedule_cron_events() {
    // Main sync event
    if (!wp_next_scheduled('nb_cron_sync_event')) {
        wp_schedule_event(time(), 'hourly', 'nb_cron_sync_event');
    }
    
    // Cleanup event (daily)
    if (!wp_next_scheduled('nb_cron_cleanup_event')) {
        wp_schedule_event(time(), 'daily', 'nb_cron_cleanup_event');
    }
    
    // Security audit event (weekly)
    if (!wp_next_scheduled('nb_cron_security_audit_event')) {
        wp_schedule_event(time(), 'weekly', 'nb_cron_security_audit_event');
    }
}

/**
 * Clear cron events
 *
 * Removes all scheduled cron events created by the plugin.
 * Called during plugin deactivation.
 *
 * @since 1.0.0
 * @return void
 */
/**
 * Clear cron events
 *
 * Removes all scheduled cron events created by the plugin.
 * Called during plugin deactivation.
 *
 * @since 1.0.0
 * @return void
 */
function nb_clear_cron_events() {
    $cron_events = array(
        'nb_cron_sync_event',
        'nb_cron_cleanup_event',
        'nb_cron_security_audit_event'
    );
    
    foreach ($cron_events as $event) {
        $timestamp = wp_next_scheduled($event);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $event);
        }
    }
}

/**
 * Create upload directories
 *
 * Creates necessary upload directories with proper security measures
 * including .htaccess files and index.php files to prevent direct access.
 *
 * @since 1.0.0
 * @return void
 */
/**
 * Create upload directories
 *
 * Creates necessary upload directories with proper security measures
 * including .htaccess files and index.php files to prevent direct access.
 *
 * @since 1.0.0
 * @return void
 */
function nb_create_upload_directories() {
    $upload_dir = wp_upload_dir();
    $nb_dir = $upload_dir['basedir'] . '/newbytes';
    
    if (!file_exists($nb_dir)) {
        wp_mkdir_p($nb_dir);
        
        // Create .htaccess for security
        $htaccess_content = "# NewBytes Security\nOrder deny,allow\nDeny from all\n<Files ~ \"\\.(log|txt)$\">\nAllow from all\n</Files>";
        file_put_contents($nb_dir . '/.htaccess', $htaccess_content);
        
        // Create index.php to prevent directory listing
        file_put_contents($nb_dir . '/index.php', '<?php // Silence is golden');
    }
    
    // Create logs subdirectory
    $logs_dir = $nb_dir . '/logs';
    if (!file_exists($logs_dir)) {
        wp_mkdir_p($logs_dir);
        file_put_contents($logs_dir . '/index.php', '<?php // Silence is golden');
    }
}

/**
 * Generate encryption key
 *
 * Generates a secure encryption key for sensitive data encryption
 * if one doesn't already exist.
 *
 * @since 1.0.0
 * @return void
 */
/**
 * Generate encryption key
 *
 * Generates a secure encryption key for sensitive data encryption
 * if one doesn't already exist.
 *
 * @since 1.0.0
 * @return void
 */
function nb_generate_encryption_key() {
    if (!get_option('nb_encryption_key')) {
        $key = wp_generate_password(32, false);
        add_option('nb_encryption_key', $key);
    }
}

/**
 * Clear plugin cache and transients
 *
 * Removes all plugin-related transients and cache data from the database.
 * Also flushes object cache if available.
 *
 * @since 1.0.0
 * @return void
 */
/**
 * Clear plugin cache and transients
 *
 * Removes all plugin-related transients and cache data from the database.
 * Also flushes object cache if available.
 *
 * @since 1.0.0
 * @return void
 */
function nb_clear_plugin_cache() {
    // Clear all NewBytes transients
    global $wpdb;
    
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_nb_%' 
         OR option_name LIKE '_transient_timeout_nb_%'"
    );
    
    // Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}