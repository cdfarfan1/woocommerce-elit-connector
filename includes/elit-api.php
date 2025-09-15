<?php
/**
 * ELIT API integration functions
 * 
 * @package ELIT_Connector
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * ELIT API Manager Class
 * 
 * Handles all interactions with ELIT API including authentication,
 * product fetching, and data processing.
 * 
 * @since 1.0.0
 * @package ELIT_Connector
 */
class ELIT_API_Manager {
    
    /**
     * ELIT API base URL
     * 
     * @var string
     * @since 1.0.0
     */
    private static $api_url = 'https://clientes.elit.com.ar/v1/api';
    
    /**
     * ELIT CSV API URL (working endpoint)
     * 
     * @var string
     * @since 1.0.0
     */
    private static $csv_api_url = 'https://clientes.elit.com.ar/v1/api/productos/csv';
    
    /**
     * Maximum products per API request
     * 
     * @var int
     * @since 1.0.0
     */
    private static $max_limit = 100; // ELIT API limit
    
    /**
     * Get authentication credentials
     * 
     * Retrieves ELIT API credentials from WordPress options.
     * 
     * @since 1.0.0
     * @return array|null Array with user_id and token, or null if not configured
     */
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
    
    /**
     * Make API request to ELIT
     * 
     * Performs authenticated requests to ELIT API with proper error handling.
     * 
     * @since 1.0.0
     * @param string $endpoint API endpoint (without base URL)
     * @param array  $params   Additional parameters for the request
     * @return array|null      API response data or null on failure
     */
    public static function make_request($endpoint, $params = array()) {
        $credentials = self::get_credentials();
        if (!$credentials) {
            return null;
        }
        
        try {
            // Prepare request body
            $body_data = array_merge($credentials, $params);
            
            $args = array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'ELIT-WooCommerce-Connector/1.0.0'
                ),
                'body' => json_encode($body_data),
                'timeout' => 60,
                'blocking' => true,
            );
            
            $url = self::$api_url . '/' . ltrim($endpoint, '/');
            NB_Logger::info('Realizando petición a ELIT: ' . $url);
            
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                $error_msg = 'Error en petición a ELIT: ' . $response->get_error_message();
                NB_Logger::error($error_msg);
                return null;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code >= 400) {
                $error_msg = 'Error HTTP ' . $response_code . ' en petición a ELIT';
                NB_Logger::error($error_msg);
                return null;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error_msg = 'Error al decodificar JSON de ELIT: ' . json_last_error_msg();
                NB_Logger::error($error_msg);
                return null;
            }
            
            NB_Logger::info('Respuesta exitosa de ELIT API');
            return $data;
            
        } catch (Exception $e) {
            $error_msg = 'Excepción en petición a ELIT: ' . $e->getMessage();
            NB_Logger::error($error_msg);
            return null;
        }
    }
    
    /**
     * Get all products from ELIT API
     * 
     * Fetches all products from ELIT API with pagination support.
     * According to ELIT documentation, the endpoint is productos with limit and offset parameters.
     * 
     * @since 1.0.0
     * @param int $limit Number of products per request (max 100)
     * @return array     Array of all products or empty array on failure
     */
    public static function get_all_products($limit = null) {
        if (is_null($limit)) {
            $limit = self::$max_limit;
        }
        
        $limit = min($limit, self::$max_limit);
        $all_products = array();
        $offset = 0;
        $has_more = true;
        
        NB_Logger::info('Iniciando obtención de productos de ELIT');
        
        while ($has_more) {
            // Build URL with query parameters as per ELIT documentation
            $endpoint = 'productos?limit=' . $limit . '&offset=' . $offset;
            
            $response = self::make_request($endpoint);
            
            if (!$response) {
                NB_Logger::error('Error obteniendo productos de ELIT en offset: ' . $offset);
                break;
            }
            
            // ELIT returns structured response with 'resultado' containing the products array
            $products = array();
            if (is_array($response) && isset($response['resultado']) && is_array($response['resultado'])) {
                $products = $response['resultado'];
            } elseif (is_array($response)) {
                // Fallback for direct array response
                $products = $response;
            }
            
            if (empty($products)) {
                $has_more = false;
                break;
            }
            
            $all_products = array_merge($all_products, $products);
            
            // Si obtuvimos menos productos que el límite, no hay más páginas
            if (count($products) < $limit) {
                $has_more = false;
            } else {
                $offset += $limit;
            }
            
            NB_Logger::info('Obtenidos ' . count($products) . ' productos de ELIT (total: ' . count($all_products) . ')');
        }
        
        NB_Logger::info('Total de productos obtenidos de ELIT: ' . count($all_products));
        return $all_products;
    }
    
    /**
     * Transform ELIT product data to WooCommerce format
     * 
     * Converts ELIT product data structure to match the format
     * expected by the existing sync functions. Uses actual ELIT API fields.
     * 
     * @since 1.0.0
     * @param array $elit_product Raw product data from ELIT API
     * @return array              Formatted product data for WooCommerce
     */
    public static function transform_product_data($elit_product) {
        if (!is_array($elit_product)) {
            return null;
        }
        
        // Map ELIT fields to WooCommerce format using exact API fields
        $transformed = array(
            // Core WooCommerce fields
            'sku' => self::get_field_value($elit_product, array('codigo_producto')),
            'name' => self::get_field_value($elit_product, array('nombre')),
            'short_description' => self::build_short_description($elit_product),
            'price' => self::get_elit_price($elit_product),
            'stock_quantity' => self::get_elit_stock($elit_product),
            'stock_status' => self::get_elit_stock_status($elit_product),
            'categories' => self::get_elit_categories($elit_product),
            'images' => self::get_elit_images($elit_product),
            'weight' => self::get_field_value($elit_product, array('peso'), 0),
            'brand' => self::get_field_value($elit_product, array('marca')),
            'warranty' => self::get_field_value($elit_product, array('garantia')),
            'ean' => self::get_field_value($elit_product, array('ean')),
            'is_gamer' => self::get_field_value($elit_product, array('gamer'), false),
            'attributes' => self::get_field_value($elit_product, array('atributos'), array()),
            
            // ELIT specific metadata - using exact API field names
            'meta_data' => array(
                'elit_id' => self::get_field_value($elit_product, array('id')),
                'elit_codigo_alfa' => self::get_field_value($elit_product, array('codigo_alfa')),
                'elit_codigo_producto' => self::get_field_value($elit_product, array('codigo_producto')),
                'elit_categoria' => self::get_field_value($elit_product, array('categoria')),
                'elit_sub_categoria' => self::get_field_value($elit_product, array('sub_categoria')),
                'elit_marca' => self::get_field_value($elit_product, array('marca')),
                'elit_precio' => self::get_field_value($elit_product, array('precio'), 0),
                'elit_impuesto_interno' => self::get_field_value($elit_product, array('impuesto_interno'), 0),
                'elit_iva' => self::get_field_value($elit_product, array('iva'), 0),
                'elit_moneda' => self::get_field_value($elit_product, array('moneda')),
                'elit_markup' => self::get_field_value($elit_product, array('markup'), 0),
                'elit_cotizacion' => self::get_field_value($elit_product, array('cotizacion'), 0),
                'elit_pvp_usd' => self::get_field_value($elit_product, array('pvp_usd'), 0),
                'elit_pvp_ars' => self::get_field_value($elit_product, array('pvp_ars'), 0),
                'elit_peso' => self::get_field_value($elit_product, array('peso'), 0),
                'elit_ean' => self::get_field_value($elit_product, array('ean')),
                'elit_nivel_stock' => self::get_field_value($elit_product, array('nivel_stock')),
                'elit_stock_total' => self::get_field_value($elit_product, array('stock_total'), 0),
                'elit_stock_deposito_cliente' => self::get_field_value($elit_product, array('stock_deposito_cliente'), 0),
                'elit_stock_deposito_cd' => self::get_field_value($elit_product, array('stock_deposito_cd'), 0),
                'elit_garantia' => self::get_field_value($elit_product, array('garantia')),
                'elit_link' => self::get_field_value($elit_product, array('link')),
                'elit_imagenes' => self::get_field_value($elit_product, array('imagenes'), array()),
                'elit_miniaturas' => self::get_field_value($elit_product, array('miniaturas'), array()),
                'elit_atributos' => self::get_field_value($elit_product, array('atributos'), array()),
                'elit_gamer' => self::get_field_value($elit_product, array('gamer'), false),
                'elit_creado' => self::get_field_value($elit_product, array('creado')),
                'elit_actualizado' => self::get_field_value($elit_product, array('actualizado'))
            )
        );
        
        // Remove empty short description override since we have a dedicated function
        // Short description is built in build_short_description() function
        
        // Add prefix to SKU if configured
        $prefix = get_option('elit_sku_prefix', 'ELIT_');
        if (!empty($prefix) && !empty($transformed['sku'])) {
            $transformed['sku'] = $prefix . $transformed['sku'];
        }
        
        return $transformed;
    }
    
    /**
     * Get field value with fallbacks
     * 
     * Attempts to get a field value from multiple possible field names.
     * 
     * @since 1.0.0
     * @param array $data         Source data array
     * @param array $field_names  Array of possible field names to try
     * @param mixed $default      Default value if no field is found
     * @return mixed              Field value or default
     */
    private static function get_field_value($data, $field_names, $default = '') {
        foreach ($field_names as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }
        return $default;
    }
    
    /**
     * Get price value from ELIT product data
     * 
     * Extracts price information according to ELIT API documentation.
     * Uses pvp_usd or pvp_ars based on user preference.
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return float              Product price
     */
    private static function get_elit_price($elit_product) {
        $price = 0;
        
        // Check user preference for USD pricing
        $use_usd = get_option('elit_sync_usd', false);
        
        if ($use_usd) {
            // Use USD pricing (pvp_usd)
            if (isset($elit_product['pvp_usd']) && is_numeric($elit_product['pvp_usd'])) {
                $price = floatval($elit_product['pvp_usd']);
            }
        } else {
            // Use ARS pricing (pvp_ars)
            if (isset($elit_product['pvp_ars']) && is_numeric($elit_product['pvp_ars'])) {
                $price = floatval($elit_product['pvp_ars']);
            }
        }
        
        // Fallback to base price if PVP not available
        if ($price <= 0 && isset($elit_product['precio']) && is_numeric($elit_product['precio'])) {
            $price = floatval($elit_product['precio']);
            
            // Add taxes if using base price
            if (isset($elit_product['iva']) && is_numeric($elit_product['iva'])) {
                $price += floatval($elit_product['iva']);
            }
            if (isset($elit_product['impuesto_interno']) && is_numeric($elit_product['impuesto_interno'])) {
                $price += floatval($elit_product['impuesto_interno']);
            }
        }
        
        // IMPORTANTE: Los precios pvp_usd y pvp_ars de ELIT ya son precios de venta público
        // Aplicar markup solo si está configurado para hacerlo sobre PVP
        $is_pvp_price = isset($elit_product['pvp_ars']) || isset($elit_product['pvp_usd']);
        $apply_markup_on_pvp = get_option('elit_apply_markup_on_pvp', false);
        
        if ($price > 0 && function_exists('nb_calculate_price_with_markup')) {
            if (!$is_pvp_price || $apply_markup_on_pvp) {
                // Aplicar markup si:
                // 1. No es precio PVP (precio base + impuestos)
                // 2. Es precio PVP pero el usuario quiere markup adicional
                $price = nb_calculate_price_with_markup($price);
            }
            // Si es precio PVP y no se quiere markup adicional, usar precio directo
        }
        
        return $price > 0 ? $price : 0;
    }
    
    /**
     * Get categories from ELIT product data
     * 
     * Extracts category information according to ELIT API documentation.
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return array              Array of category names
     */
    private static function get_elit_categories($elit_product) {
        $categories = array();
        
        // Add main category
        if (isset($elit_product['categoria']) && !empty($elit_product['categoria'])) {
            $categories[] = trim($elit_product['categoria']);
        }
        
        // Add subcategory
        if (isset($elit_product['sub_categoria']) && !empty($elit_product['sub_categoria'])) {
            $categories[] = trim($elit_product['sub_categoria']);
        }
        
        // Add brand as category if enabled
        if (isset($elit_product['marca']) && !empty($elit_product['marca'])) {
            $categories[] = 'Marca: ' . trim($elit_product['marca']);
        }
        
        // Add gamer category if product is gamer
        if (isset($elit_product['gamer']) && $elit_product['gamer'] === true) {
            $categories[] = 'Gaming';
        }
        
        return array_filter(array_unique($categories));
    }
    
    /**
     * Get images from ELIT product data
     * 
     * Extracts image URLs according to ELIT API documentation.
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return array              Array of image URLs
     */
    private static function get_elit_images($elit_product) {
        $images = array();
        
        // Get images from 'imagenes' array (main images) - according to ELIT API docs
        if (isset($elit_product['imagenes']) && is_array($elit_product['imagenes'])) {
            foreach ($elit_product['imagenes'] as $img) {
                if (is_string($img) && !empty($img)) {
                    $processed_img = self::process_image_url($img);
                    if ($processed_img && !in_array($processed_img, $images)) {
                        $images[] = $processed_img;
                    }
                }
            }
        }
        
        // Get thumbnails from 'miniaturas' array as additional images
        if (isset($elit_product['miniaturas']) && is_array($elit_product['miniaturas'])) {
            foreach ($elit_product['miniaturas'] as $thumb) {
                if (is_string($thumb) && !empty($thumb)) {
                    $processed_thumb = self::process_image_url($thumb);
                    if ($processed_thumb && !in_array($processed_thumb, $images)) {
                        $images[] = $processed_thumb;
                    }
                }
            }
        }
        
        return array_unique($images);
    }
    
    /**
     * Process image URL for .webp compatibility
     * 
     * @since 1.0.0
     * @param string $url Image URL
     * @return string|false Processed image URL or false if invalid
     */
    private static function process_image_url($url) {
        if (!is_string($url) || empty($url)) {
            return false;
        }
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if URL is already .webp
        if (strpos($url, '.webp') !== false) {
            return $url;
        }
        
        // Check if URL is from ELIT images domain
        if (strpos($url, 'images.elit.com.ar') !== false) {
            // ELIT provides .webp versions, try to get it
            $webp_url = str_replace(array('.jpg', '.jpeg', '.png', '.gif'), '.webp', $url);
            
            // Test if .webp version exists
            $response = wp_remote_head($webp_url, array('timeout' => 5));
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return $webp_url;
            }
        }
        
        // Return original URL if .webp not available
        return $url;
    }
    
    /**
     * Get stock quantity from ELIT product data
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return int Stock quantity
     */
    private static function get_elit_stock($elit_product) {
        // Use stock_total as primary source according to ELIT API docs
        if (isset($elit_product['stock_total']) && is_numeric($elit_product['stock_total'])) {
            return intval($elit_product['stock_total']);
        }
        
        // Fallback to other stock fields
        $stock_fields = array('stock_deposito_cliente', 'stock_deposito_cd');
        
        foreach ($stock_fields as $field) {
            if (isset($elit_product[$field]) && is_numeric($elit_product[$field])) {
                $stock = intval($elit_product[$field]);
                if ($stock > 0) {
                    return $stock;
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Get stock status from ELIT product data
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return string WooCommerce stock status
     */
    private static function get_elit_stock_status($elit_product) {
        $stock_quantity = self::get_elit_stock($elit_product);
        $stock_level = self::get_field_value($elit_product, array('nivel_stock'), '');
        
        // Use nivel_stock field according to ELIT API docs
        if ($stock_level === 'alto' && $stock_quantity > 0) {
            return 'instock';
        } elseif ($stock_level === 'bajo' && $stock_quantity > 0) {
            return 'onbackorder'; // Permite pedidos pero indica stock bajo
        } else {
            return 'outofstock';
        }
    }
    
    /**
     * Get dimensions from ELIT product data
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return array Dimensions array
     */
    private static function get_elit_dimensions($elit_product) {
        $dimensions = array(
            'length' => 0,
            'width' => 0,
            'height' => 0
        );
        
        if (isset($elit_product['dimensiones']) && is_array($elit_product['dimensiones'])) {
            $dims = $elit_product['dimensiones'];
            $dimensions['length'] = floatval($dims['largo'] ?? 0);
            $dimensions['width'] = floatval($dims['ancho'] ?? 0);
            $dimensions['height'] = floatval($dims['alto'] ?? 0);
        }
        
        return $dimensions;
    }
    
    /**
     * Get currency information from ELIT product data
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return string Currency code
     */
    private static function get_elit_currency($elit_product) {
        $moneda = self::get_field_value($elit_product, array('moneda'), 1);
        return ($moneda == 2) ? 'USD' : 'ARS';
    }
    
    /**
     * Build short description from product data
     * 
     * @since 1.0.0
     * @param array $elit_product ELIT product data
     * @return string Short description
     */
    private static function build_short_description($elit_product) {
        $parts = array();
        
        // Add brand and category
        $brand = self::get_field_value($elit_product, array('marca'));
        $category = self::get_field_value($elit_product, array('categoria'));
        $subcategory = self::get_field_value($elit_product, array('sub_categoria'));
        
        if ($brand) {
            $parts[] = $brand;
        }
        
        if ($category && $subcategory) {
            $parts[] = $category . ' - ' . $subcategory;
        } elseif ($category) {
            $parts[] = $category;
        }
        
        // Add warranty info
        $warranty = self::get_field_value($elit_product, array('garantia'));
        if ($warranty) {
            $parts[] = 'Garantía: ' . $warranty . ($warranty === '12' ? ' meses' : '');
        }
        
        // Add gaming tag
        if (self::get_field_value($elit_product, array('gamer'), false)) {
            $parts[] = '🎮 Gaming';
        }
        
        // Add stock level info
        $stock_level = self::get_field_value($elit_product, array('nivel_stock'));
        if ($stock_level === 'bajo') {
            $parts[] = '⚠️ Stock limitado';
        }
        
        return implode(' | ', $parts);
    }
    
    /**
     * Get products from ELIT CSV API (working endpoint)
     * 
     * @param int $offset Starting offset (not used in CSV, but kept for compatibility)
     * @param int $limit Maximum products to fetch (not used in CSV, but kept for compatibility)
     * @return array Array of products or empty array
     * @since 1.0.0
     */
    public static function get_products_batch($offset = 0, $limit = 10) {
        $credentials = self::get_credentials();
        
        if (!$credentials) {
            return array();
        }
        
        // Use CSV endpoint which works with these credentials
        $url = self::$csv_api_url . '?user_id=' . $credentials['user_id'] . '&token=' . $credentials['token'];
        
        NB_Logger::info('Obteniendo productos desde CSV ELIT: ' . $url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 60,
            'headers' => array(
                'User-Agent' => 'ELIT-WooCommerce-Connector/1.0.0'
            )
        ));
        
        if (is_wp_error($response)) {
            NB_Logger::error('Error en CSV API ELIT: ' . $response->get_error_message());
            return array();
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            NB_Logger::error('Error HTTP ' . $response_code . ' en CSV API ELIT');
            return array();
        }
        
        $csv_data = wp_remote_retrieve_body($response);
        if (empty($csv_data)) {
            NB_Logger::error('Respuesta vacía de CSV API ELIT');
            return array();
        }
        
        // Parse CSV data
        $products = self::parse_csv_products($csv_data);
        
        // Apply offset and limit for compatibility
        if ($offset > 0 || $limit < count($products)) {
            $products = array_slice($products, $offset, $limit);
        }
        
        NB_Logger::info('Productos obtenidos desde CSV ELIT: ' . count($products));
        return $products;
    }
    
    /**
     * Parse CSV products data
     * 
     * @param string $csv_data CSV content
     * @return array Array of products
     * @since 1.0.0
     */
    private static function parse_csv_products($csv_data) {
        $lines = explode("\n", $csv_data);
        $products = array();
        
        if (count($lines) < 2) {
            return $products;
        }
        
        // Get headers from first line
        $headers = str_getcsv($lines[0]);
        
        // Process each product line
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) continue;
            
            $product = array();
            for ($j = 0; $j < count($headers); $j++) {
                $key = $headers[$j];
                $value = $values[$j];
                
                // Convert numeric values
                if (in_array($key, ['id', 'precio', 'impuesto_interno', 'iva', 'markup', 'cotizacion', 'pvp_usd', 'pvp_ars', 'peso', 'stock_total', 'stock_deposito_cliente', 'stock_deposito_cd'])) {
                    $value = is_numeric($value) ? floatval($value) : 0;
                }
                
                // Convert boolean values
                if ($key === 'gamer') {
                    $value = $value === 'true';
                }
                
                $product[$key] = $value;
            }
            
            // Only add products with valid data
            if (!empty($product['codigo_producto']) && !empty($product['nombre'])) {
                $products[] = $product;
            }
        }
        
        return $products;
    }

    /**
     * Test ELIT API connection
     * 
     * Tests the connection to ELIT API with current credentials.
     * 
     * @since 1.0.0
     * @return array Test result with success status and message
     */
    public static function test_connection() {
        $credentials = self::get_credentials();
        if (!$credentials) {
            return array(
                'success' => false,
                'message' => 'Credenciales de ELIT no configuradas'
            );
        }
        
        NB_Logger::info('Probando conexión con ELIT API');
        
        // Test connection with CSV endpoint (working)
        $url = self::$csv_api_url . '?user_id=' . $credentials['user_id'] . '&token=' . $credentials['token'];
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'ELIT-WooCommerce-Connector/1.0.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Error al conectar con ELIT API: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            return array(
                'success' => false,
                'message' => 'Error HTTP ' . $response_code . ' al conectar con ELIT CSV API'
            );
        }
        
        $csv_data = wp_remote_retrieve_body($response);
        if (empty($csv_data)) {
            return array(
                'success' => false,
                'message' => 'Respuesta vacía de ELIT CSV API'
            );
        }
        
        // Parse CSV to count products
        $products = self::parse_csv_products($csv_data);
        $total_products = count($products);
        
        if ($total_products > 0) {
            return array(
                'success' => true,
                'message' => "Conexión exitosa con ELIT CSV API. Total de productos disponibles: {$total_products}"
            );
        } else {
            return array(
                'success' => false,
                'message' => 'No se encontraron productos en ELIT CSV API. Verifica las credenciales (User ID: ' . $credentials['user_id'] . ').'
            );
        }
    }
}

/**
 * Get all products from ELIT (legacy function wrapper)
 * 
 * @since 1.0.0
 * @return array Array of products from ELIT API
 */
function elit_get_all_products() {
    return ELIT_API_Manager::get_all_products();
}

/**
 * Transform ELIT products for sync (legacy function wrapper)
 * 
 * @since 1.0.0
 * @param array $elit_products Array of raw ELIT product data
 * @return array               Array of transformed products
 */
function elit_transform_products($elit_products) {
    $transformed = array();
    
    foreach ($elit_products as $product) {
        $transformed_product = ELIT_API_Manager::transform_product_data($product);
        if ($transformed_product) {
            $transformed[] = $transformed_product;
        }
    }
    
    return $transformed;
}

/**
 * Test ELIT connection (legacy function wrapper)
 * 
 * @since 1.0.0
 * @return array Test result
 */
function elit_test_connection() {
    return ELIT_API_Manager::test_connection();
}
