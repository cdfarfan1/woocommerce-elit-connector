<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function nb_plugin_action_links($links)
{
    $settings = '<a href="' . get_admin_url(null, 'options-general.php?page=nb') . '">Ajustes</a>';
    array_unshift($links, $settings);
    return $links;
}

function nb_menu()
{
    add_options_page('Conector NB', 'Conector NB', 'manage_options', 'nb', 'nb_options_page');
}

function nb_register_settings()
{
    register_setting('nb_options', 'nb_user');
    register_setting('nb_options', 'nb_password');
    register_setting('nb_options', 'nb_token');
    register_setting('nb_options', 'nb_prefix');
    register_setting('nb_options', 'nb_sync_no_iva');
    register_setting('nb_options', 'nb_sync_usd');
    register_setting('nb_options', 'nb_description');
    register_setting('nb_options', 'nb_sync_interval');
    register_setting('nb_options', 'nb_markup_percentage');
}
