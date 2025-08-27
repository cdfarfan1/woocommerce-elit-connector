<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function nb_activation() {
    // Establecer valores por defecto
    if (!get_option('nb_sync_interval')) {
        add_option('nb_sync_interval', '3600'); // 1 hora por defecto
    }
    
    if (!get_option('nb_markup_percentage')) {
        add_option('nb_markup_percentage', '35'); // 35% por defecto
    }

    // Programar el primer evento cron
    if (!wp_next_scheduled('nb_cron_sync_event')) {
        wp_schedule_event(time(), 'hourly', 'nb_cron_sync_event');
    }
}

function nb_deactivation() {
    try {
        // Verificar que la función existe
        if (function_exists('wp_next_scheduled') && function_exists('wp_unschedule_event')) {
            $timestamp = wp_next_scheduled('nb_cron_sync_event');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'nb_cron_sync_event');
            }
        }
        
        // Limpiar opciones si es necesario
        // delete_option('nb_sync_interval');
        // delete_option('nb_markup_percentage');
        
    } catch (Exception $e) {
        error_log('Error en nb_deactivation: ' . $e->getMessage());
    }
} 