<?php
/**
 * settings.php
 * ---
 * Renderiza el HTML de la página de ajustes del Conector ELIT.
 */

if (!defined('ABSPATH')) {
    exit; // No permitir acceso directo.
}

/**
 * Función principal que se llama para dibujar toda la página de ajustes.
 */
function elit_render_settings_page() {
    ?>
    <div class="wrap elit-connector-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>Configura la conexión con la API de ELIT y personaliza la sincronización de productos.</p>

        <form method="post" action="options.php">
            <?php
            settings_fields('elit_options_group');
            ?>

            <!-- Sección de Credenciales de API -->
            <div class="card">
                <h2><span class="dashicons dashicons-admin-network"></span> Credenciales de la API</h2>
                <p>Introduce tus credenciales para conectar con la API de ELIT.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="elit_user_id">User ID de ELIT</label></th>
                        <td><input type="text" id="elit_user_id" name="elit_user_id" value="<?php echo esc_attr(get_option('elit_user_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="elit_token">Token de ELIT</label></th>
                        <td><input type="text" id="elit_token" name="elit_token" value="<?php echo esc_attr(get_option('elit_token')); ?>" class="regular-text"/></td>
                    </tr>
                </table>
                <button type="button" id="test-elit-connection" class="button button-secondary">Probar Conexión</button>
                <div id="elit-connection-result" style="margin-top: 10px; font-weight: bold;"></div>
            </div>

            <!-- Sección de Opciones de Sincronización -->
            <div class="card">
                <h2><span class="dashicons dashicons-admin-settings"></span> Opciones de Sincronización</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="elit_sku_prefix">Prefijo para SKU</label></th>
                        <td><input type="text" id="elit_sku_prefix" name="elit_sku_prefix" value="<?php echo esc_attr(get_option('elit_sku_prefix', 'ELIT-')); ?>" />
                        <p class="description">Este prefijo se añadirá a cada SKU de producto importado.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nb_markup_percentage">Margen de Ganancia (%)</label></th>
                        <td><input type="number" id="nb_markup_percentage" name="nb_markup_percentage" value="<?php echo esc_attr(get_option('nb_markup_percentage', '0')); ?>" class="small-text" min="0" step="any" />
                        <p class="description">Añade un porcentaje de ganancia sobre el precio de ELIT.</p></td>
                    </tr>
                    <tr>
                        <th scope="row">Moneda de Sincronización</th>
                        <td>
                            <fieldset>
                                <label><input type="checkbox" name="elit_sync_usd" value="1" <?php checked(get_option('elit_sync_usd'), 1); ?> /> Sincronizar precios en USD</label>
                                <p class="description">Si se marca, se usarán los precios en dólares (pvp_usd). Si no, se usarán pesos (pvp_ars).</p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sección de Ajustes de Actualización -->
             <div class="card">
                <h2><span class="dashicons dashicons-update-alt"></span> Opciones de Actualización</h2>
                 <p>Marca los campos que quieres actualizar cuando un producto ya existente se sincroniza.</p>
                 <table class="form-table">
                    <tr>
                        <th scope="row">Actualizar Datos</th>
                        <td>
                            <fieldset>
                                <label><input type="checkbox" name="elit_update_prices" value="1" <?php checked(get_option('elit_update_prices', 1), 1); ?> /> Actualizar Precios</label><br>
                                <label><input type="checkbox" name="elit_update_stock" value="1" <?php checked(get_option('elit_update_stock', 1), 1); ?> /> Actualizar Stock</label><br>
                                <label><input type="checkbox" name="elit_update_images" value="1" <?php checked(get_option('elit_update_images', 1), 1); ?> /> Actualizar Imágenes</label><br>
                                <label><input type="checkbox" name="elit_update_categories" value="1" <?php checked(get_option('elit_update_categories', 1), 1); ?> /> Actualizar Categorías</label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button('Guardar Cambios'); ?>
        </form>

        <!-- Sección de Sincronización Manual -->
        <div class="card">
             <h2><span class="dashicons dashicons-controls-repeat"></span> Sincronización Manual</h2>
            <p>Ejecuta la sincronización de productos de forma manual.</p>
            <button type="button" id="elit-full-sync" class="button button-primary">Actualizar Todo</button>
            <button type="button" id="elit-desc-sync" class="button button-secondary">Actualizar Descripciones</button>
            <div id="elit-sync-results" style="margin-top: 15px;"></div>
        </div>

        <!-- Sección de Vista Previa de Producto -->
        <div class="card">
            <hr>
            <h2><span class="dashicons dashicons-search"></span> Vista Previa de Producto</h2>
            <p>Introduce el SKU de un producto de ELIT (sin el prefijo) para ver cómo se importaría en WooCommerce.</p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="preview_sku">SKU del Producto</label></th>
                    <td><input type="text" id="preview_sku" class="regular-text" placeholder="Ej: LENEX5WS0T36151" /></td>
                </tr>
            </table>
            <button type="button" id="generate_preview" class="button button-secondary">Generar Vista Previa</button>
            <div id="preview_result" style="margin-top: 15px;"></div>
        </div>

    </div>
    <?php
}
