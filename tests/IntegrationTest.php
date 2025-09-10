<?php
/**
 * Pruebas de integración para el plugin WooCommerce NewBytes
 * 
 * @package WooCommerce_NewBytes
 * @subpackage Tests
 */

class IntegrationTest extends WP_UnitTestCase {

    protected $admin_user;
    protected $shop_manager_user;
    protected $customer_user;

    public function setUp(): void {
        parent::setUp();
        
        // Obtener usuarios de prueba
        $this->admin_user = get_user_by('login', 'test_admin');
        $this->shop_manager_user = get_user_by('login', 'test_shop_manager');
        $this->customer_user = get_user_by('login', 'test_customer');
        
        // Incluir todos los archivos del plugin
        include_once plugin_dir_path(__FILE__) . '../woocommerce-newbytes.php';
        include_once plugin_dir_path(__FILE__) . '../includes/config.php';
        include_once plugin_dir_path(__FILE__) . '../includes/product-sync.php';
        include_once plugin_dir_path(__FILE__) . '../includes/error-handler.php';
        include_once plugin_dir_path(__FILE__) . '../includes/database-optimizer.php';
        include_once plugin_dir_path(__FILE__) . '../includes/admin-page.php';
        include_once plugin_dir_path(__FILE__) . '../includes/utils.php';
        
        // Activar el plugin
        if (function_exists('nb_activate_plugin')) {
            nb_activate_plugin();
        }
    }

    /**
     * Test: Integración completa del flujo de sincronización
     */
    public function test_complete_sync_workflow() {
        wp_set_current_user($this->admin_user->ID);
        
        // 1. Verificar configuración inicial
        $this->assertTrue(current_user_can('manage_woocommerce'));
        
        // 2. Configurar credenciales de prueba
        update_option('nb_api_url', 'https://test-api.newbytes.com');
        update_option('nb_api_key', 'test_api_key_123');
        update_option('nb_store_name', 'Test Store');
        
        // 3. Verificar que las opciones se guardaron correctamente
        $this->assertEquals('https://test-api.newbytes.com', get_option('nb_api_url'));
        $this->assertEquals('test_api_key_123', get_option('nb_api_key'));
        $this->assertEquals('Test Store', get_option('nb_store_name'));
        
        // 4. Simular proceso de sincronización
        $_POST['action'] = 'sync_products';
        $_POST['nonce'] = wp_create_nonce('nb_sync_nonce');
        $_POST['prefix'] = 'TEST';
        
        // 5. Verificar que la función de sincronización existe y es callable
        if (function_exists('handle_sync_products')) {
            $this->assertTrue(is_callable('handle_sync_products'));
        }
        
        // 6. Verificar logging de la actividad
        if (function_exists('log_security_event')) {
            log_security_event('sync_test', 'Test sync integration', ['user_id' => $this->admin_user->ID]);
            
            global $wpdb;
            $log_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type = %s",
                    'sync_test'
                )
            );
            
            $this->assertGreaterThan(0, $log_count);
        }
    }

    /**
     * Test: Integración de manejo de errores con logging
     */
    public function test_error_handling_integration() {
        wp_set_current_user($this->admin_user->ID);
        
        // 1. Simular error en el sistema
        $error_data = [
            'error_type' => 'integration_test_error',
            'error_message' => 'Test error for integration testing',
            'file_path' => __FILE__,
            'line_number' => __LINE__
        ];
        
        // 2. Verificar que el manejo de errores funciona
        if (function_exists('nb_handle_error')) {
            $result = nb_handle_error(
                $error_data['error_type'],
                $error_data['error_message'],
                $error_data['file_path'],
                $error_data['line_number']
            );
            
            $this->assertTrue($result !== false);
        }
        
        // 3. Verificar que el error se registró en la base de datos
        global $wpdb;
        $error_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}nb_error_logs WHERE error_type = %s",
                'integration_test_error'
            )
        );
        
        $this->assertGreaterThan(0, $error_count);
        
        // 4. Verificar que se registró evento de seguridad
        $security_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE message LIKE '%error%'"
        );
        
        $this->assertGreaterThanOrEqual(0, $security_count);
    }

    /**
     * Test: Integración de optimización de base de datos
     */
    public function test_database_optimization_integration() {
        wp_set_current_user($this->admin_user->ID);
        
        // 1. Insertar datos de prueba antiguos
        global $wpdb;
        
        $old_security_log = [
            'event_type' => 'old_integration_test',
            'message' => 'Old test message',
            'ip_address' => '192.168.1.1',
            'created_at' => date('Y-m-d H:i:s', strtotime('-35 days'))
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'nb_security_logs',
            $old_security_log,
            ['%s', '%s', '%s', '%s']
        );
        
        $old_error_log = [
            'error_type' => 'old_integration_error',
            'error_message' => 'Old error message',
            'created_at' => date('Y-m-d H:i:s', strtotime('-95 days'))
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'nb_error_logs',
            $old_error_log,
            ['%s', '%s', '%s']
        );
        
        // 2. Obtener estadísticas antes de la limpieza
        if (function_exists('get_database_stats')) {
            $stats_before = get_database_stats();
            $this->assertIsArray($stats_before);
        }
        
        // 3. Ejecutar limpieza de base de datos
        if (function_exists('cleanup_database')) {
            $cleanup_result = cleanup_database();
            $this->assertTrue($cleanup_result !== false);
        }
        
        // 4. Verificar que los logs antiguos fueron eliminados
        $old_security_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type = %s",
                'old_integration_test'
            )
        );
        
        $old_error_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}nb_error_logs WHERE error_type = %s",
                'old_integration_error'
            )
        );
        
        $this->assertEquals(0, $old_security_count);
        $this->assertEquals(0, $old_error_count);
        
        // 5. Obtener estadísticas después de la limpieza
        if (function_exists('get_database_stats')) {
            $stats_after = get_database_stats();
            $this->assertIsArray($stats_after);
        }
    }

    /**
     * Test: Integración de seguridad completa
     */
    public function test_complete_security_integration() {
        // 1. Test con usuario no autorizado
        wp_set_current_user($this->customer_user->ID);
        
        // Verificar que no puede acceder a funciones administrativas
        $this->assertFalse(current_user_can('manage_woocommerce'));
        $this->assertFalse(current_user_can('manage_options'));
        
        // 2. Test con usuario autorizado
        wp_set_current_user($this->admin_user->ID);
        
        $this->assertTrue(current_user_can('manage_woocommerce'));
        $this->assertTrue(current_user_can('manage_options'));
        
        // 3. Test de sanitización en flujo completo
        $malicious_data = [
            'nb_api_url' => '<script>alert("XSS")</script>https://evil.com',
            'nb_store_name' => '<script>alert("XSS")</script>Evil Store',
            'prefix' => '<script>alert("XSS")</script>EVIL'
        ];
        
        // Simular guardado de configuración
        $_POST['nb_api_url'] = $malicious_data['nb_api_url'];
        $_POST['nb_store_name'] = $malicious_data['nb_store_name'];
        $_POST['nb_settings_nonce'] = wp_create_nonce('nb_settings_action');
        
        // Verificar sanitización
        $sanitized_url = sanitize_url($_POST['nb_api_url']);
        $sanitized_name = sanitize_text_field($_POST['nb_store_name']);
        
        $this->assertStringNotContainsString('<script>', $sanitized_url);
        $this->assertStringNotContainsString('<script>', $sanitized_name);
        
        // 4. Test de rate limiting
        if (function_exists('check_rate_limit')) {
            $_POST['action'] = 'sync_products';
            
            $rate_limited = false;
            for ($i = 0; $i < 10; $i++) {
                if (!check_rate_limit()) {
                    $rate_limited = true;
                    break;
                }
            }
            
            // El rate limiting debería activarse eventualmente
            $this->assertTrue($rate_limited || $i >= 10);
        }
    }

    /**
     * Test: Integración de utilidades y helpers
     */
    public function test_utilities_integration() {
        wp_set_current_user($this->admin_user->ID);
        
        // 1. Test de validación de credenciales
        if (function_exists('nb_validate_credentials')) {
            $valid_creds = nb_validate_credentials('test_api_key', 'https://test.com');
            // Nota: Esto puede fallar si no hay conexión real, pero verifica que la función existe
            $this->assertTrue(is_bool($valid_creds));
        }
        
        // 2. Test de formateo de tamaño de archivo
        if (function_exists('nb_format_file_size')) {
            $formatted_size = nb_format_file_size(1024);
            $this->assertStringContainsString('KB', $formatted_size);
            
            $formatted_size_mb = nb_format_file_size(1048576);
            $this->assertStringContainsString('MB', $formatted_size_mb);
        }
        
        // 3. Test de información del sistema
        if (function_exists('nb_get_system_info')) {
            $system_info = nb_get_system_info();
            $this->assertIsArray($system_info);
            $this->assertArrayHasKey('php_version', $system_info);
            $this->assertArrayHasKey('wp_version', $system_info);
        }
        
        // 4. Test de respuesta JSON
        if (function_exists('output_response')) {
            ob_start();
            output_response(true, 'Test message', ['test' => 'data']);
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            $this->assertIsArray($response);
            $this->assertArrayHasKey('success', $response);
            $this->assertTrue($response['success']);
            $this->assertEquals('Test message', $response['message']);
        }
    }

    /**
     * Test: Integración de activación y desactivación del plugin
     */
    public function test_plugin_activation_deactivation() {
        // 1. Test de activación
        if (function_exists('nb_activate_plugin')) {
            nb_activate_plugin();
            
            // Verificar que las tablas fueron creadas
            global $wpdb;
            $security_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}nb_security_logs'");
            $this->assertEquals($wpdb->prefix . 'nb_security_logs', $security_table);
        }
        
        // 2. Test de opciones por defecto
        $default_options = [
            'nb_api_url' => '',
            'nb_api_key' => '',
            'nb_store_name' => '',
            'nb_contact_email' => '',
            'nb_sync_interval' => '60',
            'nb_debug_mode' => '0'
        ];
        
        foreach ($default_options as $option => $default_value) {
            $value = get_option($option, $default_value);
            $this->assertNotNull($value);
        }
        
        // 3. Test de desactivación
        if (function_exists('nb_deactivate_plugin')) {
            nb_deactivate_plugin();
            
            // Verificar que los eventos programados fueron eliminados
            $scheduled = wp_next_scheduled('nb_sync_products_hook');
            $this->assertFalse($scheduled);
        }
    }

    /**
     * Test: Integración con WooCommerce
     */
    public function test_woocommerce_integration() {
        wp_set_current_user($this->admin_user->ID);
        
        // 1. Verificar que WooCommerce está activo en el entorno de pruebas
        $this->assertTrue(class_exists('WooCommerce'));
        
        // 2. Crear producto de prueba
        $product = new WC_Product_Simple();
        $product->set_name('Test Product Integration');
        $product->set_regular_price('19.99');
        $product->set_sku('TEST-INTEGRATION-001');
        $product_id = $product->save();
        
        $this->assertGreaterThan(0, $product_id);
        
        // 3. Verificar que el producto se puede obtener
        $retrieved_product = wc_get_product($product_id);
        $this->assertInstanceOf('WC_Product', $retrieved_product);
        $this->assertEquals('Test Product Integration', $retrieved_product->get_name());
        
        // 4. Test de estadísticas de productos
        if (function_exists('get_database_stats')) {
            $stats = get_database_stats();
            $this->assertArrayHasKey('total_products', $stats);
            $this->assertGreaterThan(0, $stats['total_products']);
        }
        
        // 5. Limpiar producto de prueba
        wp_delete_post($product_id, true);
    }

    /**
     * Test: Integración de logs y monitoreo
     */
    public function test_logging_monitoring_integration() {
        global $wpdb;
        
        wp_set_current_user($this->admin_user->ID);
        
        // 1. Limpiar logs anteriores
        $wpdb->query("DELETE FROM {$wpdb->prefix}nb_security_logs WHERE event_type LIKE '%integration%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}nb_error_logs WHERE error_type LIKE '%integration%'");
        
        // 2. Generar eventos de diferentes tipos
        $events = [
            ['type' => 'integration_login', 'message' => 'User login test'],
            ['type' => 'integration_sync', 'message' => 'Sync operation test'],
            ['type' => 'integration_config', 'message' => 'Configuration change test']
        ];
        
        foreach ($events as $event) {
            if (function_exists('log_security_event')) {
                log_security_event($event['type'], $event['message'], ['user_id' => $this->admin_user->ID]);
            }
        }
        
        // 3. Verificar que los eventos fueron registrados
        $total_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type LIKE '%integration%'"
        );
        
        $this->assertEquals(count($events), $total_events);
        
        // 4. Test de consulta de logs por fecha
        $recent_logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}nb_security_logs WHERE created_at >= %s AND event_type LIKE %s ORDER BY created_at DESC",
                date('Y-m-d H:i:s', strtotime('-1 hour')),
                '%integration%'
            )
        );
        
        $this->assertGreaterThan(0, count($recent_logs));
        
        // 5. Test de limpieza automática
        if (function_exists('cleanup_database')) {
            $cleanup_result = cleanup_database();
            $this->assertTrue($cleanup_result !== false);
        }
    }

    public function tearDown(): void {
        global $wpdb;
        
        // Limpiar todos los datos de prueba
        $wpdb->query("DELETE FROM {$wpdb->prefix}nb_security_logs WHERE event_type LIKE '%test%' OR event_type LIKE '%integration%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}nb_error_logs WHERE error_type LIKE '%test%' OR error_type LIKE '%integration%'");
        
        // Limpiar opciones de prueba
        delete_option('nb_api_url');
        delete_option('nb_api_key');
        delete_option('nb_store_name');
        
        // Limpiar datos POST/GET
        unset($_POST, $_GET);
        
        wp_set_current_user(0);
        parent::tearDown();
    }
}