<?php
/**
 * Demo Mode for ELIT Plugin
 * 
 * This file provides demo functionality when ELIT API credentials are not available
 * or when the client account is disabled.
 * 
 * @package ELIT_Connector
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Demo Product Data
 * 
 * Sample products that simulate ELIT API response for demonstration purposes
 * 
 * @since 1.0.0
 * @return array
 */
function elit_get_demo_products() {
    return array(
        array(
            'id' => 1001,
            'codigo_producto' => 'DEMO001',
            'codigo_alfa' => 'DEMO-001',
            'nombre' => 'Procesador Intel Core i7-12700K',
            'categoria' => 'Procesadores',
            'sub_categoria' => 'Intel',
            'marca' => 'Intel',
            'precio' => 45000,
            'pvp_ars' => 67500,
            'pvp_usd' => 150,
            'stock_total' => 15,
            'stock_deposito_cliente' => 8,
            'peso' => 0.5,
            'ean' => '1234567890123',
            'garantia' => '3 años',
            'nivel_stock' => 'alto',
            'gamer' => true,
            'imagenes' => array(
                'https://via.placeholder.com/300x300/0066CC/FFFFFF?text=Intel+i7'
            ),
            'atributos' => array(
                array('nombre' => 'Núcleos', 'valor' => '12'),
                array('nombre' => 'Hilos', 'valor' => '20'),
                array('nombre' => 'Frecuencia Base', 'valor' => '3.6 GHz'),
                array('nombre' => 'Socket', 'valor' => 'LGA 1700')
            )
        ),
        array(
            'id' => 1002,
            'codigo_producto' => 'DEMO002',
            'codigo_alfa' => 'DEMO-002',
            'nombre' => 'Placa de Video NVIDIA RTX 4070',
            'categoria' => 'Placas de Video',
            'sub_categoria' => 'NVIDIA',
            'marca' => 'NVIDIA',
            'precio' => 120000,
            'pvp_ars' => 180000,
            'pvp_usd' => 400,
            'stock_total' => 5,
            'stock_deposito_cliente' => 2,
            'peso' => 1.2,
            'ean' => '1234567890124',
            'garantia' => '3 años',
            'nivel_stock' => 'bajo',
            'gamer' => true,
            'imagenes' => array(
                'https://via.placeholder.com/300x300/00AA00/FFFFFF?text=RTX+4070'
            ),
            'atributos' => array(
                array('nombre' => 'Memoria', 'valor' => '12GB GDDR6X'),
                array('nombre' => 'Ancho de Banda', 'valor' => '504 GB/s'),
                array('nombre' => 'Ray Tracing', 'valor' => 'Sí'),
                array('nombre' => 'DLSS', 'valor' => '3.0')
            )
        ),
        array(
            'id' => 1003,
            'codigo_producto' => 'DEMO003',
            'codigo_alfa' => 'DEMO-003',
            'nombre' => 'Memoria RAM DDR4 32GB 3200MHz',
            'categoria' => 'Memorias',
            'sub_categoria' => 'DDR4',
            'marca' => 'Corsair',
            'precio' => 25000,
            'pvp_ars' => 37500,
            'pvp_usd' => 85,
            'stock_total' => 0,
            'stock_deposito_cliente' => 0,
            'peso' => 0.1,
            'ean' => '1234567890125',
            'garantia' => 'Lifetime',
            'nivel_stock' => 'bajo',
            'gamer' => false,
            'imagenes' => array(
                'https://via.placeholder.com/300x300/FF6600/FFFFFF?text=DDR4+32GB'
            ),
            'atributos' => array(
                array('nombre' => 'Capacidad', 'valor' => '32GB'),
                array('nombre' => 'Velocidad', 'valor' => '3200MHz'),
                array('nombre' => 'Tipo', 'valor' => 'DDR4'),
                array('nombre' => 'Latencia', 'valor' => 'CL16')
            )
        )
    );
}

/**
 * Demo API Manager
 * 
 * Simulates ELIT API responses for demonstration purposes
 * 
 * @since 1.0.0
 */
class ELIT_Demo_API {
    
    /**
     * Get demo products in batches
     * 
     * @param int $offset Starting offset
     * @param int $limit Maximum products to return
     * @return array Array of demo products
     * @since 1.0.0
     */
    public static function get_products_batch($offset = 0, $limit = 5) {
        $all_products = elit_get_demo_products();
        return array_slice($all_products, $offset, $limit);
    }
    
    /**
     * Transform demo product data to WooCommerce format
     * 
     * @param array $demo_product Demo product data
     * @return array|false WooCommerce product data or false on error
     * @since 1.0.0
     */
    public static function transform_product_data($demo_product) {
        if (empty($demo_product) || !is_array($demo_product)) {
            return false;
        }
        
        $prefix = get_option('elit_sku_prefix', 'ELIT_');
        $sku = $prefix . $demo_product['codigo_producto'];
        
        // Get price with markup
        $price = self::get_demo_price($demo_product);
        
        // Get stock status
        $stock_quantity = intval($demo_product['stock_total'] ?? 0);
        $stock_status = self::get_demo_stock_status($demo_product);
        
        // Build categories
        $categories = array();
        if (!empty($demo_product['categoria'])) {
            $categories[] = $demo_product['categoria'];
        }
        if (!empty($demo_product['sub_categoria'])) {
            $categories[] = $demo_product['sub_categoria'];
        }
        if (!empty($demo_product['marca'])) {
            $categories[] = $demo_product['marca'];
        }
        
        // Build short description
        $short_description = self::build_demo_short_description($demo_product);
        
        return array(
            'sku' => $sku,
            'name' => $demo_product['nombre'],
            'price' => $price,
            'stock_quantity' => $stock_quantity,
            'stock_status' => $stock_status,
            'categories' => $categories,
            'short_description' => $short_description,
            'images' => $demo_product['imagenes'] ?? array(),
            'weight' => floatval($demo_product['peso'] ?? 0),
            'meta_data' => array(
                'elit_id' => $demo_product['id'],
                'elit_codigo_alfa' => $demo_product['codigo_alfa'],
                'elit_ean' => $demo_product['ean'],
                'elit_garantia' => $demo_product['garantia'],
                'elit_gamer' => $demo_product['gamer'] ? 'Sí' : 'No'
            )
        );
    }
    
    /**
     * Get demo price with markup
     * 
     * @param array $demo_product Demo product data
     * @return float Price with markup applied
     * @since 1.0.0
     */
    private static function get_demo_price($demo_product) {
        $price = 0;
        $use_usd = get_option('elit_sync_usd', false);
        
        if ($use_usd) {
            $price = floatval($demo_product['pvp_usd'] ?? 0);
        } else {
            $price = floatval($demo_product['pvp_ars'] ?? 0);
        }
        
        // Apply markup if configured
        if ($price > 0 && function_exists('nb_calculate_price_with_markup')) {
            $apply_markup_on_pvp = get_option('elit_apply_markup_on_pvp', false);
            if ($apply_markup_on_pvp) {
                $price = nb_calculate_price_with_markup($price);
            }
        }
        
        return $price > 0 ? $price : 0;
    }
    
    /**
     * Get demo stock status
     * 
     * @param array $demo_product Demo product data
     * @return string Stock status
     * @since 1.0.0
     */
    private static function get_demo_stock_status($demo_product) {
        $stock_quantity = intval($demo_product['stock_total'] ?? 0);
        $nivel_stock = $demo_product['nivel_stock'] ?? 'bajo';
        
        if ($stock_quantity > 10) {
            return 'instock';
        } elseif ($stock_quantity > 0) {
            return 'onbackorder';
        } else {
            return 'outofstock';
        }
    }
    
    /**
     * Build demo short description
     * 
     * @param array $demo_product Demo product data
     * @return string Short description
     * @since 1.0.0
     */
    private static function build_demo_short_description($demo_product) {
        $parts = array();
        
        if (!empty($demo_product['marca'])) {
            $parts[] = 'Marca: ' . $demo_product['marca'];
        }
        
        if (!empty($demo_product['categoria'])) {
            $parts[] = 'Categoría: ' . $demo_product['categoria'];
        }
        
        if (!empty($demo_product['garantia'])) {
            $parts[] = 'Garantía: ' . $demo_product['garantia'];
        }
        
        if (!empty($demo_product['gamer']) && $demo_product['gamer']) {
            $parts[] = 'Gaming: Sí';
        }
        
        if (!empty($demo_product['atributos']) && is_array($demo_product['atributos'])) {
            foreach ($demo_product['atributos'] as $attr) {
                if (isset($attr['nombre']) && isset($attr['valor'])) {
                    $parts[] = $attr['nombre'] . ': ' . $attr['valor'];
                }
            }
        }
        
        return implode(' | ', $parts);
    }
    
    /**
     * Test demo connection
     * 
     * @return array Test result
     * @since 1.0.0
     */
    public static function test_connection() {
        $demo_products = elit_get_demo_products();
        $total_products = count($demo_products);
        
        return array(
            'success' => true,
            'message' => "Modo DEMO activado. Productos de demostración disponibles: {$total_products}"
        );
    }
}
