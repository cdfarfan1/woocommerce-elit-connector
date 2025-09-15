<?php
/**
 * NewBytes WooCommerce Connector - Settings Functions
 * 
 * @package NewBytes_WooCommerce_Connector
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Render the main options page
 *
 * Displays the plugin configuration page with all settings including
 * API credentials, sync options, markup percentage, and update functionality.
 *
 * @since 1.0.0
 * @return void
 */
function elit_options_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $plugin_url = plugin_dir_url(__FILE__);
    $icon_url = $plugin_url . '../assets/icon-128x128.png';

    $latest_commit = get_latest_version_elit();
    $show_new_version_button = ($latest_commit !== VERSION_ELIT);

    echo '<div class="wrap" style="display: flex; justify-content: center; align-items: center; height: 100%;">';
    echo '<div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; max-width: 600px; width: 100%;">';

    echo '<section style="width: 100%; text-align: left;">';
    if ($show_new_version_button) {
        echo '<form method="post" style="margin-top: 20px;">';
        echo '<button type="button" id="update-connector-btn" style="
            min-width: 130px;
            height: 40px;
            color: #fff;
            padding: 5px 10px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            border: none;
            background-color: #FFC300;
        ">Actualizar Conector NB</button>';
        echo '</form>';
    } else {
        echo '<form method="post" style="margin-top: 20px;">';
        echo '<button type="button" style="
            min-width: 130px;
            height: 40px;
            color: #fff;
            padding: 5px 10px;
            font-weight: bold;
            cursor: not-allowed;
            border-radius: 5px;
            border: none;
            background-color: #e0e0e0;
        " disabled>Actualizado: ' . VERSION_ELIT . '</button>';
        echo '</form>';
    }
    echo '</section>';


    echo '<img src="' . esc_url($icon_url) . '" alt="Logo" style="width: 128px; height: 128px; margin-bottom: 20px;">';
    echo '<h1 style="display: flex; align-items: center; justify-content: center; gap: 10px;">Conector ELIT</h1>';
    echo '<p>Gracias por utilizar nuestro conector de productos para ELIT.</p>';
    echo '<p>Si no tienes credenciales, puedes contactar a <a href="https://elit.com.ar/" target="_blank">ELIT</a> para obtener acceso a la API.</p>';
    if (!is_plugin_active('featured-image-from-url/featured-image-from-url.php')) {
        echo '<p><strong>Para el funcionamiento de las imágenes se requiere la instalación del plugin: ';
        echo '<a href="' . wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=featured-image-from-url'), 'install-plugin_featured-image-from-url') . '">FIFU (Featured Image From URL)</a>';
        echo '</strong></p>';
    }
    echo '<form method="post" action="options.php" style="display: inline-block; text-align: left;">';
    settings_fields('elit_options');
    do_settings_sections('elit');
    echo '<table class="form-table" role="presentation" style="margin: 0 auto;">';
    echo '<tbody>';
    // Add ELIT User ID
    echo '<tr>';
    echo '<th scope="row">ELIT User ID *</th>';
    echo '<td><input type="number" name="elit_user_id" id="elit_user_id" value="' . esc_attr(get_option('elit_user_id')) . '" required />';
    echo '<p class="description">Tu ID de usuario en ELIT (ejemplo: 24560)</p></td>';
    echo '</tr>';
    // Add ELIT Token
    echo '<tr>';
    echo '<th scope="row">ELIT Token *</th>';
    echo '<td><input type="text" name="elit_token" id="elit_token" value="' . esc_attr(get_option('elit_token')) . '" required />';
    echo '<p class="description">Tu token de acceso a la API de ELIT</p></td>';
    echo '</tr>';
    
    // Test credentials button
    echo '<tr>';
    echo '<th scope="row">Probar Credenciales</th>';
    echo '<td>';
    echo '<button type="button" id="test-elit-credentials" class="button button-secondary">Probar Conexión</button>';
    echo '<button type="button" id="save-test-credentials" class="button button-primary" style="margin-left: 10px;">Guardar y Probar</button>';
    echo '<div id="credentials-test-result" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none;"></div>';
    echo '<p class="description">Prueba las credenciales antes de guardar para verificar que funcionan correctamente. "Guardar y Probar" guarda las credenciales temporalmente y las prueba.</p>';
    echo '</td>';
    echo '</tr>';
    // Add Prefix SKU
    echo '<tr>';
    echo '<th scope="row">Prefijo SKU *</th>';
    echo '<td><input type="text" name="elit_sku_prefix" id="elit_sku_prefix" value="' . esc_attr(get_option('elit_sku_prefix', 'ELIT_')) . '" required placeholder="Ejemplo: ELIT_" />';
    echo '<p class="description">Se colocará este prefijo al comienzo de cada SKU para que puedas filtrar tus productos.</p></td>';
    echo '</tr>';
    // Add description
    echo '<tr>';
    echo '<th scope="row">Descripción corta</th>';
    echo '<td><textarea name="elit_description" id="elit_description">' . esc_attr(get_option('elit_description')) . '</textarea>';
    echo '<p class="description">Se agregará esta descripción en todos los productos.</p></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row">Última actualización</th>';
    echo '<td id=last_update>' . esc_attr(get_option('elit_last_update') != '' ? date('d/m/Y H:i', strtotime(get_option('elit_last_update') . '-3 hours')) : '--') . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row">Intervalo de sincronización automática</th>';
    echo '<td><select name="elit_sync_interval" id="elit_sync_interval">';
    $intervals = array(
        '3600'  => 'Cada 1 hora',
        '7200'  => 'Cada 2 horas',
        '10800' => 'Cada 3 horas',
        '14400' => 'Cada 4 horas',
        '18000' => 'Cada 5 horas',
        '21600' => 'Cada 6 horas',
        '25200' => 'Cada 7 horas',
        '28800' => 'Cada 8 horas',
        '32400' => 'Cada 9 horas',
        '36000' => 'Cada 10 horas',
        '39600' => 'Cada 11 horas',
        '43200' => 'Cada 12 horas'
    );

    $current_interval = get_option('elit_sync_interval', get_option('nb_sync_interval', 3600)); // Valor por defecto 1 hora
    foreach ($intervals as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($current_interval, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Selecciona el intervalo en el que deseas que se sincronice automáticamente.</p></td>';
    echo '</tr>';
    // Add Sync USD
    echo '<tr>';
    echo '<th scope="row">Sincronizar en USD</th>';
    echo '<td><input type="checkbox" name="elit_sync_usd" id="elit_sync_usd" value="1" ' . checked(1, get_option('elit_sync_usd'), false) . ' />';
    echo '<p class="description">Selecciona esta opción si deseas sincronizar los productos en USD desde ELIT.</p></td>';
    echo '</tr>';
    // Add Markup Percentage
    echo '<tr>';
    echo '<th scope="row">Porcentaje de Markup Adicional</th>';
    echo '<td><input type="number" name="elit_markup_percentage" id="elit_markup_percentage" value="' . esc_attr(get_option('elit_markup_percentage', 0)) . '" min="0" max="100" /> %';
    echo '<p class="description">Markup adicional sobre los precios PVP de ELIT. Los precios de ELIT ya incluyen su margen. Usa 0% para precios directos de ELIT.</p></td>';
    echo '</tr>';
    
    // Add option to apply markup on PVP
    echo '<tr>';
    echo '<th scope="row">Aplicar Markup sobre PVP</th>';
    echo '<td><input type="checkbox" name="elit_apply_markup_on_pvp" id="elit_apply_markup_on_pvp" value="1" ' . checked(1, get_option('elit_apply_markup_on_pvp'), false) . ' />';
    echo '<p class="description">Marca esta opción si deseas aplicar markup adicional sobre los precios PVP de ELIT. Por defecto, se usan los precios PVP directamente.</p></td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    
    // Configuración de mapeo de campos
    echo '<h2 style="margin-top: 30px; color: #0073aa;">🔗 Mapeo de Campos ELIT → WooCommerce</h2>';
    echo '<p>Configura qué campos de ELIT se vinculan con los campos de WooCommerce:</p>';
    
    echo '<table class="form-table" style="background: white; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    echo '<thead style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
    echo '<tr>';
    echo '<th style="padding: 12px; text-align: left; font-weight: bold; color: #495057;">Campo WooCommerce</th>';
    echo '<th style="padding: 12px; text-align: left; font-weight: bold; color: #495057;">Campo ELIT</th>';
    echo '<th style="padding: 12px; text-align: left; font-weight: bold; color: #495057;">Actualizar</th>';
    echo '<th style="padding: 12px; text-align: left; font-weight: bold; color: #495057;">Descripción</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    // Configuración de mapeo de campos
    $field_mappings = array(
        array('sku', 'codigo_producto', 'SKU del producto'),
        array('name', 'nombre', 'Nombre del producto'),
        array('price', 'pvp_ars', 'Precio en pesos argentinos'),
        array('price_usd', 'pvp_usd', 'Precio en dólares'),
        array('stock_quantity', 'stock_total', 'Cantidad de stock'),
        array('stock_status', 'nivel_stock', 'Estado de stock'),
        array('weight', 'peso', 'Peso del producto'),
        array('ean', 'ean', 'Código de barras'),
        array('warranty', 'garantia', 'Información de garantía'),
        array('gamer', 'gamer', 'Producto gaming'),
        array('category', 'categoria', 'Categoría principal'),
        array('subcategory', 'sub_categoria', 'Subcategoría'),
        array('brand', 'marca', 'Marca del producto'),
        array('images', 'imagenes', 'Imágenes del producto'),
        array('thumbnails', 'miniaturas', 'Miniaturas del producto'),
        array('attributes', 'atributos', 'Atributos del producto'),
        array('link', 'link', 'Enlace ELIT')
    );
    
    foreach ($field_mappings as $mapping) {
        $wc_field = $mapping[0];
        $elit_field = $mapping[1];
        $description = $mapping[2];
        
        $update_key = 'elit_update_' . $wc_field;
        $field_key = 'elit_field_' . $wc_field;
        
        $update_enabled = get_option($update_key, true);
        $field_value = get_option($field_key, $elit_field);
        
        echo '<tr>';
        echo '<td style="padding: 10px; font-weight: bold; color: #0073aa;">' . esc_html(ucfirst(str_replace('_', ' ', $wc_field))) . '</td>';
        echo '<td style="padding: 10px;">';
        echo '<select name="' . esc_attr($field_key) . '" style="width: 200px;">';
        
        $elit_fields = array(
            'id' => 'ID único',
            'codigo_alfa' => 'Código alfanumérico',
            'codigo_producto' => 'Código de producto',
            'nombre' => 'Nombre',
            'categoria' => 'Categoría',
            'sub_categoria' => 'Subcategoría',
            'marca' => 'Marca',
            'precio' => 'Precio base',
            'impuesto_interno' => 'Impuesto interno',
            'iva' => 'IVA',
            'moneda' => 'Moneda',
            'markup' => 'Markup',
            'cotizacion' => 'Cotización',
            'pvp_usd' => 'PVP USD',
            'pvp_ars' => 'PVP ARS',
            'peso' => 'Peso',
            'ean' => 'EAN',
            'nivel_stock' => 'Nivel de stock',
            'stock_total' => 'Stock total',
            'stock_deposito_cliente' => 'Stock depósito cliente',
            'stock_deposito_cd' => 'Stock depósito CD',
            'garantia' => 'Garantía',
            'link' => 'Enlace',
            'imagenes' => 'Imágenes',
            'miniaturas' => 'Miniaturas',
            'atributos' => 'Atributos',
            'gamer' => 'Gaming',
            'creado' => 'Fecha creación',
            'actualizado' => 'Fecha actualización'
        );
        
        foreach ($elit_fields as $field => $label) {
            $selected = ($field_value === $field) ? 'selected' : '';
            echo '<option value="' . esc_attr($field) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
        echo '</td>';
        echo '<td style="padding: 10px;">';
        echo '<input type="checkbox" name="' . esc_attr($update_key) . '" value="1" ' . checked(1, $update_enabled, false) . ' />';
        echo '</td>';
        echo '<td style="padding: 10px; color: #6c757d; font-size: 12px;">' . esc_html($description) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Configuración de actualización automática
    echo '<h2 style="margin-top: 30px; color: #0073aa;">🔄 Configuración de Actualización Automática</h2>';
    echo '<table class="form-table" style="background: white; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    echo '<tbody>';
    
    // Actualizar precios
    echo '<tr>';
    echo '<th scope="row">Actualizar Precios</th>';
    echo '<td><input type="checkbox" name="elit_update_prices" id="elit_update_prices" value="1" ' . checked(1, get_option('elit_update_prices', true), false) . ' />';
    echo '<p class="description">Actualiza automáticamente los precios de los productos existentes durante la sincronización.</p></td>';
    echo '</tr>';
    
    // Actualizar stock
    echo '<tr>';
    echo '<th scope="row">Actualizar Stock</th>';
    echo '<td><input type="checkbox" name="elit_update_stock" id="elit_update_stock" value="1" ' . checked(1, get_option('elit_update_stock', true), false) . ' />';
    echo '<p class="description">Actualiza automáticamente las cantidades de stock de los productos existentes durante la sincronización.</p></td>';
    echo '</tr>';
    
    // Actualizar imágenes
    echo '<tr>';
    echo '<th scope="row">Actualizar Imágenes</th>';
    echo '<td><input type="checkbox" name="elit_update_images" id="elit_update_images" value="1" ' . checked(1, get_option('elit_update_images', false), false) . ' />';
    echo '<p class="description">Actualiza las imágenes de los productos existentes (puede ser lento).</p></td>';
    echo '</tr>';
    
    // Actualizar categorías
    echo '<tr>';
    echo '<th scope="row">Actualizar Categorías</th>';
    echo '<td><input type="checkbox" name="elit_update_categories" id="elit_update_categories" value="1" ' . checked(1, get_option('elit_update_categories', true), false) . ' />';
    echo '<p class="description">Actualiza las categorías y marcas de los productos existentes.</p></td>';
    echo '</tr>';
    
    // Actualizar metadatos
    echo '<tr>';
    echo '<th scope="row">Actualizar Metadatos</th>';
    echo '<td><input type="checkbox" name="elit_update_metadata" id="elit_update_metadata" value="1" ' . checked(1, get_option('elit_update_metadata', true), false) . ' />';
    echo '<p class="description">Actualiza metadatos como EAN, garantía, gaming, etc.</p></td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    // Mapeo de campos ELIT -> WooCommerce
    echo '<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0073aa;">';
    echo '<h3 style="margin-top: 0; color: #0073aa;">📋 Mapeo de Campos ELIT → WooCommerce</h3>';
    echo '<p style="margin-bottom: 20px; color: #666;">Esta tabla muestra cómo se mapean los campos de ELIT a los productos de WooCommerce:</p>';
    
    echo '<div style="overflow-x: auto;">';
    echo '<table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    echo '<thead style="background: #0073aa; color: white;">';
    echo '<tr>';
    echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #005a87;">Campo ELIT</th>';
    echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #005a87;">Campo WooCommerce</th>';
    echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #005a87;">Tipo</th>';
    echo '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #005a87;">Descripción</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $field_mappings = array(
        array('codigo_producto', 'SKU', 'Texto', 'Código único del producto con prefijo ELIT_'),
        array('nombre', 'Nombre del Producto', 'Texto', 'Título del producto'),
        array('pvp_ars / pvp_usd', 'Precio', 'Moneda', 'Precio de venta (ARS o USD según configuración)'),
        array('stock_total', 'Stock', 'Número', 'Cantidad disponible'),
        array('nivel_stock', 'Estado de Stock', 'Estado', 'instock/onbackorder/outofstock'),
        array('categoria', 'Categoría Principal', 'Taxonomía', 'Categoría de WooCommerce'),
        array('sub_categoria', 'Subcategoría', 'Taxonomía', 'Subcategoría de WooCommerce'),
        array('marca', 'Marca', 'Taxonomía', 'Marca como categoría'),
        array('imagen', 'Imagen Destacada', 'Imagen', 'Imagen principal (.webp compatible)'),
        array('miniatura', 'Galería de Imágenes', 'Imagen', 'Imágenes adicionales (.webp compatible)'),
        array('peso', 'Peso', 'Número', 'Peso del producto para envío'),
        array('ean', 'EAN/UPC', 'Meta', 'Código de barras del producto'),
        array('garantia', 'Garantía', 'Meta', 'Información de garantía'),
        array('gamer', 'Gaming', 'Meta', 'Indica si es producto gaming'),
        array('atributos', 'Descripción Corta', 'Texto', 'Características del producto'),
        array('link', 'Enlace ELIT', 'Meta', 'Link al producto en ELIT'),
        array('precio', 'Precio Base', 'Meta', 'Precio de costo (sin impuestos)'),
        array('impuesto_interno', 'Impuesto Interno', 'Meta', 'Impuesto interno aplicado'),
        array('iva', 'IVA', 'Meta', 'Impuesto al valor agregado'),
        array('markup', 'Markup ELIT', 'Meta', 'Markup aplicado por ELIT'),
        array('cotizacion', 'Cotización USD', 'Meta', 'Cotización del dólar al momento de la consulta')
    );
    
    foreach ($field_mappings as $index => $mapping) {
        $row_class = ($index % 2 == 0) ? 'background: #f8f9fa;' : 'background: white;';
        echo '<tr style="' . $row_class . '">';
        echo '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; font-family: monospace; font-weight: bold; color: #e83e8c;">' . esc_html($mapping[0]) . '</td>';
        echo '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; font-weight: 500; color: #28a745;">' . esc_html($mapping[1]) . '</td>';
        echo '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #6c757d;">' . esc_html($mapping[2]) . '</td>';
        echo '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; color: #495057;">' . esc_html($mapping[3]) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Información sobre compatibilidad de imágenes
    echo '<div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 6px; border-left: 4px solid #007cba;">';
    echo '<h4 style="margin-top: 0; color: #007cba;">🖼️ Compatibilidad de Imágenes</h4>';
    echo '<ul style="margin-bottom: 0; color: #495057;">';
    echo '<li><strong>Formatos soportados:</strong> .webp, .jpg, .jpeg, .png, .gif</li>';
    echo '<li><strong>Optimización automática:</strong> Las imágenes .webp se cargan directamente para mejor rendimiento</li>';
    echo '<li><strong>Fallback:</strong> Si .webp no es compatible, se usa la imagen original</li>';
    echo '<li><strong>Imagen destacada:</strong> Se usa el campo "imagen" de ELIT</li>';
    echo '<li><strong>Galería:</strong> Se usa el campo "miniatura" de ELIT para imágenes adicionales</li>';
    echo '</ul>';
    echo '</div>';
    
    // Información sobre procesamiento
    echo '<div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">';
    echo '<h4 style="margin-top: 0; color: #856404;">⚙️ Procesamiento de Datos</h4>';
    echo '<ul style="margin-bottom: 0; color: #495057;">';
    echo '<li><strong>Prefijo SKU:</strong> Se agrega automáticamente el prefijo configurado (ej: ELIT_)</li>';
    echo '<li><strong>Markup:</strong> Se aplica el porcentaje configurado sobre los precios PVP</li>';
    echo '<li><strong>Stock inteligente:</strong> Se determina el estado según nivel_stock y stock_total</li>';
    echo '<li><strong>Categorías:</strong> Se crean automáticamente si no existen</li>';
    echo '<li><strong>Metadatos:</strong> Se guardan campos adicionales como EAN, garantía, gaming</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '</div>';
    submit_button();
    echo '</form>';
    echo '<form method="post" style="margin-top: 20px;">';
    echo '<p>Si cambiaste los markups o realizaste algún ajuste, puedes resincronizar todos los productos:</p>';
    echo '<input type="hidden" name="update_all"/>';
    echo '<button type="submit" class="button button-secondary" id="update-all-btn">';
    echo '<span id="update-all-text">Actualizar todo</span>';
    echo '<span id="update-all-spinner" style="display: none;">';
    echo '<i class="fas fa-spinner fa-spin" style="font-size: 20px;"></i> Sincronizando artículos...';
    echo '</span>';
    echo '</button>';
    echo '</form>';

    if (isset($_POST['update_all'])) {
        echo '<p><details><summary><strong>Respuesta del conector ELIT</strong></summary>';
        echo '<ul>' . elit_callback() . '</ul>';
        echo '</details></p>';
        elit_show_last_update();
    }

    btn_update_description_products();
    modal_confirm_update_();
    modal_success_confirm_update();
    modal_fail_confirm_update();

    btn_delete_products();
    modal_confirm_delete_products();
    js_handler_modals();

    echo '<script>
        jQuery(document).ready(function($) {
            $("#update-connector-btn").on("click", function() {
                if (confirm("¿Estás seguro de que deseas actualizar el conector NB?")) {
                    var $btn = $(this);
                    $btn.prop("disabled", true);
                    $btn.html("<i class=\'fas fa-spinner fa-spin\'></i> Actualizando...");
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "nb_update_connector",
                            nonce: nbAdmin.nonce
                        },
                        success: function(response) {
                            alert(response);
                            location.reload();
                        },
                        error: function() {
                            alert("Error al actualizar el conector NB.");
                            $btn.prop("disabled", false);
                            $btn.html("Actualizar Conector NB");
                        }
                    });
                }
            });

            $("#update-all-btn").on("click", function() {
                $("#update-all-text").hide();
                $("#update-all-spinner").show();
            });

            // Test ELIT credentials
            $("#test-elit-credentials").on("click", function() {
                var $btn = $(this);
                var $result = $("#credentials-test-result");
                
                $btn.prop("disabled", true);
                $btn.html("Probando...");
                $result.hide();
                
                var user_id = $("#elit_user_id").val();
                var token = $("#elit_token").val();
                
                if (!user_id || !token) {
                    $result.html("<div style=\'color: #d63638; background: #fcf0f1; border: 1px solid #f0b7b8; padding: 10px; border-radius: 4px;\'>❌ Por favor completa el User ID y Token antes de probar.</div>").show();
                    $btn.prop("disabled", false);
                    $btn.html("Probar Conexión");
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "elit_test_credentials",
                        user_id: user_id,
                        token: token,
                        nonce: nbAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html("<div style=\'color: #00a32a; background: #f0f6fc; border: 1px solid #72aee6; padding: 10px; border-radius: 4px;\'>✅ " + response.message + "</div>").show();
                        } else {
                            $result.html("<div style=\'color: #d63638; background: #fcf0f1; border: 1px solid #f0b7b8; padding: 10px; border-radius: 4px;\'>❌ " + response.message + "</div>").show();
                        }
                    },
                    error: function() {
                        $result.html("<div style=\'color: #d63638; background: #fcf0f1; border: 1px solid #f0b7b8; padding: 10px; border-radius: 4px;\'>❌ Error al probar las credenciales. Intenta nuevamente.</div>").show();
                    },
                    complete: function() {
                        $btn.prop("disabled", false);
                        $btn.html("Probar Conexión");
                    }
                });
            });

            // Save and test ELIT credentials
            $("#save-test-credentials").on("click", function() {
                var $btn = $(this);
                var $result = $("#credentials-test-result");
                
                $btn.prop("disabled", true);
                $btn.html("Guardando...");
                $result.hide();
                
                var user_id = $("#elit_user_id").val();
                var token = $("#elit_token").val();
                
                if (!user_id || !token) {
                    $result.html("<div style=\'color: #d63638; background: #fcf0f1; border: 1px solid #f0b7b8; padding: 10px; border-radius: 4px;\'>❌ Por favor completa el User ID y Token antes de guardar.</div>").show();
                    $btn.prop("disabled", false);
                    $btn.html("Guardar y Probar");
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "elit_save_test_credentials",
                        user_id: user_id,
                        token: token,
                        nonce: nbAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html("<div style=\'color: #00a32a; background: #f0f6fc; border: 1px solid #72aee6; padding: 10px; border-radius: 4px;\'>✅ " + response.message + "</div>").show();
                        } else {
                            $result.html("<div style=\'color: #d63638; background: #fcf0f1; border: 1px solid #f0b7b8; padding: 10px; border-radius: 4px;\'>❌ " + response.message + "</div>").show();
                        }
                    },
                    error: function() {
                        $result.html("<div style=\'color: #d63638; background: #fcf0f1; border: 1px solid #f0b7b8; padding: 10px; border-radius: 4px;\'>❌ Error al guardar las credenciales. Intenta nuevamente.</div>").show();
                    },
                    complete: function() {
                        $btn.prop("disabled", false);
                        $btn.html("Guardar y Probar");
                    }
                });
            });
        });
    </script>';
}

add_action('wp_ajax_nb_update_connector', 'nb_update_connector');
add_action('wp_ajax_elit_test_credentials', 'elit_test_credentials_ajax');
add_action('wp_ajax_elit_save_test_credentials', 'elit_save_test_credentials_ajax');

/**
 * Maneja la actualización del conector vía AJAX
 *
 * Descarga e instala la última versión del plugin desde GitHub.
 * Incluye verificaciones de seguridad y manejo adecuado de errores.
 *
 * @since 1.0.0
 * @return void
 */
function nb_update_connector()
{
    // Verify nonce for security
    if (!check_ajax_referer('nb_admin_nonce', 'nonce', false)) {
        wp_send_json_error(__('Error de seguridad: nonce inválido', 'newbytes-connector'));
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('No tienes permisos suficientes para realizar esta acción', 'newbytes-connector'));
        return;
    }

    $zip_url = 'https://github.com/New-Bytes/woocommerce-newbytes/archive/refs/heads/main.zip';
    $upload_dir = wp_upload_dir();
    $zip_file = $upload_dir['path'] . '/woocommerce-newbytes-main.zip';

    // Descargar el archivo .zip
    $response = wp_remote_get($zip_url, array('timeout' => 300));
    if (is_wp_error($response)) {
        wp_die('Error downloading the update.');
    }

    $zip_data = wp_remote_retrieve_body($response);
    if (empty($zip_data)) {
        wp_die('Empty response from the update server.');
    }

    // Guardar el archivo .zip en el directorio de uploads
    if (!file_put_contents($zip_file, $zip_data)) {
        wp_die('Error saving the update file.');
    }

    // Descomprimir el archivo .zip
    if (!class_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    WP_Filesystem();
    $unzip_result = unzip_file($zip_file, WP_PLUGIN_DIR);

    if (is_wp_error($unzip_result)) {
        wp_die('Error unzipping the update file.');
    }

    // Borrar el archivo .zip descargado
    unlink($zip_file);

    echo 'Conector NB actualizado correctamente.';
    wp_die();
}

/**
 * Obtiene la última versión disponible del plugin ELIT
 * 
 * Consulta el repositorio de GitHub para obtener la versión más reciente
 * del plugin desde el archivo principal del repositorio.
 * 
 * @since 1.0.0
 * @return string La versión más reciente o mensaje de error
 */
function get_latest_version_elit()
{
    // URL del archivo PHP que contiene la versión
    $file_url = 'https://raw.githubusercontent.com/cdfarfan1/woocommerce-elit-connector/main/woocommerce-elit-connector.php';

    // Obtener el contenido del archivo
    $response = wp_remote_get($file_url);

    if (is_wp_error($response)) {
        return 'Error fetching version data';
    }

    $body = wp_remote_retrieve_body($response);

    // Buscar la línea que contiene la versión
    preg_match('/Version:\s*(\S+)/', $body, $matches);

    if (isset($matches[1])) {
        return $matches[1];
    } else {
        return 'Version not found';
    }
}

/**
 * Inicializa la configuración del plugin
 *
 * Registra secciones de configuración, campos y opciones para la
 * integración con la API de configuración de WordPress.
 *
 * @since 1.0.0
 * @return void
 */
function nb_settings_init()
{
    add_settings_section(
        'nb_settings_section',
        'Configuración General',
        'nb_settings_section_callback',
        'nb'
    );

    add_settings_field(
        'nb_markup_percentage',
        'Porcentaje de Markup',
        'nb_markup_percentage_callback',
        'nb',
        'nb_settings_section'
    );

    register_setting('nb_options', 'nb_markup_percentage', array(
        'type' => 'number',
        'description' => 'Porcentaje de markup a aplicar a los precios de los productos',
        'default' => 35,
        'sanitize_callback' => 'absint'
    ));
}

/**
 * Callback de la sección de configuración
 *
 * Muestra el texto descriptivo para la sección principal de configuración.
 *
 * @since 1.0.0
 * @return void
 */
function nb_settings_section_callback()
{
    echo 'Configura los parámetros necesarios para la sincronización de productos.';
}

/**
 * Callback del campo de porcentaje de markup
 *
 * Renderiza el campo de entrada del porcentaje de markup con el valor actual
 * y los atributos de validación apropiados.
 *
 * @since 1.0.0
 * @return void
 */
function nb_markup_percentage_callback()
{
    $markup_percentage = get_option('nb_markup_percentage', 35);
    echo '<input type="number" name="nb_markup_percentage" value="' . esc_attr($markup_percentage) . '" min="0" max="100" /> %';
}

add_action('admin_init', 'nb_settings_init');

/**
 * Maneja la prueba de credenciales de ELIT vía AJAX
 *
 * Prueba las credenciales de ELIT sin guardarlas permanentemente.
 * Utiliza el endpoint CSV que funciona correctamente.
 *
 * @since 1.0.0
 * @return void
 */
function elit_test_credentials_ajax() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'], 'nb_admin_nonce')) {
        wp_die('Error de seguridad');
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para realizar esta acción');
    }
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $token = sanitize_text_field($_POST['token']);
    
    if (empty($user_id) || empty($token)) {
        wp_send_json_error('User ID y Token son requeridos');
        return;
    }
    
    // Probar conexión con las credenciales proporcionadas
    $csv_url = 'https://clientes.elit.com.ar/v1/api/productos/csv';
    $url = $csv_url . '?user_id=' . $user_id . '&token=' . $token;
    
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'headers' => array(
            'User-Agent' => 'ELIT-WooCommerce-Connector/1.0.0'
        )
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error('Error al conectar con ELIT: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code >= 400) {
        wp_send_json_error('Error HTTP ' . $response_code . ' al conectar con ELIT');
        return;
    }
    
    $csv_data = wp_remote_retrieve_body($response);
    if (empty($csv_data)) {
        wp_send_json_error('Respuesta vacía de ELIT');
        return;
    }
    
    // Parsear CSV para contar productos
    $lines = explode("\n", $csv_data);
    $product_count = 0;
    
    for ($i = 1; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        
        $values = str_getcsv($line);
        if (count($values) >= 3 && !empty($values[2]) && !empty($values[3])) {
            $product_count++;
        }
    }
    
    if ($product_count > 0) {
        wp_send_json_success("Conexión exitosa con ELIT. Se encontraron {$product_count} productos disponibles.");
    } else {
        wp_send_json_error('No se encontraron productos en ELIT con estas credenciales');
    }
}

/**
 * Maneja el guardado y prueba de credenciales de ELIT vía AJAX
 *
 * Guarda las credenciales temporalmente y las prueba.
 * Si funcionan, las guarda permanentemente.
 *
 * @since 1.0.0
 * @return void
 */
function elit_save_test_credentials_ajax() {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'], 'nb_admin_nonce')) {
        wp_die('Error de seguridad');
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para realizar esta acción');
    }
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $token = sanitize_text_field($_POST['token']);
    
    if (empty($user_id) || empty($token)) {
        wp_send_json_error('User ID y Token son requeridos');
        return;
    }
    
    // Guardar credenciales temporalmente
    update_option('elit_user_id', $user_id);
    update_option('elit_token', $token);
    
    // Probar conexión con las credenciales guardadas
    $csv_url = 'https://clientes.elit.com.ar/v1/api/productos/csv';
    $url = $csv_url . '?user_id=' . $user_id . '&token=' . $token;
    
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'headers' => array(
            'User-Agent' => 'ELIT-WooCommerce-Connector/1.0.0'
        )
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error('Error al conectar con ELIT: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code >= 400) {
        wp_send_json_error('Error HTTP ' . $response_code . ' al conectar con ELIT');
        return;
    }
    
    $csv_data = wp_remote_retrieve_body($response);
    if (empty($csv_data)) {
        wp_send_json_error('Respuesta vacía de ELIT');
        return;
    }
    
    // Parsear CSV para contar productos
    $lines = explode("\n", $csv_data);
    $product_count = 0;
    
    for ($i = 1; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        
        $values = str_getcsv($line);
        if (count($values) >= 3 && !empty($values[2]) && !empty($values[3])) {
            $product_count++;
        }
    }
    
    if ($product_count > 0) {
        wp_send_json_success("Credenciales guardadas y probadas exitosamente. Se encontraron {$product_count} productos disponibles en ELIT. Ya puedes sincronizar productos.");
    } else {
        wp_send_json_error('Credenciales guardadas pero no se encontraron productos en ELIT');
    }
}
