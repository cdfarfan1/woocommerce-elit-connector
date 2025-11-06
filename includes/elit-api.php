<?php
/**
 * ELIT API integration functions
 * 
 * @package ELIT_Connector
 * @version 1.1.3
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class ELIT_API_Manager {
    
    private static $api_url = 'https://clientes.elit.com.ar/v1/api';
    private static $csv_api_url = 'https://clientes.elit.com.ar/v1/api/productos/csv';
    private static $max_limit = 100;

    private static function get_credentials() {
        $user_id = get_option('elit_user_id');
        $token = get_option('elit_token');
        
        if (empty($user_id) || empty($token)) {
            NB_Logger::error('Credenciales de ELIT no configuradas');
            return null;
        }
        
        return array(
            'user_id' => intval($user_id),
            'token' => sanitize_text_field($token)
        );
    }

    // ... (make_request, get_all_products, etc. sin cambios)

    /**
     * Transform ELIT product data to WooCommerce format
     *
     * @since 1.0.0
     * @param array $elit_product Raw product data from ELIT API
     * @return array              Formatted product data for WooCommerce
     */
    public static function transform_product_data($elit_product) {
        if (!is_array($elit_product)) {
            return null;
        }
        
        $transformed = array(
            'sku' => self::get_mapped_field($elit_product, 'sku'),
            'name' => self::get_mapped_field($elit_product, 'name'),
            'short_description' => self::build_short_description($elit_product),
            'price' => self::get_elit_price($elit_product),
            'stock_quantity' => self::get_mapped_field($elit_product, 'stock_quantity'),
            'stock_status' => self::get_elit_stock_status($elit_product),
            'categories' => self::get_elit_categories($elit_product), // <-- Esta función será la actualizada
            'images' => self::get_elit_images($elit_product),
            'weight' => self::get_mapped_field($elit_product, 'weight'),
            'brand' => self::get_mapped_field($elit_product, 'brand'),
            // ... (el resto de los campos)
        );

        $prefix = get_option('elit_sku_prefix', 'ELIT_');
        if (!empty($prefix) && !empty($transformed['sku'])) {
            $transformed['sku'] = $prefix . $transformed['sku'];
        }
        
        return $transformed;
    }

    // ... (get_field_value, get_mapped_field, get_elit_price sin cambios)

    /**
     * Get categories from ELIT product data with intelligent filtering.
     *
     * Extracts category information and prevents the brand from being added as a regular category.
     *
     * @since 1.1.3
     * @param array $elit_product ELIT product data
     * @return array              Array of category names
     */
    private static function get_elit_categories($elit_product) {
        $categories = array();
        $brand = isset($elit_product['marca']) ? trim($elit_product['marca']) : null;

        // Add main category, only if it's not the same as the brand
        if (isset($elit_product['categoria']) && !empty($elit_product['categoria'])) {
            $category_name = trim($elit_product['categoria']);
            if (strcasecmp($category_name, $brand) !== 0) {
                $categories[] = $category_name;
            }
        }
        
        // Add subcategory, only if it's not the same as the brand
        if (isset($elit_product['sub_categoria']) && !empty($elit_product['sub_categoria'])) {
            $subcategory_name = trim($elit_product['sub_categoria']);
            if (strcasecmp($subcategory_name, $brand) !== 0) {
                $categories[] = $subcategory_name;
            }
        }
        
        // Add brand as a prefixed category if it exists
        if ($brand) {
            $categories[] = 'Marca: ' . $brand;
        }
        
        // Add gamer category if product is gamer
        if (isset($elit_product['gamer']) && $elit_product['gamer'] === true) {
            $categories[] = 'Gaming';
        }
        
        return array_filter(array_unique($categories));
    }

    // ... (el resto de las funciones de la clase como get_elit_images, get_elit_stock, etc.)
}

// ... (resto del archivo elit-api.php)
