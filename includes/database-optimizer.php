<?php
/**
 * NewBytes WooCommerce Connector - Database Optimizer
 * 
 * @package NewBytes_WooCommerce_Connector
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Database Optimizer Class
 *
 * Provides optimized database operations for bulk product synchronization,
 * query optimization, caching, and performance improvements.
 *
 * @since 1.0.0
 */
class NB_Database_Optimizer {
    
    private static $instance = null;
    private $cache_group = 'newbytes_db';
    private $cache_duration = 300; // 5 minutes
    
    /**
     * Get singleton instance
     *
     * Returns the single instance of the database optimizer.
     *
     * @since 1.0.0
     * @return NB_Database_Optimizer The singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     *
     * Private constructor to enforce singleton pattern.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize optimizer
     *
     * Sets up database optimization hooks, filters, and configurations
     * for improved performance during bulk operations.
     *
     * @since 1.0.0
     * @return void
     */
    private function init() {
        // Set cache duration from options
        $this->cache_duration = get_option('nb_cache_duration', 300);
        
        // Add database optimization hooks
        add_action('init', array($this, 'optimize_database_settings'));
        add_action('nb_cron_cleanup_event', array($this, 'cleanup_database'));
        
        // Add query optimization filters
        add_filter('posts_clauses', array($this, 'optimize_product_queries'), 10, 2);
    }
    
    /**
     * Optimize database settings
     *
     * Configures MySQL settings for optimal performance during bulk operations.
     * Disables foreign key checks, unique checks, and autocommit for speed.
     *
     * @since 1.0.0
     * @return void
     */
    public function optimize_database_settings() {
        global $wpdb;
        
        // Only run in admin or during cron
        if (!is_admin() && !wp_doing_cron()) {
            return;
        }
        
        // Set optimal MySQL settings for bulk operations
        if (defined('NB_DOING_BULK_SYNC') && NB_DOING_BULK_SYNC) {
            $wpdb->query("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
            $wpdb->query("SET SESSION foreign_key_checks = 0");
            $wpdb->query("SET SESSION unique_checks = 0");
            $wpdb->query("SET SESSION autocommit = 0");
        }
    }
    
    /**
     * Get products with optimized query
     *
     * Retrieves products using optimized SQL queries with caching support.
     * Includes options for filtering, pagination, and metadata inclusion.
     *
     * @since 1.0.0
     * @param array $args {
     *     Query arguments for product retrieval.
     *     @type int    $limit        Maximum number of products to retrieve (default: 50)
     *     @type int    $offset       Number of products to skip (default: 0)
     *     @type string $sku_prefix   SKU prefix filter (default: '')
     *     @type string $status       Post status filter (default: 'publish')
     *     @type bool   $include_meta Whether to include product metadata (default: true)
     *     @type bool   $cache        Whether to use caching (default: true)
     * }
     * @return array|object Array of product objects or empty array on failure
     */
    public function get_products_optimized($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'sku_prefix' => '',
            'status' => 'publish',
            'include_meta' => true,
            'cache' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Create cache key
        $cache_key = 'nb_products_' . md5(serialize($args));
        
        // Try to get from cache first
        if ($args['cache']) {
            $cached_result = wp_cache_get($cache_key, $this->cache_group);
            if ($cached_result !== false) {
                return $cached_result;
            }
        }
        
        // Build optimized query
        $sql = "SELECT DISTINCT p.ID, p.post_title, p.post_status, p.post_date";
        
        if ($args['include_meta']) {
            $sql .= ", 
                GROUP_CONCAT(
                    CASE WHEN pm.meta_key = '_sku' THEN pm.meta_value END
                ) as sku,
                GROUP_CONCAT(
                    CASE WHEN pm.meta_key = '_price' THEN pm.meta_value END
                ) as price,
                GROUP_CONCAT(
                    CASE WHEN pm.meta_key = '_stock' THEN pm.meta_value END
                ) as stock";
        }
        
        $sql .= " FROM {$wpdb->posts} p";
        
        if ($args['include_meta'] || !empty($args['sku_prefix'])) {
            $sql .= " INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id";
        }
        
        $sql .= " WHERE p.post_type = 'product'";
        
        if (!empty($args['status'])) {
            $sql .= $wpdb->prepare(" AND p.post_status = %s", $args['status']);
        }
        
        if (!empty($args['sku_prefix'])) {
            $sql .= $wpdb->prepare(
                " AND pm.meta_key = '_sku' AND pm.meta_value LIKE %s",
                $args['sku_prefix'] . '%'
            );
        }
        
        if ($args['include_meta']) {
            $sql .= " AND pm.meta_key IN ('_sku', '_price', '_stock')";
            $sql .= " GROUP BY p.ID";
        }
        
        $sql .= " ORDER BY p.post_date DESC";
        
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }
        
        $results = $wpdb->get_results($sql);
        
        // Cache the results
        if ($args['cache'] && !empty($results)) {
            wp_cache_set($cache_key, $results, $this->cache_group, $this->cache_duration);
        }
        
        return $results;
    }
    
    /**
     * Bulk insert products with optimized performance
     *
     * Inserts multiple products in a single transaction using optimized
     * bulk SQL operations for maximum performance.
     *
     * @since 1.0.0
     * @param array $products_data Array of product data arrays, each containing:
     *                            - name (required): Product name
     *                            - sku (required): Product SKU
     *                            - price: Product price
     *                            - stock: Stock quantity
     *                            - description: Product description
     *                            - weight, length, width, height: Dimensions
     * @return array {
     *     Result array with operation details.
     *     @type bool   $success    Whether the operation succeeded
     *     @type int    $inserted   Number of products inserted (on success)
     *     @type float  $duration   Operation duration in seconds (on success)
     *     @type array  $errors     Array of error messages
     *     @type array  $post_ids   Array of inserted post IDs (on success)
     *     @type string $error      Main error message (on failure)
     * }
     */
    public function bulk_insert_products($products_data) {
        global $wpdb;
        
        if (empty($products_data) || !is_array($products_data)) {
            return array('success' => false, 'error' => 'No product data provided');
        }
        
        $start_time = microtime(true);
        $inserted_count = 0;
        $errors = array();
        
        // Define bulk sync mode
        if (!defined('NB_DOING_BULK_SYNC')) {
            define('NB_DOING_BULK_SYNC', true);
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Prepare bulk insert for posts
            $posts_values = array();
            $posts_placeholders = array();
            $meta_values = array();
            $meta_placeholders = array();
            
            foreach ($products_data as $index => $product) {
                // Validate required fields
                if (empty($product['name']) || empty($product['sku'])) {
                    $errors[] = "Product at index {$index}: Missing required fields (name or sku)";
                    continue;
                }
                
                // Prepare post data
                $post_date = current_time('mysql');
                $post_name = sanitize_title($product['name']);
                
                $posts_values = array_merge($posts_values, array(
                    $post_date,
                    $post_date,
                    $product['name'],
                    $post_name,
                    $product['description'] ?? '',
                    'publish',
                    'open',
                    'closed',
                    '',
                    $post_name,
                    '',
                    '',
                    $post_date,
                    $post_date,
                    '',
                    0,
                    '',
                    0,
                    'product',
                    '',
                    0
                ));
                
                $posts_placeholders[] = "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %d, %s, %s, %d)";
            }
            
            // Execute bulk insert for posts
            if (!empty($posts_values)) {
                $posts_sql = "INSERT INTO {$wpdb->posts} 
                    (post_date, post_date_gmt, post_title, post_name, post_content, 
                     post_status, comment_status, ping_status, post_password, post_name, 
                     to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, 
                     post_parent, guid, menu_order, post_type, post_mime_type, comment_count) 
                    VALUES " . implode(', ', $posts_placeholders);
                
                $result = $wpdb->query($wpdb->prepare($posts_sql, $posts_values));
                
                if ($result === false) {
                    throw new Exception('Failed to insert posts: ' . $wpdb->last_error);
                }
                
                $inserted_count = $wpdb->rows_affected;
            }
            
            // Get inserted post IDs
            $first_inserted_id = $wpdb->insert_id;
            $post_ids = range($first_inserted_id, $first_inserted_id + $inserted_count - 1);
            
            // Prepare bulk insert for post meta
            foreach ($products_data as $index => $product) {
                if (!isset($post_ids[$index])) {
                    continue;
                }
                
                $post_id = $post_ids[$index];
                
                // Essential meta fields
                $meta_fields = array(
                    '_sku' => $product['sku'],
                    '_price' => $product['price'] ?? 0,
                    '_regular_price' => $product['price'] ?? 0,
                    '_stock' => $product['stock'] ?? 0,
                    '_manage_stock' => 'yes',
                    '_stock_status' => ($product['stock'] ?? 0) > 0 ? 'instock' : 'outofstock',
                    '_visibility' => 'visible',
                    '_featured' => 'no',
                    '_virtual' => 'no',
                    '_downloadable' => 'no',
                    '_sold_individually' => 'no',
                    '_weight' => $product['weight'] ?? '',
                    '_length' => $product['length'] ?? '',
                    '_width' => $product['width'] ?? '',
                    '_height' => $product['height'] ?? ''
                );
                
                foreach ($meta_fields as $meta_key => $meta_value) {
                    $meta_values = array_merge($meta_values, array($post_id, $meta_key, $meta_value));
                    $meta_placeholders[] = "(%d, %s, %s)";
                }
            }
            
            // Execute bulk insert for meta
            if (!empty($meta_values)) {
                $meta_sql = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " 
                          . implode(', ', $meta_placeholders);
                
                $result = $wpdb->query($wpdb->prepare($meta_sql, $meta_values));
                
                if ($result === false) {
                    throw new Exception('Failed to insert post meta: ' . $wpdb->last_error);
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Clear relevant caches
            $this->clear_product_caches();
            
            $duration = microtime(true) - $start_time;
            
            return array(
                'success' => true,
                'inserted' => $inserted_count,
                'duration' => $duration,
                'errors' => $errors,
                'post_ids' => $post_ids
            );
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            
            nb_log_error('database', 'Bulk insert failed: ' . $e->getMessage(), array(
                'products_count' => count($products_data),
                'errors' => $errors
            ));
            
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $errors
            );
        }
    }
    
    /**
     * Bulk update products with optimized performance
     *
     * Updates multiple products in a single transaction using optimized
     * SQL operations. Supports updating by product ID or SKU.
     *
     * @since 1.0.0
     * @param array $updates_data Array of update data arrays, each containing:
     *                           - id or sku (required): Product identifier
     *                           - name: New product name
     *                           - description: New product description
     *                           - price: New product price
     *                           - stock: New stock quantity
     *                           - Other metadata fields
     * @return array {
     *     Result array with operation details.
     *     @type bool   $success   Whether the operation succeeded
     *     @type int    $updated   Number of products updated (on success)
     *     @type float  $duration  Operation duration in seconds (on success)
     *     @type array  $errors    Array of error messages
     *     @type string $error     Main error message (on failure)
     * }
     */
    public function bulk_update_products($updates_data) {
        global $wpdb;
        
        if (empty($updates_data) || !is_array($updates_data)) {
            return array('success' => false, 'error' => 'No update data provided');
        }
        
        $start_time = microtime(true);
        $updated_count = 0;
        $errors = array();
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($updates_data as $update) {
                if (empty($update['id']) && empty($update['sku'])) {
                    $errors[] = 'Missing product ID or SKU for update';
                    continue;
                }
                
                // Find product ID if only SKU is provided
                if (empty($update['id']) && !empty($update['sku'])) {
                    $product_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
                        $update['sku']
                    ));
                    
                    if (!$product_id) {
                        $errors[] = 'Product not found with SKU: ' . $update['sku'];
                        continue;
                    }
                    
                    $update['id'] = $product_id;
                }
                
                // Update post data if provided
                if (isset($update['name']) || isset($update['description'])) {
                    $post_data = array();
                    $post_formats = array();
                    
                    if (isset($update['name'])) {
                        $post_data['post_title'] = $update['name'];
                        $post_data['post_name'] = sanitize_title($update['name']);
                        $post_formats[] = '%s';
                        $post_formats[] = '%s';
                    }
                    
                    if (isset($update['description'])) {
                        $post_data['post_content'] = $update['description'];
                        $post_formats[] = '%s';
                    }
                    
                    if (!empty($post_data)) {
                        $post_data['post_modified'] = current_time('mysql');
                        $post_data['post_modified_gmt'] = current_time('mysql', 1);
                        $post_formats[] = '%s';
                        $post_formats[] = '%s';
                        
                        $result = $wpdb->update(
                            $wpdb->posts,
                            $post_data,
                            array('ID' => $update['id']),
                            $post_formats,
                            array('%d')
                        );
                        
                        if ($result === false) {
                            $errors[] = 'Failed to update post for ID: ' . $update['id'];
                            continue;
                        }
                    }
                }
                
                // Update meta data
                $meta_updates = array(
                    '_price' => $update['price'] ?? null,
                    '_regular_price' => $update['price'] ?? null,
                    '_stock' => $update['stock'] ?? null,
                    '_weight' => $update['weight'] ?? null,
                    '_length' => $update['length'] ?? null,
                    '_width' => $update['width'] ?? null,
                    '_height' => $update['height'] ?? null
                );
                
                foreach ($meta_updates as $meta_key => $meta_value) {
                    if ($meta_value !== null) {
                        $existing = $wpdb->get_var($wpdb->prepare(
                            "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
                            $update['id'],
                            $meta_key
                        ));
                        
                        if ($existing) {
                            $wpdb->update(
                                $wpdb->postmeta,
                                array('meta_value' => $meta_value),
                                array('post_id' => $update['id'], 'meta_key' => $meta_key),
                                array('%s'),
                                array('%d', '%s')
                            );
                        } else {
                            $wpdb->insert(
                                $wpdb->postmeta,
                                array(
                                    'post_id' => $update['id'],
                                    'meta_key' => $meta_key,
                                    'meta_value' => $meta_value
                                ),
                                array('%d', '%s', '%s')
                            );
                        }
                    }
                }
                
                // Update stock status based on stock quantity
                if (isset($update['stock'])) {
                    $stock_status = intval($update['stock']) > 0 ? 'instock' : 'outofstock';
                    $wpdb->query($wpdb->prepare(
                        "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) 
                         VALUES (%d, '_stock_status', %s) 
                         ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
                        $update['id'],
                        $stock_status
                    ));
                }
                
                $updated_count++;
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Clear relevant caches
            $this->clear_product_caches();
            
            $duration = microtime(true) - $start_time;
            
            return array(
                'success' => true,
                'updated' => $updated_count,
                'duration' => $duration,
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            
            nb_log_error('database', 'Bulk update failed: ' . $e->getMessage(), array(
                'updates_count' => count($updates_data),
                'errors' => $errors
            ));
            
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $errors
            );
        }
    }
    
    /**
     * Optimize product queries
     *
     * Modifies WooCommerce product queries to use database indexes
     * and improve performance for large product catalogs.
     *
     * @since 1.0.0
     * @param array $clauses Query clauses from WordPress
     * @param WP_Query $query The WP_Query instance
     * @return array Modified query clauses with optimization hints
     */
    public function optimize_product_queries($clauses, $query) {
        global $wpdb;
        
        // Only optimize product queries
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product') {
            return $clauses;
        }
        
        // Add index hints for better performance
        if (strpos($clauses['join'], $wpdb->postmeta) !== false) {
            $clauses['join'] = str_replace(
                "INNER JOIN {$wpdb->postmeta}",
                "INNER JOIN {$wpdb->postmeta} USE INDEX (post_id, meta_key)",
                $clauses['join']
            );
        }
        
        return $clauses;
    }
    
    /**
     * Get database statistics
     */
    public function get_database_stats() {
        global $wpdb;
        
        $stats = array(
            'total_products' => 0,
            'nb_products' => 0,
            'database_size' => 0,
            'table_sizes' => array(),
            'index_usage' => array(),
            'slow_queries' => array()
        );
        
        // Get product counts
        $stats['total_products'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
            'product'
        ));
        
        $nb_prefix = get_option('nb_sku_prefix', 'NB');
        $stats['nb_products'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'product'
             AND pm.meta_key = '_sku'
             AND pm.meta_value LIKE %s",
            $nb_prefix . '%'
        ));
        
        // Get table sizes
        $tables = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->prefix . 'nb_sync_logs',
            $wpdb->prefix . 'nb_error_logs',
            $wpdb->prefix . 'nb_security_logs'
        );
        
        foreach ($tables as $table) {
            $size = $wpdb->get_var($wpdb->prepare(
                "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
                 FROM information_schema.TABLES 
                 WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            $stats['table_sizes'][basename($table)] = $size ? $size . ' MB' : 'N/A';
            $stats['database_size'] += floatval($size);
        }
        
        $stats['database_size'] = round($stats['database_size'], 2) . ' MB';
        
        return $stats;
    }
    
    /**
     * Clear product caches
     */
    public function clear_product_caches() {
        // Clear WordPress object cache
        wp_cache_flush_group($this->cache_group);
        
        // Clear WooCommerce caches
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients();
        }
        
        // Clear our custom transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_nb_%' 
             OR option_name LIKE '_transient_timeout_nb_%'"
        );
    }
    
    /**
     * Cleanup database
     */
    public function cleanup_database() {
        global $wpdb;
        
        $cleanup_results = array();
        
        // Clean up old sync logs (older than 30 days)
        $sync_logs_table = $wpdb->prefix . 'nb_sync_logs';
        $deleted_sync = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$sync_logs_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            30
        ));
        $cleanup_results['sync_logs_deleted'] = $deleted_sync;
        
        // Clean up old error logs (older than 60 days)
        $error_logs_table = $wpdb->prefix . 'nb_error_logs';
        $deleted_errors = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$error_logs_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            60
        ));
        $cleanup_results['error_logs_deleted'] = $deleted_errors;
        
        // Clean up old security logs (older than 90 days)
        $security_logs_table = $wpdb->prefix . 'nb_security_logs';
        $deleted_security = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$security_logs_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            90
        ));
        $cleanup_results['security_logs_deleted'] = $deleted_security;
        
        // Optimize tables
        $tables_to_optimize = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $sync_logs_table,
            $error_logs_table,
            $security_logs_table
        );
        
        foreach ($tables_to_optimize as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        // Clear expired transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_%' 
             AND option_name NOT LIKE '_transient_timeout_%' 
             AND option_name NOT IN (
                 SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_timeout_%'
             )"
        );
        
        NB_Logger::info('Database cleanup completed', $cleanup_results);
        
        return $cleanup_results;
    }
    
    /**
     * Create database indexes for better performance
     */
    public function create_performance_indexes() {
        global $wpdb;
        
        $indexes = array(
            // Posts table indexes
            "CREATE INDEX IF NOT EXISTS idx_post_type_status_date ON {$wpdb->posts} (post_type, post_status, post_date)",
            "CREATE INDEX IF NOT EXISTS idx_post_name_type ON {$wpdb->posts} (post_name, post_type)",
            
            // Postmeta table indexes
            "CREATE INDEX IF NOT EXISTS idx_meta_key_value ON {$wpdb->postmeta} (meta_key, meta_value(20))",
            "CREATE INDEX IF NOT EXISTS idx_post_meta_key ON {$wpdb->postmeta} (post_id, meta_key)",
            
            // Custom tables indexes
            "CREATE INDEX IF NOT EXISTS idx_sync_timestamp_type ON {$wpdb->prefix}nb_sync_logs (timestamp, sync_type)",
            "CREATE INDEX IF NOT EXISTS idx_error_timestamp_type ON {$wpdb->prefix}nb_error_logs (timestamp, error_type)",
            "CREATE INDEX IF NOT EXISTS idx_security_timestamp_type ON {$wpdb->prefix}nb_security_logs (timestamp, event_type)"
        );
        
        foreach ($indexes as $index_sql) {
            $wpdb->query($index_sql);
        }
    }
}

// Initialize database optimizer
NB_Database_Optimizer::get_instance();

/**
 * Helper functions for database operations
 */

/**
 * Get optimized product data
 */
function nb_get_products_optimized($args = array()) {
    $optimizer = NB_Database_Optimizer::get_instance();
    return $optimizer->get_products_optimized($args);
}

/**
 * Bulk insert products with optimization
 */
function nb_bulk_insert_products($products_data) {
    $optimizer = NB_Database_Optimizer::get_instance();
    return $optimizer->bulk_insert_products($products_data);
}

/**
 * Bulk update products with optimization
 */
function nb_bulk_update_products($updates_data) {
    $optimizer = NB_Database_Optimizer::get_instance();
    return $optimizer->bulk_update_products($updates_data);
}

/**
 * Get database statistics
 */
function nb_get_database_stats() {
    $optimizer = NB_Database_Optimizer::get_instance();
    return $optimizer->get_database_stats();
}

/**
 * Clear product caches
 */
function nb_clear_product_caches() {
    $optimizer = NB_Database_Optimizer::get_instance();
    return $optimizer->clear_product_caches();
}