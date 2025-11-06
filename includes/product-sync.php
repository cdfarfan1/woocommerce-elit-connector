<?php
/**
 * Product synchronization functions for NewBytes Connector
 *
 * @package NewBytes_Connector
 * @version 1.1.2
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class NB_Product_Sync {
    
    private static $batch_size = 20; // Aumentado para hostings optimizados
    private static $max_execution_time = 280; // Un poco menos del límite de 300s

    // ... (otras funciones de la clase como delete_products_by_prefix, etc., permanecen igual)

    /**
     * Set product images using FIFU (Featured Image From URL)
     *
     * Assigns external image URLs to a product using FIFU's custom fields.
     * This avoids downloading images to the local server, improving performance.
     *
     * @since 1.1.2
     * @param int   $product_id Product ID
     * @param array $images     Array of image URLs
     * @return void
     */
    private static function set_product_images($product_id, $images) {
        if (empty($images) || !is_array($images) || !is_plugin_active('featured-image-from-url/featured-image-from-url.php')) {
            if (!is_plugin_active('featured-image-from-url/featured-image-from-url.php')) {
                NB_Logger::warning("El plugin FIFU no está activo. Las imágenes no se pueden asignar para el producto {$product_id}.");
            }
            return;
        }

        // Sanitize all image URLs
        $sanitized_images = array_filter(array_map('esc_url_raw', $images));

        if (empty($sanitized_images)) {
            NB_Logger::info("No se proporcionaron URLs de imagen válidas para el producto {$product_id}.");
            return;
        }

        // The first image is the featured image
        $featured_image_url = array_shift($sanitized_images);

        // Update the featured image URL for FIFU
        update_post_meta($product_id, 'fifu_image_url', $featured_image_url);

        // The rest of the images go into the gallery
        if (!empty($sanitized_images)) {
            // FIFU expects a newline-separated string of URLs for the gallery
            $gallery_urls = implode("\n", $sanitized_images);
            update_post_meta($product_id, 'fifu_image_urls', $gallery_urls);
        } else {
            // Ensure the gallery meta is empty if no other images are provided
            delete_post_meta($product_id, 'fifu_image_urls');
        }

        NB_Logger::info("Imágenes externas de FIFU actualizadas para el producto {$product_id}.");
    }

    // ... (el resto de las funciones de la clase NB_Product_Sync como create_new_product, update_existing_product, etc.)

    /**
     * Create new product
     * ...
     */
    private static function create_new_product($data) {
        $product = new WC_Product_Simple();
        
        // ... (configuración del producto: nombre, sku, precio, etc.)
        $product->set_name($data['name']);
        $product->set_sku($data['sku']);
        $product->set_regular_price($data['price']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        
        if (!empty($data['short_description'])) {
            $product->set_short_description($data['short_description']);
        }
        
        $stock_quantity = intval($data['stock_quantity'] ?? 0);
        $stock_status = $data['stock_status'] ?? 'outofstock';
        
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock_quantity);
        $product->set_stock_status($stock_status);
        
        if ($stock_status === 'onbackorder') {
            $product->set_backorders('yes');
        } else {
            $product->set_backorders('no');
        }
        
        $product_id = $product->save();
        
        if ($product_id) {
            self::set_product_categories($product_id, $data['categories']);
            // Llamar a la nueva función de imágenes optimizada para FIFU
            self::set_product_images($product_id, $data['images']);
            self::update_product_metadata($product_id, $data);
        }
        
        return $product_id;
    }

    /**
     * Update existing product
     * ...
     */
    private static function update_existing_product($product_id, $data, $sync_descriptions = false) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        $product->set_name($data['name']);
        
        if (get_option('elit_update_prices', true)) {
            $product->set_regular_price($data['price']);
        }

        if (!empty($data['short_description'])) {
            $product->set_short_description($data['short_description']);
        }
        
        if (get_option('elit_update_stock', true)) {
            $stock_quantity = intval($data['stock_quantity'] ?? 0);
            $stock_status = $data['stock_status'] ?? 'outofstock';
            
            $product->set_manage_stock(true);
            $product->set_stock_quantity($stock_quantity);
            $product->set_stock_status($stock_status);

            if ($stock_status === 'onbackorder') {
                $product->set_backorders('yes');
            } else {
                $product->set_backorders('no');
            }
        }
        
        $result = $product->save();
        
        if ($result) {
            if (get_option('elit_update_categories', true)) {
                self::set_product_categories($product_id, $data['categories']);
            }
            
            // Actualizar imágenes usando la nueva función para FIFU
            // Se cambia el segundo parámetro de get_option a `true` para que esté activado por defecto
            if (get_option('elit_update_images', true) && !empty($data['images'])) {
                self::set_product_images($product_id, $data['images']);
            }
            
            if (get_option('elit_update_metadata', true)) {
                self::update_product_metadata($product_id, $data);
            }
        }
        
        return $result;
    }

    // ... (el resto de las funciones de la clase y del archivo)
}

// ... (resto del archivo product-sync.php)
