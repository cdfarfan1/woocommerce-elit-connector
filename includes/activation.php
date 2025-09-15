<?php
/**
 * Simple activation functions for ELIT Plugin (No MySQL operations)
 * 
 * @package ELIT_Connector
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Plugin activation function (minimal and safe)
 *
 * @since 1.0.0
 * @return void
 */
function nb_activation() {
        // Check user capabilities
        if (!current_user_can('activate_plugins')) {
        wp_die('No tienes permisos para activar plugins.');
    }
    
    // Set minimal default options for ELIT (no complex operations)
    if (get_option('elit_sku_prefix') === false) {
        add_option('elit_sku_prefix', 'ELIT_');
    }
    
    if (get_option('elit_user_id') === false) {
        add_option('elit_user_id', '14679');
    }
    
    if (get_option('elit_token') === false) {
        add_option('elit_token', '4ou95wmie1q');
    }
    
    if (get_option('elit_markup_percentage') === false) {
        add_option('elit_markup_percentage', 0);
    }
    
    if (get_option('elit_sync_interval') === false) {
        add_option('elit_sync_interval', 14400); // 4 hours
    }
    
    if (get_option('elit_sync_usd') === false) {
        add_option('elit_sync_usd', false);
    }
    
    if (get_option('elit_apply_markup_on_pvp') === false) {
        add_option('elit_apply_markup_on_pvp', false);
    }
    
    // Set plugin version
    update_option('elit_plugin_version', '1.0.0');
    
    // Log activation (simple)
    error_log('ELIT Plugin: Activado exitosamente');
}

/**
 * Plugin deactivation function (minimal and safe)
 *
 * @since 1.0.0
 * @return void
 */
function nb_deactivation() {
    // Clear scheduled events (safe operation)
    wp_clear_scheduled_hook('elit_cron_sync_event');
    
    // Log deactivation
    error_log('ELIT Plugin: Desactivado');
}