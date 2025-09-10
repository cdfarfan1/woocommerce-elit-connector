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
     * Maximum products per API request
     * 
     * @var int
     * @since 1.0.0
     */
    private static $max_limit = 100;
    
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
            
            // ELIT returns array of products directly according to documentation
            $products = array();
            if (is_array($response)) {
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
        
        // Map ELIT fields to WooCommerce format according to ELIT documentation
        $transformed = array(
            'sku' => self::get_field_value($elit_product, array('codigo_producto', 'codigo_alfa', 'id')),
            'name' => self::get_field_value($elit_product, array('nombre')),
            'description' => '', // ELIT doesn't provide description in basic product data
            'short_description' => '', // Will be populated from attributes if available
            'price' => self::get_elit_price($elit_product),
            'stock_quantity' => self::get_field_value($elit_product, array('stock_total', 'stock_deposito_cliente'), 0),
            'categories' => self::get_elit_categories($elit_product),
            'images' => self::get_elit_images($elit_product),
            'weight' => self::get_field_value($elit_product, array('peso'), 0),
            'brand' => self::get_field_value($elit_product, array('marca')),
            'warranty' => self::get_field_value($elit_product, array('garantia')),
            'ean' => self::get_field_value($elit_product, array('ean')),
            'stock_level' => self::get_field_value($elit_product, array('nivel_stock')),
            'is_gamer' => self::get_field_value($elit_product, array('gamer'), false),
            'elit_id' => self::get_field_value($elit_product, array('id')),
            'elit_link' => self::get_field_value($elit_product, array('link')),
            'attributes' => self::get_field_value($elit_product, array('atributos'), array())
        );
        
        // Create short description from attributes
        if (!empty($transformed['attributes']) && is_array($transformed['attributes'])) {
            $attr_descriptions = array();
            foreach ($transformed['attributes'] as $attr) {
                if (is_array($attr) && isset($attr['nombre']) && isset($attr['valor'])) {
                    $attr_descriptions[] = $attr['nombre'] . ': ' . $attr['valor'];
                }
            }
            if (!empty($attr_descriptions)) {
                $transformed['short_description'] = implode(' | ', array_slice($attr_descriptions, 0, 3));
            }
        }
        
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
        
        // Get images from 'imagenes' array (main images)
        if (isset($elit_product['imagenes']) && is_array($elit_product['imagenes'])) {
            foreach ($elit_product['imagenes'] as $img) {
                if (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                    $images[] = $img;
                }
            }
        }
        
        // Get thumbnails from 'miniaturas' array as fallback
        if (empty($images) && isset($elit_product['miniaturas']) && is_array($elit_product['miniaturas'])) {
            foreach ($elit_product['miniaturas'] as $thumb) {
                if (is_string($thumb) && filter_var($thumb, FILTER_VALIDATE_URL)) {
                    $images[] = $thumb;
                }
            }
        }
        
        return array_unique($images);
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
        
        // Test connection with minimal request
        $response = self::make_request('productos?limit=1');
        
        if ($response !== null && is_array($response)) {
            $product_count = count($response);
            return array(
                'success' => true,
                'message' => 'Conexión exitosa con ELIT API. Se encontraron productos disponibles.'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Error al conectar con ELIT API. Verifica las credenciales (User ID: ' . $credentials['user_id'] . ').'
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
