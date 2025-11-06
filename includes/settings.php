<?php
/**
 * NewBytes WooCommerce Connector - Settings Functions
 *
 * @package NewBytes_WooCommerce_Connector
 * @version 1.1.1
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// ... (la función elit_options_page() y el resto de la parte superior del archivo permanece igual)

function elit_options_page()
{
    // ... (código existente de la página de opciones)

    // Vista Previa de Producto
    echo '<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #17a2b8;">';
    echo '<h3 style="margin-top: 0; color: #17a2b8;">🔍 Vista Previa de Producto</h3>';
    echo '<p style="margin-bottom: 15px; color: #666;">Introduce el SKU de un producto de ELIT para ver cómo se importaría en WooCommerce sin sincronizarlo.</p>';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="preview_sku">SKU del Producto</label></th>';
    echo '<td><input type="text" id="preview_sku" name="preview_sku" placeholder="Ej: LENEX5WS0T36151" style="width: 250px;"/>';
    echo '<p class="description">Usa el código de producto de ELIT (sin el prefijo).</p></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"></th>';
    echo '<td><button type="button" id="generate-preview-btn" class="button button-info">Generar Vista Previa</button></td>';
    echo '</tr>';
    echo '</table>';
    echo '<div id="product-preview-result" style="margin-top: 20px; display: none;"></div>';
    echo '</div>';

    // ... (resto del HTML de la página)
    
    // Añadir el JavaScript para la vista previa al final de la función
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#generate-preview-btn').on('click', function() {
            var $btn = $(this);
            var sku = $('#preview_sku').val();
            var $resultDiv = $('#product-preview-result');

            if (!sku) {
                alert('Por favor, introduce un SKU.');
                return;
            }

            $btn.prop('disabled', true).text('Generando...');
            $resultDiv.hide().html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'nb_product_preview',
                    sku: sku,
                    nonce: '<?php echo wp_create_nonce('nb_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $resultDiv.html(response.data).show();
                    } else {
                        $resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>').show();
                    }
                },
                error: function() {
                    $resultDiv.html('<div class="notice notice-error"><p>Ocurrió un error al generar la vista previa. Revisa la consola para más detalles.</p></div>').show();
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Generar Vista Previa');
                }
            });
        });
    });
    </script>
    <?php

    // ... (código JavaScript existente para prueba de conexión, etc.)
}

add_action('wp_ajax_nb_product_preview', 'nb_product_preview_ajax');

/**
 * Maneja la generación de la vista previa de un producto vía AJAX
 *
 * @since 1.1.1
 * @return void
 */
function nb_product_preview_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nb_admin_nonce')) {
        wp_send_json_error('Error de seguridad.', 403);
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos para esta acción.', 403);
        return;
    }

    $sku = sanitize_text_field($_POST['sku']);
    if (empty($sku)) {
        wp_send_json_error('El SKU es obligatorio.');
        return;
    }

    // Obtener credenciales
    $user_id = get_option('elit_user_id');
    $token = get_option('elit_token');

    if (empty($user_id) || empty($token)) {
        wp_send_json_error('Las credenciales de ELIT no están configuradas.');
        return;
    }

    // Endpoint de la API para un producto específico por 'codigo_producto'
    $api_url = 'https://clientes.elit.com.ar/v1/api/productos';
    $url = add_query_arg(array(
        'user_id' => $user_id,
        'token'   => $token,
        'codigo_producto' => $sku, // Usar 'codigo_producto' para buscar por SKU
    ), $api_url);

    $response = wp_remote_get($url, array('timeout' => 30));

    if (is_wp_error($response)) {
        wp_send_json_error('Error de conexión: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['resultado']) || !is_array($data['resultado'])) {
         wp_send_json_error('Producto no encontrado o error en la respuesta de la API. Verifica que el SKU sea correcto.');
        return;
    }

    // Como la API devuelve un array, tomamos el primer elemento
    $elit_product = $data['resultado'][0];

    // Transformar los datos del producto
    if (!class_exists('ELIT_API_Manager')) {
        require_once ELIT_PLUGIN_PATH . 'includes/elit-api.php';
    }
    $transformed_product = ELIT_API_Manager::transform_product_data($elit_product);

    if (!$transformed_product) {
        wp_send_json_error('No se pudieron procesar los datos del producto.');
        return;
    }

    // Generar HTML para la vista previa
    $html = '<div class="product-preview-table-container">';
    $html .= '<h4>Resultado de la Vista Previa</h4>';
    $html .= '<table class="widefat striped">';
    $html .= '<thead><tr><th>Campo</th><th>Valor</th></tr></thead>';
    $html .= '<tbody>';
    $html .= '<tr><td><strong>Nombre</strong></td><td>' . esc_html($transformed_product['name']) . '</td></tr>';
    $html .= '<tr><td><strong>SKU Final</strong></td><td>' . esc_html($transformed_product['sku']) . '</td></tr>';
    $html .= '<tr><td><strong>Precio Calculado</strong></td><td>$' . number_format($transformed_product['price'], 2, ',', '.') . ' (' . (get_option('elit_sync_usd') ? 'USD' : 'ARS') . ')</td></tr>';
    $html .= '<tr><td><strong>Stock</strong></td><td>' . esc_html($transformed_product['stock_quantity']) . ' unidades (' . $transformed_product['stock_status'] . ')</td></tr>';
    $html .= '<tr><td><strong>Categorías</strong></td><td>' . implode(', ', array_map('esc_html', $transformed_product['categories'])) . '</td></tr>';
    $html .= '<tr><td><strong>Marca</strong></td><td>' . esc_html($transformed_product['brand']) . '</td></tr>';
    $html .= '<tr><td><strong>Imagen Principal</strong></td><td><a href="' . esc_url($transformed_product['images'][0]) . '" target="_blank"><img src="' . esc_url($transformed_product['images'][0]) . '" style="max-width: 80px; height: auto;"/></a></td></tr>';
    $html .= '<tr><td><strong>Descripción Corta</strong></td><td>' . esc_html($transformed_product['short_description']) . '</td></tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';

    wp_send_json_success($html);
}

// ... (el resto de las funciones de settings.php)
