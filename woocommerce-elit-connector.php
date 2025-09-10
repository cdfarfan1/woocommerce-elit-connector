<?php
/*
Plugin Name: Conector ELIT
Description: Sincroniza los productos del catálogo de ELIT con WooCommerce.
Author: ELIT Connector
Author URI: https://elit.com.ar
Version: 1.0.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('API_URL_ELIT', 'https://clientes.elit.com.ar/v1/api');
define('VERSION_ELIT', '1.0.0');
define('ELIT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ELIT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once ELIT_PLUGIN_PATH . 'includes/activation.php';
require_once ELIT_PLUGIN_PATH . 'includes/admin-hooks.php';
require_once ELIT_PLUGIN_PATH . 'includes/cron-hooks.php';
require_once ELIT_PLUGIN_PATH . 'includes/rest-api.php';
require_once ELIT_PLUGIN_PATH . 'includes/utils.php';
require_once ELIT_PLUGIN_PATH . 'includes/price-calculator.php';
require_once ELIT_PLUGIN_PATH . 'includes/modals.php';
require_once ELIT_PLUGIN_PATH . 'includes/product-sync.php';
require_once ELIT_PLUGIN_PATH . 'includes/product-delete.php';
require_once ELIT_PLUGIN_PATH . 'includes/settings.php';
require_once ELIT_PLUGIN_PATH . 'includes/sync-callback.php';

// Include ELIT specific files
require_once ELIT_PLUGIN_PATH . 'includes/elit-api.php';
require_once ELIT_PLUGIN_PATH . 'includes/elit-sync-callback.php';

/**
 * Updated callback function to use ELIT instead of NewBytes
 * 
 * @param bool $syncDescription Whether to sync product descriptions
 * @return mixed
 */
function nb_callback($syncDescription = false) {
    // Check if ELIT credentials are configured
    $elit_user_id = get_option('elit_user_id');
    $elit_token = get_option('elit_token');
    
    if (!empty($elit_user_id) && !empty($elit_token)) {
        // Use ELIT sync if credentials are configured
        return elit_callback($syncDescription);
    } else {
        // Fallback to original NewBytes sync if available
        if (function_exists('nb_original_callback')) {
            return nb_original_callback($syncDescription);
        } else {
            return 'Error: No hay credenciales configuradas. Configure las credenciales de ELIT en los ajustes.';
        }
    }
}

// WordPress Hooks
add_action('wp_ajax_elit_update_description_products', 'elit_update_description_products');
add_action('admin_enqueue_scripts', 'enqueue_fontawesome');
add_action('wp_ajax_elit_delete_products', 'elit_delete_products');
add_action('admin_post_elit_delete_products', 'elit_delete_products');
add_action('update_option_elit_sync_interval', 'elit_update_cron_schedule', 10, 2);
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'elit_plugin_action_links');
add_filter('cron_schedules', 'elit_cron_interval');
add_action('admin_menu', 'elit_menu');
add_action('admin_init', 'elit_register_settings');
add_action('elit_cron_sync_event', 'elit_smart_callback');
add_action('rest_api_init', 'elit_sync_catalog');

// ELIT specific hooks
add_action('wp_ajax_test_elit_connection', 'ajax_test_elit_connection');

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'elit_activation');
register_deactivation_hook(__FILE__, 'elit_deactivation');

/**
 * Enqueue FontAwesome for admin pages
 */
function enqueue_fontawesome($hook) {
    if (strpos($hook, 'nb') !== false || strpos($hook, 'elit') !== false) {
        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );
    }
}

/**
 * Add admin notice for ELIT configuration
 */
function elit_admin_notices() {
    if (get_current_screen()->id === 'settings_page_nb') {
        $elit_user_id = get_option('elit_user_id');
        $elit_token = get_option('elit_token');
        
        if (empty($elit_user_id) || empty($elit_token)) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Conector ELIT:</strong> Configure sus credenciales de ELIT (User ID y Token) para comenzar la sincronización.</p>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'elit_admin_notices');

/**
 * Add ELIT connection test button to settings page
 */
function add_elit_test_button() {
    if (get_current_screen()->id === 'settings_page_nb') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add test connection button after ELIT token field
            $('#elit_token').parent().append('<br/><button type="button" id="test-elit-connection" class="button button-secondary" style="margin-top: 10px;">Probar Conexión ELIT</button><div id="elit-connection-result" style="margin-top: 10px;"></div>');
            
            $('#test-elit-connection').on('click', function() {
                var $btn = $(this);
                var $result = $('#elit-connection-result');
                
                $btn.prop('disabled', true).text('Probando...');
                $result.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_elit_connection',
                        nonce: '<?php echo wp_create_nonce('nb_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div style="color: green; font-weight: bold;">✓ ' + response.data.message + '</div>');
                        } else {
                            $result.html('<div style="color: red; font-weight: bold;">✗ ' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        $result.html('<div style="color: red; font-weight: bold;">✗ Error al probar la conexión</div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Probar Conexión ELIT');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'add_elit_test_button');

/**
 * Plugin information function
 */
function elit_get_plugin_info() {
    return array(
        'name' => 'Conector ELIT',
        'version' => VERSION_ELIT,
        'description' => 'Sincroniza productos desde ELIT a WooCommerce',
        'author' => 'ELIT Connector',
        'api_url' => API_URL_ELIT
    );
}

/**
 * Check plugin requirements
 */
function elit_check_requirements() {
    // Check if WooCommerce is active
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>Plugin requerido</h1>' .
            '<p>El Conector ELIT requiere WooCommerce para funcionar.</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">&laquo; Volver a Plugins</a></p>'
        );
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>Versión de PHP no compatible</h1>' .
            '<p>El Conector ELIT requiere PHP 7.4 o superior. Versión actual: ' . PHP_VERSION . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">&laquo; Volver a Plugins</a></p>'
        );
    }
}

// Check requirements on activation
register_activation_hook(__FILE__, 'elit_check_requirements');

/**
 * Initialize plugin logging
 */
function elit_init_logging() {
    if (class_exists('NB_Logger')) {
        NB_Logger::init();
        NB_Logger::info('Plugin Conector ELIT iniciado - Versión: ' . VERSION_ELIT);
    }
}
add_action('init', 'elit_init_logging');

/**
 * Add custom admin styles
 */
function elit_admin_styles() {
    if (get_current_screen()->id === 'settings_page_nb') {
        ?>
        <style>
        .elit-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .elit-section h3 {
            margin-top: 0;
            color: #0073aa;
        }
        .elit-connection-status {
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        .elit-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .elit-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        </style>
        <?php
    }
}
add_action('admin_head', 'elit_admin_styles');

/**
 * ELIT specific functions to replace NB functions
 */

// Cron interval function
function elit_cron_interval($schedules) {
    $interval = get_option('elit_sync_interval', get_option('nb_sync_interval', 3600));
    $schedules['elit_interval'] = array(
        'interval' => $interval,
        'display' => 'ELIT Sync Interval'
    );
    return $schedules;
}

// Update cron schedule when interval changes
function elit_update_cron_schedule($old_value, $new_value) {
    if ($old_value != $new_value) {
        wp_clear_scheduled_hook('elit_cron_sync_event');
        wp_schedule_event(time(), 'elit_interval', 'elit_cron_sync_event');
    }
}

// Activation function
function elit_activation() {
    if (function_exists('nb_activation')) {
        nb_activation();
    }
    // Schedule ELIT sync
    if (!wp_next_scheduled('elit_cron_sync_event')) {
        wp_schedule_event(time(), 'elit_interval', 'elit_cron_sync_event');
    }
}

// Deactivation function  
function elit_deactivation() {
    if (function_exists('nb_deactivation')) {
        nb_deactivation();
    }
    wp_clear_scheduled_hook('elit_cron_sync_event');
}

// Sync catalog function
function elit_sync_catalog() {
    if (function_exists('nb_sync_catalog')) {
        nb_sync_catalog();
    }
}

// Delete products function
function elit_delete_products() {
    if (function_exists('nb_delete_products')) {
        nb_delete_products();
    }
}

// Update description products function
function elit_update_description_products() {
    if (function_exists('nb_update_description_products')) {
        nb_update_description_products();
    }
}
