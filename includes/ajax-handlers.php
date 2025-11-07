<?php
/**
 * ajax-handlers.php
 * ---
 * Maneja las peticiones AJAX que vienen del panel de administración.
 */

if (!defined('ABSPATH')) {
    exit; // No permitir acceso directo.
}

/**
 * Registra los endpoints de AJAX para usuarios logueados.
 */
add_action('wp_ajax_test_elit_connection', 'elit_ajax_test_connection_handler');
add_action('wp_ajax_get_elit_product_preview', 'elit_ajax_product_preview_handler');
add_action('wp_ajax_elit_full_sync', 'elit_ajax_full_sync_handler');
add_action('wp_ajax_elit_desc_sync', 'elit_ajax_desc_sync_handler');

/**
 * Manejador para la prueba de conexión.
 * Ahora redirige la lógica al script independiente `test-elit-connection.php`.
 */
function elit_ajax_test_connection_handler() {
    check_ajax_referer('elit_ajax_nonce');

    // Los datos ahora se envían directamente a `test-elit-connection.php` desde JS.
    // Sin embargo, mantenemos este handler por si se necesita lógica adicional del lado de WP.

    $user_id = get_option('elit_user_id');
    $token = get_option('elit_token');

    if (empty($user_id) || empty($token)) {
        wp_send_json_error(['message' => 'Error: Por favor, guarda el User ID y el Token antes de probar.']);
        return;
    }

    // Incluir el script de prueba de conexión para realizar la llamada a la API
    $test_script_path = ELIT_PLUGIN_PATH . 'test-elit-connection.php';

    if (file_exists($test_script_path)) {
        // Simular una petición POST interna al script
        $_POST['user_id'] = $user_id;
        $_POST['token'] = $token;
        
        // Capturar la salida del script
        ob_start();
        include $test_script_path;
        $response = ob_get_clean();
        
        // Devolver la respuesta del script de prueba
        header('Content-Type: application/json');
        echo $response;
        exit;

    } else {
        wp_send_json_error(['message' => 'Error: No se encontró el archivo de prueba de conexión.']);
    }
}

/**
 * Manejador para la vista previa de producto.
 * Llama a la función de ayuda `elit_get_product_from_api` de `sync.php`.
 */
function elit_ajax_product_preview_handler() {
    check_ajax_referer('elit_ajax_nonce');
    if (!isset($_POST['sku']) || empty($_POST['sku'])) {
        wp_send_json_error(['message' => 'Error: No se proporcionó ningún SKU.']);
        return;
    }

    $sku = sanitize_text_field($_POST['sku']);

    // Llama a la función que obtiene datos reales de la API (desde sync.php)
    $product_data = elit_get_product_from_api($sku);

    if (is_wp_error($product_data)) {
        wp_send_json_error(['message' => $product_data->get_error_message()]);
    } else {
        wp_send_json_success($product_data);
    }
}

/**
 * Manejador para la sincronización completa.
 */
function elit_ajax_full_sync_handler() {
    check_ajax_referer('elit_ajax_nonce');
    $result = elit_sync_products();
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['message' => 'Sincronización completa finalizada.']);
    }
}

/**
 * Manejador para la sincronización de descripciones.
 */
function elit_ajax_desc_sync_handler() {
    check_ajax_referer('elit_ajax_nonce');
    $result = elit_sync_products(true);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['message' => 'Sincronización de descripciones finalizada.']);
    }
}
