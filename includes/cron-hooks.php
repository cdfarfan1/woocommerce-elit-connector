<?php
if (!defined('ABSPATH')) {
    exit;
}

function nb_cron_interval($schedules)
{
    // Obtén el intervalo seleccionado por el usuario
    $user_interval = intval(get_option('nb_sync_interval', 3600));
    $user_interval_in_min = $user_interval / 60;

    $schedules['custom_user_interval'] = array(
        'interval' => $user_interval,
        'display'  => __("NewBytes: Intervalo personalizado para cada {$user_interval_in_min} minutos")
    );

    return $schedules;
}

function nb_update_cron_schedule($old_value = null, $value = null)
{
    $timestamp = wp_next_scheduled('nb_cron_sync_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'nb_cron_sync_event');
    }
    wp_schedule_event(time(), 'custom_user_interval', 'nb_cron_sync_event');
}

// Los hooks se registrarán en el archivo principal
