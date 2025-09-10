<?php
/**
 * Product synchronization functions for NewBytes Connector
 * 
 * @package NewBytes_Connector
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Product Sync Manager Class
 * 
 * Handles product synchronization operations including creation, updating,
 * deletion, and batch processing with improved performance and error handling.
 * 
 * @since 1.0.0
 * @package NewBytes_Connector
 */
class NB_Product_Sync {
    
    /**
     * Maximum number of products to process in a single batch
     * 
     * @var int
     * @since 1.0.0
     */
    private static $batch_size = 50;
    
    /**
     * Maximum execution time in seconds (5 minutes)
     * 
     * @var int
     * @since 1.0.0
     */
    private static $max_execution_time = 300;
    
    /**
     * Delete products by prefix with improved performance and safety
     * 
     * Removes products that match a specific SKU prefix but are not in the
     * provided list of existing SKUs. Uses batch processing to handle large
     * datasets efficiently.
     * 
     * @since 1.0.0
     * @param array  $existing_skus Array of SKUs that should be preserved
     * @param string $prefix       SKU prefix to filter products for deletion
     * @return array               Results array with deleted count and duration
     */
    public static function delete_products_by_prefix($existing_skus, $prefix) {
        global $wpdb;
        
        if (empty($existing_skus) || empty($prefix)) {
            NB_Logger::error('Parámetros inválidos para eliminar productos');
            return array('error' => 'Parámetros inválidos');
        }
        
        try {
            $start_time = microtime(true);
            NB_Logger::info('Iniciando eliminación de productos con prefijo: ' . $prefix);
            
            // Sanitize and validate SKUs
            $sanitized_skus = array_filter(array_map('sanitize_text_field', $existing_skus));
            
            if (empty($sanitized_skus)) {
                NB_Logger::warning('No hay SKUs válidos para procesar');
                return array('deleted' => 0, 'sync_duration' => self::calculate_duration($start_time));
            }
            
            // Use placeholders for safe SQL
            $placeholders = implode(',', array_fill(0, count($sanitized_skus), '%s'));
            
            // First, get the product IDs to delete
            $query = $wpdb->prepare(
                "SELECT DISTINCT p.ID 
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'product'
                 AND p.post_status IN ('publish', 'private', 'draft')
                 AND pm.meta_key = '_sku'
                 AND pm.meta_value REGEXP %s
                 AND pm.meta_value NOT IN ({$placeholders})",
                '^' . $wpdb->esc_like($prefix),
                ...$sanitized_skus
            );
            
            $product_ids = $wpdb->get_col($query);
            
            if (empty($product_ids)) {
                NB_Logger::info('No se encontraron productos para eliminar');
                return array('deleted' => 0, 'sync_duration' => self::calculate_duration($start_time));
            }
            
            $deleted_count = 0;
            $batches = array_chunk($product_ids, self::$batch_size);
            
            foreach ($batches as $batch) {
                // Check execution time
                if ((microtime(true) - $start_time) > self::$max_execution_time) {
                    NB_Logger::warning('Tiempo de ejecución excedido, deteniendo eliminación');
                    break;
                }
                
                foreach ($batch as $product_id) {
                    if (wp_delete_post($product_id, true)) {
                        $deleted_count++;
                    }
                }
            }
            
            $duration = self::calculate_duration($start_time);
            NB_Logger::info("Eliminación completada: {$deleted_count} productos eliminados");
            
            return array(
                'deleted' => $deleted_count,
                'sync_duration' => $duration
            );
            
        } catch (Exception $e) {
            $error_msg = 'Error al eliminar productos: ' . $e->getMessage();
            NB_Logger::error($error_msg);
            return array('error' => $error_msg);
        }
    }
    
    /**
     * Sync products from API with batch processing
     * 
     * Processes an array of product data from API, creating new products
     * or updating existing ones. Uses batch processing to handle large
     * datasets efficiently while respecting execution time limits.
     * 
     * @since 1.0.0
     * @param array $api_data         Array of product data from API
     * @param bool  $sync_descriptions Whether to sync product descriptions
     * @return array                  Results array with processing statistics
     */
    public static function sync_products_from_api($api_data, $sync_descriptions = false) {
        if (empty($api_data) || !is_array($api_data)) {
            NB_Logger::error('Datos de API inválidos para sincronización');
            return array('error' => 'Datos de API inválidos');
        }
        
        $start_time = microtime(true);
        $processed = 0;
        $created = 0;
        $updated = 0;
        $errors = array();
        
        NB_Logger::info('Iniciando sincronización de ' . count($api_data) . ' productos');
        
        try {
            $batches = array_chunk($api_data, self::$batch_size);
            
            foreach ($batches as $batch_index => $batch) {
                // Check execution time
                if ((microtime(true) - $start_time) > self::$max_execution_time) {
                    NB_Logger::warning('Tiempo de ejecución excedido en lote ' . ($batch_index + 1));
                    break;
                }
                
                foreach ($batch as $product_data) {
                    $result = self::process_single_product($product_data, $sync_descriptions);
                    
                    if (isset($result['error'])) {
                        $errors[] = $result['error'];
                    } elseif ($result['action'] === 'created') {
                        $created++;
                    } elseif ($result['action'] === 'updated') {
                        $updated++;
                    }
                    
                    $processed++;
                }
                
                // Clear object cache periodically
                if ($batch_index % 5 === 0) {
                    wp_cache_flush();
                }
            }
            
            $duration = self::calculate_duration($start_time);
            
            $summary = array(
                'processed' => $processed,
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors),
                'sync_duration' => $duration
            );
            
            NB_Logger::info('Sincronización completada: ' . json_encode($summary));
            
            if (!empty($errors)) {
                $summary['error_details'] = array_slice($errors, 0, 10); // Limit error details
            }
            
            return $summary;
            
        } catch (Exception $e) {
            $error_msg = 'Error durante la sincronización: ' . $e->getMessage();
            NB_Logger::error($error_msg);
            return array('error' => $error_msg);
        }
    }
    
    /**
     * Process a single product
     * 
     * Handles the creation or update of a single product based on API data.
     * Validates and sanitizes input data before processing.
     * 
     * @since 1.0.0
     * @param array $product_data     Product data from API
     * @param bool  $sync_descriptions Whether to sync descriptions
     * @return array                  Result array with action taken and product ID
     */
    private static function process_single_product($product_data, $sync_descriptions = false) {
        try {
            // Validate required fields
            if (empty($product_data['sku']) || empty($product_data['name'])) {
                return array('error' => 'SKU o nombre del producto faltante');
            }
            
            // Sanitize product data
            $sanitized_data = array(
                'sku' => sanitize_text_field($product_data['sku']),
                'name' => sanitize_text_field($product_data['name']),
                'price' => floatval($product_data['price'] ?? 0),
                'description' => $sync_descriptions ? wp_kses_post($product_data['description'] ?? '') : '',
                'short_description' => wp_kses_post($product_data['short_description'] ?? ''),
                'stock_quantity' => intval($product_data['stock_quantity'] ?? 0),
                'categories' => is_array($product_data['categories'] ?? null) ? $product_data['categories'] : array(),
                'images' => is_array($product_data['images'] ?? null) ? $product_data['images'] : array()
            );
            
            // Process price with markup
            $sanitized_data['price'] = self::process_product_price($sanitized_data['price']);
            
            // Check if product exists
            $existing_product = wc_get_product_id_by_sku($sanitized_data['sku']);
            
            if ($existing_product) {
                $result = self::update_existing_product($existing_product, $sanitized_data, $sync_descriptions);
                return array('action' => 'updated', 'product_id' => $existing_product, 'result' => $result);
            } else {
                $product_id = self::create_new_product($sanitized_data);
                return array('action' => 'created', 'product_id' => $product_id);
            }
            
        } catch (Exception $e) {
            return array('error' => 'Error procesando producto ' . ($product_data['sku'] ?? 'desconocido') . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Create new product
     *
     * Creates a new WooCommerce product with the provided data.
     * Sets up all necessary product meta and taxonomy relationships.
     *
     * @since 1.0.0
     * @param array $data Sanitized product data
     * @return int|WP_Error Product ID on success, WP_Error on failure
     */
    private static function create_new_product($data) {
        $product = new WC_Product_Simple();
        
        $product->set_name($data['name']);
        $product->set_sku($data['sku']);
        $product->set_regular_price($data['price']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        
        if (!empty($data['description'])) {
            $product->set_description($data['description']);
        }
        
        if (!empty($data['short_description'])) {
            $product->set_short_description($data['short_description']);
        }
        
        // Handle stock management with ELIT stock status
        $stock_quantity = intval($data['stock_quantity'] ?? 0);
        $stock_status = $data['stock_status'] ?? 'outofstock';
        
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock_quantity);
        $product->set_stock_status($stock_status);
        
        // Set backorders if stock is low but available
        if ($stock_status === 'onbackorder') {
            $product->set_backorders('yes');
        } else {
            $product->set_backorders('no');
        }
        
        $product_id = $product->save();
        
        if ($product_id) {
            // Handle categories and images
            self::set_product_categories($product_id, $data['categories']);
            self::set_product_images($product_id, $data['images']);
        }
        
        return $product_id;
    }
    
    /**
     * Update existing product
     * 
     * Updates an existing WooCommerce product with new data.
     * Preserves existing data when sync_descriptions is false.
     * 
     * @since 1.0.0
     * @param int   $product_id       Product ID to update
     * @param array $data            Sanitized product data
     * @param bool  $sync_descriptions Whether to update descriptions
     * @return bool                  True on success, false on failure
     */
    private static function update_existing_product($product_id, $data, $sync_descriptions = false) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        $product->set_name($data['name']);
        $product->set_regular_price($data['price']);
        
        if ($sync_descriptions && !empty($data['description'])) {
            $product->set_description($data['description']);
        }
        
        if (!empty($data['short_description'])) {
            $product->set_short_description($data['short_description']);
        }
        
        // Handle stock management with ELIT stock status
        $stock_quantity = intval($data['stock_quantity'] ?? 0);
        $stock_status = $data['stock_status'] ?? 'outofstock';
        
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock_quantity);
        $product->set_stock_status($stock_status);
        
        // Set backorders if stock is low but available
        if ($stock_status === 'onbackorder') {
            $product->set_backorders('yes');
        } else {
            $product->set_backorders('no');
        }
        
        $result = $product->save();
        
        if ($result) {
            // Handle categories and images
            self::set_product_categories($product_id, $data['categories']);
            if (!empty($data['images'])) {
                self::set_product_images($product_id, $data['images']);
            }
        }
        
        return $result;
    }
    
    /**
     * Set product categories
     * 
     * Assigns categories to a product, creating new categories if they don't exist.
     * 
     * @since 1.0.0
     * @param int   $product_id Product ID
     * @param array $categories Array of category names
     * @return void
     */
    private static function set_product_categories($product_id, $categories) {
        if (empty($categories) || !is_array($categories)) {
            return;
        }
        
        $category_ids = array();
        
        foreach ($categories as $category_name) {
            $category_name = sanitize_text_field($category_name);
            $term = get_term_by('name', $category_name, 'product_cat');
            
            if (!$term) {
                $term_data = wp_insert_term($category_name, 'product_cat');
                if (!is_wp_error($term_data)) {
                    $category_ids[] = $term_data['term_id'];
                }
            } else {
                $category_ids[] = $term->term_id;
            }
        }
        
        if (!empty($category_ids)) {
            wp_set_object_terms($product_id, $category_ids, 'product_cat');
        }
    }
    
    /**
     * Set product images
     *
     * Downloads and assigns images to a product from provided URLs.
     * Sets the first image as the featured image.
     *
     * @since 1.0.0
     * @param int   $product_id Product ID
     * @param array $images     Array of image URLs
     * @return void
     */
    private static function set_product_images($product_id, $images) {
        if (empty($images) || !is_array($images)) {
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $gallery_ids = array();
        
        foreach ($images as $index => $image_url) {
            $image_url = esc_url_raw($image_url);
            
            if (empty($image_url)) {
                continue;
            }
            
            $attachment_id = media_sideload_image($image_url, $product_id, null, 'id');
            
            if (!is_wp_error($attachment_id)) {
                if ($index === 0) {
                    set_post_thumbnail($product_id, $attachment_id);
                } else {
                    $gallery_ids[] = $attachment_id;
                }
            }
        }
        
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        }
    }
    
    /**
     * Calculate sync duration
     * 
     * Calculates the duration of a sync operation and formats it
     * into hours, minutes, and seconds.
     * 
     * @since 1.0.0
     * @param float $start_time Start time from microtime(true)
     * @return array            Formatted duration array
     */
    private static function calculate_duration($start_time) {
        $sync_duration = microtime(true) - $start_time;
        $hours = floor($sync_duration / 3600);
        $minutes = floor(($sync_duration % 3600) / 60);
        $seconds = $sync_duration % 60;
        
        return array(
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => number_format($seconds, 2),
            'total_seconds' => $sync_duration
        );
    }
    
    /**
     * Process product price with markup
     *
     * Applies configured markup percentage to the base price.
     *
     * @since 1.0.0
     * @param float $original_price Base price from API
     * @return float Price with markup applied
     */
    private static function process_product_price($original_price) {
        if (function_exists('nb_calculate_price_with_markup')) {
            return nb_calculate_price_with_markup($original_price);
        }
        
        // Fallback: apply default markup if function doesn't exist
        $markup_percentage = floatval(get_option('nb_markup_percentage', 0));
        return $original_price * (1 + ($markup_percentage / 100));
    }
}

/**
 * Legacy function wrapper for backward compatibility
 * 
 * @deprecated 1.0.0 Use NB_Product_Sync::delete_products_by_prefix() instead
 * @since 1.0.0
 * @param array  $existing_skus Array of SKUs that should be preserved
 * @param string $prefix       SKU prefix to filter products for deletion
 * @return array               Results array with deleted count and duration
 */
function nb_delete_products_by_prefix($existing_skus, $prefix) {
    return NB_Product_Sync::delete_products_by_prefix($existing_skus, $prefix);
}

/**
 * AJAX handler for updating product descriptions
 * 
 * Handles AJAX requests to update product descriptions from the admin panel.
 * Includes security checks, user capability verification, and error handling.
 * 
 * @since 1.0.0
 * @return void Sends JSON response and exits
 */
function nb_update_description_products() {
    // Verify nonce for security
    if (!check_ajax_referer('nb_update_description_all', 'nb_update_description_all_nonce', false)) {
        NB_Logger::error('Nonce verification failed for description update');
        wp_send_json_error(__('Error de seguridad: nonce inválido', 'newbytes-connector'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_woocommerce')) {
        NB_Logger::error('User without proper capabilities tried to update descriptions');
        wp_send_json_error(__('No tienes permisos para realizar esta acción', 'newbytes-connector'));
        return;
    }

    try {
        NB_Logger::info('Iniciando actualización de descripciones de productos');
        
        // Call the callback with syncDescription flag set to true
        $result = nb_callback(true);
        
        if (is_array($result) && isset($result['error'])) {
            NB_Logger::error('Error en callback de sincronización: ' . $result['error']);
            wp_send_json_error(__('Error al sincronizar las descripciones: ', 'newbytes-connector') . $result['error']);
            return;
        }
        
        if (is_string($result) && strpos($result, 'Error:') === 0) {
            NB_Logger::error('Error en callback de sincronización: ' . $result);
            wp_send_json_error(__('Error al sincronizar las descripciones: ', 'newbytes-connector') . $result);
            return;
        }

        // Update last update timestamp
        update_option('nb_last_update', current_time('Y-m-d H:i:s'));
        
        NB_Logger::info('Descripciones sincronizadas exitosamente');
        
        // Success response
        wp_send_json_success(array(
            'message' => __('Descripciones sincronizadas correctamente.', 'newbytes-connector'),
            'timestamp' => current_time('Y-m-d H:i:s'),
            'result' => $result
        ));
        
    } catch (Exception $e) {
        $error_msg = 'Error en nb_update_description_products: ' . $e->getMessage();
        NB_Logger::error($error_msg);
        wp_send_json_error(__('Error al sincronizar las descripciones: ', 'newbytes-connector') . $e->getMessage());
    }
}

/**
 * AJAX handler for deleting products
 * 
 * Handles AJAX requests to delete products by prefix from the admin panel.
 * Includes security checks, user capability verification, and error handling.
 * 
 * @since 1.0.0
 * @return void Sends JSON response and exits
 */
function nb_delete_products() {
    // Verify nonce for security
    if (!check_ajax_referer('nb_delete_products_nonce', 'nonce', false)) {
        NB_Logger::error('Nonce verification failed for product deletion');
        wp_send_json_error(__('Error de seguridad: nonce inválido', 'newbytes-connector'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_woocommerce')) {
        NB_Logger::error('User without proper capabilities tried to delete products');
        wp_send_json_error(__('No tienes permisos para realizar esta acción', 'newbytes-connector'));
        return;
    }

    try {
        $prefix = sanitize_text_field($_POST['prefix'] ?? '');
        
        if (empty($prefix)) {
            wp_send_json_error(__('Prefijo requerido para eliminar productos', 'newbytes-connector'));
            return;
        }
        
        NB_Logger::info('Iniciando eliminación de productos con prefijo: ' . $prefix);
        
        // Get existing SKUs from API or database
        $existing_skus = array(); // This should be populated from your API or database
        
        $result = NB_Product_Sync::delete_products_by_prefix($existing_skus, $prefix);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success(array(
                'message' => sprintf(__('Se eliminaron %d productos correctamente', 'newbytes-connector'), $result['deleted']),
                'deleted_count' => $result['deleted'],
                'duration' => $result['sync_duration']
            ));
        }
        
    } catch (Exception $e) {
        $error_msg = 'Error en nb_delete_products: ' . $e->getMessage();
        NB_Logger::error($error_msg);
        wp_send_json_error(__('Error al eliminar productos: ', 'newbytes-connector') . $e->getMessage());
    }
}

// Register AJAX actions
add_action('wp_ajax_nb_update_description_products', 'nb_update_description_products');
add_action('wp_ajax_nb_delete_products', 'nb_delete_products');
add_action('admin_post_nb_delete_products', 'nb_delete_products');

/**
 * Process product price with markup (legacy function)
 * 
 * @deprecated 1.0.0 Use NB_Product_Sync::process_product_price() instead
 * @since 1.0.0
 * @param array|float $product_data Product data array or price value
 * @return float                   Price with markup applied
 */
function nb_process_product_price($product_data) {
    if (is_array($product_data) && isset($product_data['price'])) {
        $original_price = $product_data['price'];
    } else {
        $original_price = floatval($product_data);
    }
    
    return NB_Product_Sync::process_product_price($original_price);
}

/**
 * Bulk sync products from API
 * 
 * Performs bulk synchronization of products with additional options
 * like deleting missing products and custom batch sizes.
 * 
 * @since 1.0.0
 * @param array $api_data Array of product data from API
 * @param array $options  Configuration options for sync operation
 * @return array          Results array with processing statistics
 */
function nb_bulk_sync_products($api_data, $options = array()) {
    $defaults = array(
        'sync_descriptions' => false,
        'delete_missing' => false,
        'prefix' => '',
        'batch_size' => 50
    );
    
    $options = wp_parse_args($options, $defaults);
    
    try {
        NB_Logger::info('Iniciando sincronización masiva de productos');
        
        // Sync products
        $sync_result = NB_Product_Sync::sync_products_from_api($api_data, $options['sync_descriptions']);
        
        if (isset($sync_result['error'])) {
            return $sync_result;
        }
        
        // Delete missing products if requested
        if ($options['delete_missing'] && !empty($options['prefix'])) {
            $existing_skus = array_column($api_data, 'sku');
            $delete_result = NB_Product_Sync::delete_products_by_prefix($existing_skus, $options['prefix']);
            
            if (isset($delete_result['error'])) {
                $sync_result['delete_error'] = $delete_result['error'];
            } else {
                $sync_result['deleted'] = $delete_result['deleted'];
            }
        }
        
        // Update last sync timestamp
        update_option('nb_last_update', current_time('Y-m-d H:i:s'));
        
        return $sync_result;
        
    } catch (Exception $e) {
        $error_msg = 'Error en sincronización masiva: ' . $e->getMessage();
        NB_Logger::error($error_msg);
        return array('error' => $error_msg);
    }
}

/**
 * Get sync statistics
 * 
 * Retrieves comprehensive statistics about product synchronization
 * including product counts, last sync time, and next scheduled sync.
 * 
 * @since 1.0.0
 * @return array Statistics array with sync information
 */
function nb_get_sync_stats() {
    global $wpdb;
    
    try {
        $stats = array(
            'total_products' => 0,
            'nb_products' => 0,
            'last_sync' => get_option('nb_last_update', ''),
            'sync_interval' => get_option('nb_sync_interval', 'hourly'),
            'next_sync' => ''
        );
        
        // Get total products count
        $stats['total_products'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'"
        );
        
        // Get NewBytes products count (assuming they have a specific prefix or meta)
        $nb_prefix = get_option('nb_sku_prefix', 'NB');
        $stats['nb_products'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) 
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'product'
                 AND p.post_status = 'publish'
                 AND pm.meta_key = '_sku'
                 AND pm.meta_value LIKE %s",
                $nb_prefix . '%'
            )
        );
        
        // Get next scheduled sync
        $next_sync_timestamp = wp_next_scheduled('nb_cron_sync_event');
        if ($next_sync_timestamp) {
            $stats['next_sync'] = date('Y-m-d H:i:s', $next_sync_timestamp);
        }
        
        return $stats;
        
    } catch (Exception $e) {
        NB_Logger::error('Error obteniendo estadísticas de sincronización: ' . $e->getMessage());
        return array('error' => $e->getMessage());
    }
}
