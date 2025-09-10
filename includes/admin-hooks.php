<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function elit_plugin_action_links($links)
{
    $settings = '<a href="' . get_admin_url(null, 'options-general.php?page=elit') . '">Ajustes</a>';
    array_unshift($links, $settings);
    return $links;
}

function elit_menu()
{
    add_options_page('Conector ELIT', 'Conector ELIT', 'manage_options', 'elit', 'elit_options_page');
}

function elit_register_settings()
{
    // ELIT API settings
    register_setting('elit_options', 'elit_user_id');
    register_setting('elit_options', 'elit_token');
    register_setting('elit_options', 'elit_sku_prefix');
    register_setting('elit_options', 'elit_sync_usd');
    
    // General settings
    register_setting('elit_options', 'elit_description');
    register_setting('elit_options', 'elit_sync_interval');
    register_setting('elit_options', 'elit_markup_percentage');
    register_setting('elit_options', 'elit_apply_markup_on_pvp');
    
    // Keep legacy settings for compatibility during transition
    register_setting('elit_options', 'nb_description');
    register_setting('elit_options', 'nb_sync_interval');
    register_setting('elit_options', 'nb_markup_percentage');
}
