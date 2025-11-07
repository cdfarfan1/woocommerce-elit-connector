<?php
/**
 * sync.php
 * ---
 * Lógica de sincronización de productos desde la API de ELIT a WooCommerce.
 */

if (!defined('ABSPATH')) {
    exit; // No permitir acceso directo.
}

/**
 * Obtiene los datos de un producto específico desde la API de ELIT.
 *
 * @param string $sku El SKU del producto a buscar.
 * @return array|WP_Error Los datos del producto o un error.
 */
function elit_get_product_from_api($sku) {
    $user_id = get_option('elit_user_id');
    $token = get_option('elit_token');

    if (empty($user_id) || empty($token)) {
        return new WP_Error('missing_credentials', 'No se han configurado el User ID o el Token de ELIT.');
    }

    $api_url = sprintf('https://www.elit.com.ar/v1/api/productos/sku/%s/key/%s/user_id/%s', $sku, $token, $user_id);

    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return new WP_Error('api_request_failed', 'Error al conectar con la API de ELIT: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['status']) || $data['status'] !== 'OK') {
        $error_message = isset($data['message']) ? $data['message'] : 'Respuesta inválida de la API.';
        return new WP_Error('invalid_api_response', $error_message);
    }

    if (empty($data['resultado'])) {
        return new WP_Error('product_not_found', 'Producto no encontrado en la API de ELIT con el SKU: ' . esc_html($sku));
    }

    // Retornar los datos del primer producto encontrado
    return $data['resultado'][0];
}

/**
 * Función principal que sincroniza TODOS los productos.
 *
 * @param bool $only_descriptions Si es true, solo actualiza descripciones.
 * @return bool|WP_Error True en éxito, WP_Error en fallo.
 */
function elit_sync_products($only_descriptions = false) {
    $user_id = get_option('elit_user_id');
    $token = get_option('elit_token');

    if (empty($user_id) || empty($token)) {
        return new WP_Error('missing_credentials', 'No se han configurado el User ID o el Token de ELIT.');
    }

    $api_url = sprintf('https://www.elit.com.ar/v1/api/productos/all/key/%s/user_id/%s', $token, $user_id);

    $response = wp_remote_get($api_url, ['timeout' => 300]);

    if (is_wp_error($response)) {
        return new WP_Error('api_request_failed', 'Error al conectar con la API de ELIT: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['status']) || $data['status'] !== 'OK') {
        return new WP_Error('invalid_api_response', 'Respuesta inválida de la API de ELIT.');
    }

    if (empty($data['resultado'])) {
        return new WP_Error('no_products', 'No se encontraron productos en la API de ELIT.');
    }

    foreach ($data['resultado'] as $product_data) {
        $sku = get_option('elit_sku_prefix', 'ELIT-') . $product_data['sku'];
        $product_id = wc_get_product_id_by_sku($sku);

        if ($product_id) {
            // Producto existente
            $product = wc_get_product($product_id);
        } else {
            // Producto nuevo
            $product = new WC_Product_Simple();
        }

        // Actualizar datos
        if ($only_descriptions) {
            $product->set_description($product_data['descripcion']);
        } else {
            $product->set_sku($sku);
            $product->set_name($product_data['nombre']);
            $product->set_description($product_data['descripcion']);

            $price = get_option('elit_sync_usd') ? $product_data['pvp_usd'] : $product_data['pvp_ars'];
            $markup = (float) get_option('nb_markup_percentage', 0);
            $final_price = $price * (1 + ($markup / 100));
            $product->set_regular_price($final_price);

            $stock_status = $product_data['stock_total'] > 0 ? 'instock' : 'outofstock';
            $product->set_stock_status($stock_status);
            $product->set_stock_quantity($product_data['stock_total']);

            // Aquí iría la lógica para imágenes y categorías
        }

        $product->save();
    }

    return true;
}

/**
 * Función de ayuda para simular la obtención de un producto por SKU.
 * Útil para la vista previa en el admin.
 */
function elit_get_simulated_product_by_sku($sku) {
    $sku_prefix = get_option('elit_sku_prefix', 'ELIT-');
    $markup = get_option('nb_markup_percentage', 0) / 100;

    // Simulación de datos de un producto
    $simulated_data = [
        'sku' => $sku,
        'name' => 'Producto Simulado (' . $sku . ')',
        'price' => 150.00 * (1 + $markup), // Aplicar margen de ganancia
        'stock' => 25,
        'image_url' => 'https://via.placeholder.com/150',
    ];

    // Añadir el prefijo al SKU para la vista previa
    $simulated_data['full_sku'] = $sku_prefix . $sku;

    return $simulated_data;
}
