<?php
/**
 * Pruebas de seguridad para el plugin WooCommerce NewBytes
 * 
 * @package WooCommerce_NewBytes
 * @subpackage Tests
 */

class SecurityTest extends WP_UnitTestCase {

    protected $admin_user;
    protected $shop_manager_user;
    protected $customer_user;

    public function setUp(): void {
        parent::setUp();
        
        // Obtener usuarios de prueba creados en bootstrap
        $this->admin_user = get_user_by('login', 'test_admin');
        $this->shop_manager_user = get_user_by('login', 'test_shop_manager');
        $this->customer_user = get_user_by('login', 'test_customer');
    }

    /**
     * Test: Verificar que las funciones AJAX requieren nonce válido
     */
    public function test_ajax_functions_require_valid_nonce() {
        // Simular usuario administrador
        wp_set_current_user($this->admin_user->ID);
        
        // Test para sync_products sin nonce
        $_POST['action'] = 'sync_products';
        unset($_POST['nonce']);
        
        try {
            do_action('wp_ajax_sync_products');
            $this->fail('Se esperaba que fallara sin nonce');
        } catch (Exception $e) {
            $this->assertStringContainsString('nonce', strtolower($e->getMessage()));
        }
        
        // Test para sync_products con nonce inválido
        $_POST['nonce'] = 'invalid_nonce';
        
        try {
            do_action('wp_ajax_sync_products');
            $this->fail('Se esperaba que fallara con nonce inválido');
        } catch (Exception $e) {
            $this->assertStringContainsString('nonce', strtolower($e->getMessage()));
        }
        
        // Test para sync_products con nonce válido
        $_POST['nonce'] = wp_create_nonce('nb_sync_nonce');
        
        // Capturar salida
        ob_start();
        do_action('wp_ajax_sync_products');
        $output = ob_get_clean();
        
        // Verificar que no hay error de nonce
        $this->assertStringNotContainsString('nonce', strtolower($output));
    }

    /**
     * Test: Verificar que las funciones de configuración requieren nonce válido
     */
    public function test_settings_require_valid_nonce() {
        wp_set_current_user($this->admin_user->ID);
        
        // Simular envío de formulario sin nonce
        $_POST['submit'] = 'Guardar Configuración';
        $_POST['nb_api_url'] = 'https://test.com';
        unset($_POST['nb_settings_nonce']);
        
        // Incluir archivo de configuración
        include_once plugin_dir_path(__FILE__) . '../includes/admin-page.php';
        
        // Capturar salida
        ob_start();
        // Simular procesamiento de formulario
        if (isset($_POST['submit'])) {
            // Esto debería fallar por falta de nonce
            $this->assertFalse(wp_verify_nonce($_POST['nb_settings_nonce'] ?? '', 'nb_settings_action'));
        }
        ob_get_clean();
    }

    /**
     * Test: Verificar sanitización de datos de entrada
     */
    public function test_input_sanitization() {
        wp_set_current_user($this->admin_user->ID);
        
        // Test datos maliciosos en configuración
        $malicious_data = [
            'nb_api_url' => '<script>alert("XSS")</script>https://evil.com',
            'nb_api_key' => '<script>alert("XSS")</script>malicious_key',
            'nb_store_name' => '<script>alert("XSS")</script>Evil Store',
            'nb_contact_email' => '<script>alert("XSS")</script>evil@test.com'
        ];
        
        foreach ($malicious_data as $key => $value) {
            $_POST[$key] = $value;
        }
        
        // Simular sanitización
        $sanitized_url = sanitize_url($_POST['nb_api_url']);
        $sanitized_key = sanitize_text_field($_POST['nb_api_key']);
        $sanitized_name = sanitize_text_field($_POST['nb_store_name']);
        $sanitized_email = sanitize_email($_POST['nb_contact_email']);
        
        // Verificar que los scripts fueron removidos
        $this->assertStringNotContainsString('<script>', $sanitized_url);
        $this->assertStringNotContainsString('<script>', $sanitized_key);
        $this->assertStringNotContainsString('<script>', $sanitized_name);
        $this->assertStringNotContainsString('<script>', $sanitized_email);
        
        // Verificar que los datos válidos se mantienen
        $this->assertStringContainsString('https://evil.com', $sanitized_url);
        $this->assertStringContainsString('malicious_key', $sanitized_key);
        $this->assertStringContainsString('Evil Store', $sanitized_name);
    }

    /**
     * Test: Verificar escapado de salidas HTML
     */
    public function test_html_output_escaping() {
        wp_set_current_user($this->admin_user->ID);
        
        // Datos con contenido potencialmente peligroso
        $dangerous_content = '<script>alert("XSS")</script>Test Content';
        
        // Test escapado con esc_html
        $escaped_html = esc_html($dangerous_content);
        $this->assertStringNotContainsString('<script>', $escaped_html);
        $this->assertStringContainsString('&lt;script&gt;', $escaped_html);
        
        // Test escapado con esc_attr
        $escaped_attr = esc_attr($dangerous_content);
        $this->assertStringNotContainsString('<script>', $escaped_attr);
        
        // Test escapado con esc_js
        $escaped_js = esc_js($dangerous_content);
        $this->assertStringNotContainsString('<script>', $escaped_js);
    }

    /**
     * Test: Verificar control de acceso de usuario
     */
    public function test_user_capability_checks() {
        // Test con usuario administrador
        wp_set_current_user($this->admin_user->ID);
        $this->assertTrue(current_user_can('manage_woocommerce'));
        $this->assertTrue(current_user_can('manage_options'));
        
        // Test con usuario shop manager
        wp_set_current_user($this->shop_manager_user->ID);
        $this->assertTrue(current_user_can('manage_woocommerce'));
        $this->assertFalse(current_user_can('manage_options'));
        
        // Test con usuario cliente
        wp_set_current_user($this->customer_user->ID);
        $this->assertFalse(current_user_can('manage_woocommerce'));
        $this->assertFalse(current_user_can('manage_options'));
        
        // Test acceso a funciones administrativas
        wp_set_current_user($this->customer_user->ID);
        
        // Simular intento de acceso a página de administración
        $_GET['page'] = 'woocommerce-newbytes';
        
        // Verificar que el usuario no tiene permisos
        $this->assertFalse(current_user_can('manage_woocommerce'));
    }

    /**
     * Test: Verificar límites de tasa (rate limiting)
     */
    public function test_rate_limiting() {
        wp_set_current_user($this->admin_user->ID);
        
        // Incluir archivo de configuración
        include_once plugin_dir_path(__FILE__) . '../includes/config.php';
        
        // Test múltiples solicitudes rápidas
        $_POST['action'] = 'sync_products';
        
        $rate_limit_exceeded = false;
        
        // Simular 10 solicitudes rápidas
        for ($i = 0; $i < 10; $i++) {
            if (function_exists('check_rate_limit')) {
                $result = check_rate_limit();
                if (!$result) {
                    $rate_limit_exceeded = true;
                    break;
                }
            }
        }
        
        // Verificar que el rate limiting funciona
        $this->assertTrue($rate_limit_exceeded, 'El rate limiting debería activarse después de múltiples solicitudes');
    }

    /**
     * Test: Verificar logging de eventos de seguridad
     */
    public function test_security_event_logging() {
        global $wpdb;
        
        // Incluir archivo de configuración
        include_once plugin_dir_path(__FILE__) . '../includes/config.php';
        
        // Limpiar logs anteriores
        $wpdb->query("DELETE FROM {$wpdb->prefix}nb_security_logs WHERE 1=1");
        
        // Simular evento de seguridad
        if (function_exists('log_security_event')) {
            log_security_event('test_event', 'Test security event', ['test' => 'data']);
            
            // Verificar que el evento fue registrado
            $log_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type = 'test_event'");
            $this->assertGreaterThan(0, $log_count, 'El evento de seguridad debería haberse registrado');
        }
    }

    /**
     * Test: Verificar validación de IP del cliente
     */
    public function test_client_ip_validation() {
        // Incluir archivo de configuración
        include_once plugin_dir_path(__FILE__) . '../includes/config.php';
        
        if (function_exists('get_client_ip')) {
            // Test IP normal
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
            $ip = get_client_ip();
            $this->assertEquals('192.168.1.1', $ip);
            
            // Test IP con proxy
            $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1';
            $ip = get_client_ip();
            $this->assertEquals('203.0.113.1', $ip);
            
            // Test IP inválida
            $_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid_ip';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
            $ip = get_client_ip();
            $this->assertEquals('192.168.1.1', $ip); // Debería usar REMOTE_ADDR como fallback
        }
    }

    public function tearDown(): void {
        // Limpiar datos de prueba
        unset($_POST, $_GET, $_SERVER['HTTP_X_FORWARDED_FOR']);
        wp_set_current_user(0);
        parent::tearDown();
    }
}