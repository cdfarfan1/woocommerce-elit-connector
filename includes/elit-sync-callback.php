<?php
/**
 * ELIT synchronization callback functions
 * 
 * @package ELIT_Connector
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main ELIT synchronization callback function
 * 
 * Synchronizes products from ELIT API to WooCommerce, replacing the
 * original NewBytes sync functionality.
 * 
 * @since 1.0.0
 * @param bool $syncDescription Whether to sync product descriptions
 * @return mixed Success message or error details
 */
function elit_callback($syncDescription = false) {
    try {
        error_log('elit_callback ejecutado a las: ' . date('Y-m-d H:i:s'));

        // Guardar límites originales
        $original_max_execution_time = ini_get('max_execution_time');
        $original_memory_limit = ini_get('memory_limit');

        // Establecer límites ultra-conservadores para servidores estrictos
        ini_set('max_execution_time', '60'); // 1 minuto máximo
        ini_set('memory_limit', '128M'); // 128MB máximo

        $start_time = microtime(true);

        // Verificar que las credenciales de ELIT estén configuradas
        $user_id = get_option('elit_user_id');
        $token = get_option('elit_token');
        
        if (empty($user_id) || empty($token)) {
            NB_Logger::error('Credenciales de ELIT no configuradas');
            return 'Error: Credenciales de ELIT no configuradas. Configura User ID y Token en los ajustes.';
        }

        NB_Logger::info('Iniciando sincronización con ELIT API');

        // Obtener productos de ELIT
        // Procesar de 100 en 100 según límite de API ELIT
        $offset = get_option('elit_sync_offset', 0);
        $limit = 100; // Límite máximo de ELIT API
        
        // Intentar obtener productos de ELIT API
        $elit_products = ELIT_API_Manager::get_products_batch($offset, $limit);
        
        // Si no hay productos de ELIT API, usar modo demo
        if (empty($elit_products)) {
            NB_Logger::info('API ELIT no disponible, usando modo DEMO');
            $elit_products = ELIT_Demo_API::get_products_batch($offset, $limit);
            
            if (empty($elit_products)) {
                // Si no hay más productos demo, reiniciar el offset
                update_option('elit_sync_offset', 0);
                NB_Logger::info('Sincronización DEMO completada. Reiniciando offset.');
                return 'Sincronización DEMO completada.';
            }
        }
        
        // Actualizar offset para la próxima ejecución
        update_option('elit_sync_offset', $offset + $limit);
        
        NB_Logger::info('Procesando lote ' . ($offset / $limit + 1) . ' - Productos: ' . count($elit_products));

        // Transformar productos de ELIT al formato WooCommerce
        $transformed_products = array();
        foreach ($elit_products as $elit_product) {
            // Usar transformación de API o Demo según corresponda
            if (isset($elit_product['id']) && $elit_product['id'] > 1000) {
                // Es un producto demo
                $transformed = ELIT_Demo_API::transform_product_data($elit_product);
            } else {
                // Es un producto real de ELIT API
                $transformed = ELIT_API_Manager::transform_product_data($elit_product);
            }
            
            if ($transformed) {
                $transformed_products[] = $transformed;
            }
        }

        if (empty($transformed_products)) {
            NB_Logger::warning('No se pudieron transformar los productos de ELIT');
            return 'Error: No se pudieron procesar los productos de ELIT.';
        }

        // Obtener SKUs existentes para eliminación
        $prefix = get_option('elit_sku_prefix', 'ELIT_');
        $existing_skus = array();

        foreach ($transformed_products as $product) {
            if (!empty($product['sku'])) {
                $existing_skus[] = $product['sku'];
            }
        }

        // Eliminar productos que no están en la respuesta de ELIT
        NB_Logger::info('Eliminando productos obsoletos con prefijo: ' . $prefix);
        $delete_result = NB_Product_Sync::delete_products_by_prefix($existing_skus, $prefix);

        $sync_stats = array(
            'updated_count' => 0,
            'created_count' => 0,
            'deleted_count' => isset($delete_result['deleted']) ? $delete_result['deleted'] : 0,
            'errors' => array()
        );

        // Verificar tiempo transcurrido antes de sincronización (ultra estricto)
        $elapsed_time = microtime(true) - $start_time;
        if ($elapsed_time > 30) { // 30 segundos
            NB_Logger::warning('Tiempo límite alcanzado antes de sincronización. Procesando solo productos obtenidos.');
            $transformed_products = array_slice($transformed_products, 0, 10); // Limitar a 10 productos
        }
        
        // Sincronizar productos usando la clase existente de sincronización
        NB_Logger::info('Iniciando sincronización de ' . count($transformed_products) . ' productos transformados');
        $sync_result = NB_Product_Sync::sync_products_from_api($transformed_products, $syncDescription);

        if (isset($sync_result['error'])) {
            NB_Logger::error('Error en sincronización: ' . $sync_result['error']);
            return 'Error en sincronización: ' . $sync_result['error'];
        }

        // Actualizar estadísticas
        $sync_stats['created_count'] = $sync_result['created'] ?? 0;
        $sync_stats['updated_count'] = $sync_result['updated'] ?? 0;
        $sync_stats['processed'] = $sync_result['processed'] ?? 0;

        // Calcular duración total
        $total_duration = microtime(true) - $start_time;
        $duration_formatted = format_sync_duration($total_duration);

        // Actualizar timestamp de última sincronización
        update_option('elit_last_update', current_time('Y-m-d H:i:s'));
        
        // Restaurar límites originales
        ini_set('max_execution_time', $original_max_execution_time);
        ini_set('memory_limit', $original_memory_limit);

        $success_message = sprintf(
            'Sincronización ELIT completada exitosamente:<br/>' .
            '• Productos procesados: %d<br/>' .
            '• Productos creados: %d<br/>' .
            '• Productos actualizados: %d<br/>' .
            '• Productos eliminados: %d<br/>' .
            '• Duración: %s',
            $sync_stats['processed'],
            $sync_stats['created_count'],
            $sync_stats['updated_count'],
            $sync_stats['deleted_count'],
            $duration_formatted
        );

        NB_Logger::info('Sincronización ELIT completada: ' . strip_tags($success_message));
        
        return $success_message;

    } catch (Exception $e) {
        $error_msg = 'Error en sincronización ELIT: ' . $e->getMessage();
        NB_Logger::error($error_msg);
        
        // Restaurar límites en caso de error
        if (isset($original_max_execution_time)) {
            ini_set('max_execution_time', $original_max_execution_time);
        }
        if (isset($original_memory_limit)) {
            ini_set('memory_limit', $original_memory_limit);
        }
        
        return $error_msg;
    }
}

/**
 * Format sync duration into readable format
 * 
 * @since 1.0.0
 * @param float $duration Duration in seconds
 * @return string Formatted duration string
 */
function format_sync_duration($duration) {
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;
    
    $parts = array();
    
    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0) {
        $parts[] = $minutes . 'm';
    }
    if ($seconds > 0 || empty($parts)) {
        $parts[] = number_format($seconds, 1) . 's';
    }
    
    return implode(' ', $parts);
}

/**
 * Test ELIT connection via AJAX
 * 
 * AJAX handler to test the connection with ELIT API.
 * 
 * @since 1.0.0
 * @return void
 */
function ajax_test_elit_connection() {
    // Verify nonce for security
    if (!check_ajax_referer('nb_admin_nonce', 'nonce', false)) {
        wp_send_json_error('Error de seguridad: nonce inválido');
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para realizar esta acción');
        return;
    }

    try {
        $test_result = ELIT_API_Manager::test_connection();
        
        if ($test_result['success']) {
            wp_send_json_success(array(
                'message' => $test_result['message']
            ));
        } else {
            wp_send_json_error($test_result['message']);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error al probar conexión: ' . $e->getMessage());
    }
}

/**
 * Get ELIT sync statistics
 * 
 * Returns statistics about ELIT product synchronization.
 * 
 * @since 1.0.0
 * @return array Statistics array
 */
function get_elit_sync_stats() {
    global $wpdb;
    
    try {
        $stats = array(
            'total_products' => 0,
            'elit_products' => 0,
            'last_sync' => get_option('nb_last_update', ''),
            'sync_interval' => get_option('nb_sync_interval', 'hourly'),
            'next_sync' => ''
        );
        
        // Get total products count
        $stats['total_products'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'"
        );
        
        // Get ELIT products count
        $elit_prefix = get_option('elit_sku_prefix', 'ELIT_');
        $stats['elit_products'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) 
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'product'
                 AND p.post_status = 'publish'
                 AND pm.meta_key = '_sku'
                 AND pm.meta_value LIKE %s",
                $elit_prefix . '%'
            )
        );
        
        // Get next scheduled sync
        $next_sync_timestamp = wp_next_scheduled('nb_cron_sync_event');
        if ($next_sync_timestamp) {
            $stats['next_sync'] = date('Y-m-d H:i:s', $next_sync_timestamp);
        }
        
        return $stats;
        
    } catch (Exception $e) {
        NB_Logger::error('Error obteniendo estadísticas de ELIT: ' . $e->getMessage());
        return array('error' => $e->getMessage());
    }
}

// Register AJAX actions
add_action('wp_ajax_test_elit_connection', 'ajax_test_elit_connection');
