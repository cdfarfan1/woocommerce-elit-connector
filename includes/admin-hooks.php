<?php
/**
 * admin-hooks.php
 * --- 
 * Registra los hooks necesarios para el área de administración.
 * Esto incluye la página de ajustes, los scripts y los estilos.
 */

if (!defined('ABSPATH')) {
    exit; // No permitir acceso directo.
}

/**
 * Añade la página de opciones al menú de "Ajustes" de WordPress.
 */
function elit_add_options_page() {
    add_options_page(
        'Conector ELIT',                 // Título de la página
        'Conector ELIT',                 // Título del menú
        'manage_options',                // Capacidad requerida
        'elit-connector-settings',       // Slug del menú
        'elit_render_settings_page'      // Función que renderiza la página (de settings.php)
    );
}
add_action('admin_menu', 'elit_add_options_page');

/**
 * Registra los ajustes del plugin para que WordPress los guarde.
 */
function elit_register_settings() {
    // Grupo de opciones para el formulario
    $option_group = 'elit_options_group';

    // Lista de todas las opciones que guardaremos
    $settings = [
        'elit_user_id',
        'elit_token',
        'elit_sku_prefix',
        'elit_sync_usd',
        'nb_markup_percentage', // Nombre antiguo, se mantiene por compatibilidad
        'elit_update_prices',
        'elit_update_stock',
        'elit_update_images',
        'elit_update_categories'
    ];

    // Registrar cada opción
    foreach ($settings as $setting_name) {
        register_setting($option_group, $setting_name);
    }
}
add_action('admin_init', 'elit_register_settings');

/**
 * Encola los archivos CSS y JS en la página de ajustes del plugin.
 */
function elit_admin_enqueue_assets($hook) {
    // Salir si no estamos en nuestra página de ajustes para no cargar assets innecesariamente.
    if ($hook !== 'settings_page_elit-connector-settings') {
        return;
    }

    // URL base de la carpeta del plugin
    $plugin_url = plugin_dir_url(__FILE__); // Apunta a la carpeta 'includes'

    // Corregir la URL para que apunte a la raíz del plugin
    $plugin_root_url = str_replace('includes/', '', $plugin_url);

    // Encolar la hoja de estilos principal
    wp_enqueue_style(
        'elit-admin-style',
        $plugin_root_url . 'assets/css/admin-style.css',
        [], // Sin dependencias
        '2.0.0' // Versión del archivo
    );

    // Encolar el script de JavaScript para la interactividad (AJAX)
    wp_enqueue_script(
        'elit-admin-script',
        $plugin_root_url . 'assets/js/admin.js',
        ['jquery'], // Depende de jQuery
        '2.0.0', // Versión del archivo
        true // Cargar en el footer
    );

    // Pasar datos de PHP a JavaScript de forma segura (para AJAX)
    wp_localize_script('elit-admin-script', 'elit_ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('elit_ajax_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'elit_admin_enqueue_assets');
