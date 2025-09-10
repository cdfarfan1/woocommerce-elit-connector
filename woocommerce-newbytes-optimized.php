<?php
/*
Plugin Name: Conector NewBytes Pro
Description: Sincroniza los productos del catálogo de NewBytes con WooCommerce de forma optimizada y segura.
Author: NewBytes
Author URI: https://nb.com.ar
Version: 1.0.0
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 8.5
Text Domain: newbytes-connector
Domain Path: /languages
Network: false
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Define plugin constants
define('NB_PLUGIN_VERSION', '1.0.0');
define('NB_PLUGIN_FILE', __FILE__);
define('NB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NB_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NB_API_URL', 'https://api.nb.com.ar/v1');
define('NB_TEXT_DOMAIN', 'newbytes-connector');

/**
 * Main Plugin Class
 * 
 * Handles the initialization and management of the NewBytes Connector plugin.
 * Implements singleton pattern to ensure only one instance exists.
 * 
 * @since 1.0.0
 * @package NewBytes_Connector
 */
class NewBytes_Connector {
    
    /**
     * Singleton instance
     * 
     * @var NewBytes_Connector|null
     * @since 1.0.0
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * Returns the single instance of the plugin class, creating it if necessary.
     * 
     * @since 1.0.0
     * @return NewBytes_Connector The plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * Private constructor to prevent direct instantiation.
     * Use get_instance() to get the singleton instance.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     * 
     * Sets up the plugin by checking requirements, loading files,
     * initializing hooks, and setting up internationalization.
     * 
     * @since 1.0.0
     * @return void
     */
    private function init() {
        // Check requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Load plugin files
        $this->load_files();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Load textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Check plugin requirements
     * 
     * Verifies that all necessary dependencies are available,
     * including WooCommerce and minimum PHP version.
     * 
     * @since 1.0.0
     * @return bool True if requirements are met, false otherwise
     */
    private function check_requirements() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Load plugin files
     * 
     * Includes all necessary plugin files and logs any missing files.
     * Files are loaded in a specific order to ensure proper dependencies.
     * 
     * @since 1.0.0
     * @return void
     */
    private function load_files() {
        $files = array(
            'includes/activation.php',
            'includes/admin-hooks.php',
            'includes/cron-hooks.php',
            'includes/rest-api.php',
            'includes/utils.php',
            'includes/price-calculator.php',
            'includes/modals.php',
            'includes/product-sync.php',
            'includes/product-delete.php',
            'includes/settings.php',
            'includes/sync-callback.php'
        );
        
        foreach ($files as $file) {
            $file_path = NB_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log('NewBytes Connector: Missing file ' . $file);
            }
        }
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * Sets up all WordPress hooks including admin, AJAX, cron,
     * REST API, and activation/deactivation hooks.
     * 
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', 'nb_menu');
            add_action('admin_init', 'nb_register_settings');
            add_action('admin_enqueue_scripts', 'enqueue_fontawesome');
            add_filter('plugin_action_links_' . NB_PLUGIN_BASENAME, 'nb_plugin_action_links');
        }
        
        // AJAX hooks
        add_action('wp_ajax_nb_update_description_products', 'nb_update_description_products');
        add_action('wp_ajax_nb_delete_products', 'nb_delete_products');
        add_action('admin_post_nb_delete_products', 'nb_delete_products');
        
        // Cron hooks
        add_action('update_option_nb_sync_interval', 'nb_update_cron_schedule', 10, 2);
        add_filter('cron_schedules', 'nb_cron_interval');
        add_action('nb_cron_sync_event', 'nb_callback');
        
        // REST API hooks
        add_action('rest_api_init', 'nb_sync_catalog');
        
        // Activation/Deactivation hooks
        register_activation_hook(NB_PLUGIN_FILE, 'nb_activation');
        register_deactivation_hook(NB_PLUGIN_FILE, 'nb_deactivation');
    }
    
    /**
     * Load plugin textdomain
     * 
     * Loads the plugin's translation files for internationalization support.
     * 
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            NB_TEXT_DOMAIN,
            false,
            dirname(NB_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * WooCommerce missing notice
     * 
     * Displays an admin notice when WooCommerce is not active.
     * 
     * @since 1.0.0
     * @return void
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('NewBytes Connector requiere WooCommerce para funcionar.', NB_TEXT_DOMAIN);
        echo '</p></div>';
    }
    
    /**
     * PHP version notice
     * 
     * Displays an admin notice when PHP version is below requirements.
     * 
     * @since 1.0.0
     * @return void
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('NewBytes Connector requiere PHP 7.4 o superior. Versión actual: %s', NB_TEXT_DOMAIN),
            PHP_VERSION
        );
        echo '</p></div>';
    }
}

// Initialize plugin
NewBytes_Connector::get_instance();

/**
 * Get plugin instance (for external access)
 * 
 * Provides external access to the plugin singleton instance.
 * 
 * @since 1.0.0
 * @return NewBytes_Connector The plugin instance
 */
function nb_connector() {
    return NewBytes_Connector::get_instance();
}