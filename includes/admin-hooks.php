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
    
    // Field mapping settings
    $field_mappings = array('sku', 'name', 'price', 'price_usd', 'stock_quantity', 'stock_status', 'weight', 'ean', 'warranty', 'gamer', 'category', 'subcategory', 'brand', 'images', 'thumbnails', 'attributes', 'link');
    
    foreach ($field_mappings as $field) {
        register_setting('elit_options', 'elit_field_' . $field);
        register_setting('elit_options', 'elit_update_' . $field);
    }
    
    // Update settings
    register_setting('elit_options', 'elit_update_prices');
    register_setting('elit_options', 'elit_update_stock');
    register_setting('elit_options', 'elit_update_images');
    register_setting('elit_options', 'elit_max_images');
    register_setting('elit_options', 'elit_cleanup_duplicate_images');
    register_setting('elit_options', 'elit_update_categories');
    register_setting('elit_options', 'elit_update_metadata');
    
    // Keep legacy settings for compatibility during transition
    register_setting('elit_options', 'nb_description');
    register_setting('elit_options', 'nb_sync_interval');
    register_setting('elit_options', 'nb_markup_percentage');
}
