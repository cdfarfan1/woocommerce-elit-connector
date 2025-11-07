<?php
/**
 * Plugin Name: Conector ELIT para WooCommerce (Corregido)
 * Plugin URI: https://github.com/cdfarfan1/woocommerce-elit-connector
 * Description: Sincroniza productos del catálogo de ELIT con WooCommerce. Versión reparada y limpia.
 * Version: 2.0.0
 * Author: Cristian Farfan, Pragmatic Solutions
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin para rutas limpias
define('ELIT_PLUGIN_FILE', __FILE__);
define('ELIT_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Función principal para cargar el plugin.
 * Comprueba las dependencias antes de cargar los archivos principales.
 */
function elit_connector_load() {
    // Comprobar si WooCommerce está activo. Si no, no hacer nada.
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'elit_woocommerce_missing_notice');
        return;
    }

    // Array de archivos principales del plugin. Limpio y sin dependencias externas.
    $core_files = [
        'includes/settings.php',      // Lógica y renderizado de la página de ajustes.
        'includes/admin-hooks.php',   // Registra el menú, los estilos y los scripts.
        'includes/sync.php',          // Lógica de sincronización de productos.
        'includes/ajax-handlers.php'  // Manejadores para las llamadas AJAX (probar conexión, etc.).
    ];

    foreach ($core_files as $file) {
        $path = ELIT_PLUGIN_PATH . $file;
        if (file_exists($path)) {
            require_once $path;
        } else {
            // Si falta un archivo crítico, notificar al admin.
            add_action('admin_notices', function() use ($file) {
                echo '<div class="error"><p><strong>Conector ELIT (Error):</strong> No se encuentra el archivo crítico: ' . esc_html($file) . '. El plugin no puede funcionar.</p></div>';
            });
            return; // Detener la carga si un archivo falta.
        }
    }
}
add_action('plugins_loaded', 'elit_connector_load');

/**
 * Muestra un aviso en el admin si WooCommerce no está activo.
 */
function elit_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>Conector ELIT</strong> requiere que WooCommerce esté instalado y activo. Por favor, active WooCommerce para continuar.</p></div>';
}

// --- Hooks de Activación y Desactivación ---

/**
 * Acciones a realizar cuando el plugin es activado.
 * Programa el cron para la sincronización automática.
 */
function elit_activate_plugin() {
    // Asegurarse de que el hook del cron no esté ya programado
    if (!wp_next_scheduled('elit_daily_sync_hook')) {
        // Programar el evento para que se ejecute dos veces al día
        wp_schedule_event(time(), 'twicedaily', 'elit_daily_sync_hook');
    }
}
register_activation_hook(ELIT_PLUGIN_FILE, 'elit_activate_plugin');

/**
 * Acciones a realizar cuando el plugin es desactivado.
 * Limpia el cron programado.
 */
function elit_deactivate_plugin() {
    // Obtener la próxima marca de tiempo del evento
    $timestamp = wp_next_scheduled('elit_daily_sync_hook');
    // Limpiar el evento programado
    wp_clear_scheduled_hook('elit_daily_sync_hook', $timestamp);
}
register_deactivation_hook(ELIT_PLUGIN_FILE, 'elit_deactivate_plugin');

// Registrar el hook para que el cron job llame a la función de sincronización
add_action('elit_daily_sync_hook', 'elit_sync_products');
