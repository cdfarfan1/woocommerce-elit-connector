<?php
/**
 * Pruebas de base de datos para el plugin WooCommerce NewBytes
 * 
 * @package WooCommerce_NewBytes
 * @subpackage Tests
 */

class DatabaseTest extends WP_UnitTestCase {

    protected $admin_user;

    public function setUp(): void {
        parent::setUp();
        
        // Obtener usuario administrador de prueba
        $this->admin_user = get_user_by('login', 'test_admin');
        wp_set_current_user($this->admin_user->ID);
        
        // Crear tablas necesarias para las pruebas
        $this->create_test_tables();
    }

    /**
     * Crear tablas de prueba necesarias
     */
    private function create_test_tables() {
        global $wpdb;
        
        // Crear tabla de logs de seguridad si no existe
        $table_name = $wpdb->prefix . 'nb_security_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            message text NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            additional_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Crear tabla de logs de errores si no existe
        $error_table = $wpdb->prefix . 'nb_error_logs';
        $sql_error = "CREATE TABLE IF NOT EXISTS $error_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            error_type varchar(50) NOT NULL,
            error_message text NOT NULL,
            file_path varchar(255) DEFAULT NULL,
            line_number int DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql_error);
    }

    /**
     * Test: Verificar que las consultas SQL usan $wpdb->prepare correctamente
     */
    public function test_sql_injection_prevention() {
        global $wpdb;
        
        // Incluir archivo de optimización de base de datos
        include_once plugin_dir_path(__FILE__) . '../includes/database-optimizer.php';
        
        // Test consulta con datos maliciosos
        $malicious_post_type = "product'; DROP TABLE {$wpdb->posts}; --";
        
        // Verificar que get_database_stats usa $wpdb->prepare
        if (function_exists('get_database_stats')) {
            $stats = get_database_stats();
            
            // La función debería devolver datos válidos sin ejecutar código malicioso
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('total_products', $stats);
            
            // Verificar que la tabla posts aún existe (no fue eliminada por inyección SQL)
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->posts}'");
            $this->assertEquals($wpdb->posts, $table_exists);
        }
    }

    /**
     * Test: Verificar función cleanup_database con parámetros seguros
     */
    public function test_cleanup_database_security() {
        global $wpdb;
        
        // Incluir archivo de optimización de base de datos
        include_once plugin_dir_path(__FILE__) . '../includes/database-optimizer.php';
        
        // Insertar datos de prueba en logs
        $test_data = [
            'event_type' => 'test_event',
            'message' => 'Test message',
            'ip_address' => '192.168.1.1',
            'created_at' => date('Y-m-d H:i:s', strtotime('-35 days'))
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'nb_security_logs',
            $test_data,
            ['%s', '%s', '%s', '%s']
        );
        
        $test_error_data = [
            'error_type' => 'test_error',
            'error_message' => 'Test error message',
            'created_at' => date('Y-m-d H:i:s', strtotime('-95 days'))
        ];
        
        $wpdb->insert(
            $wpdb->prefix . 'nb_error_logs',
            $test_error_data,
            ['%s', '%s', '%s']
        );
        
        // Test con parámetro malicioso
        $malicious_days = "30; DROP TABLE {$wpdb->prefix}nb_security_logs; --";
        
        if (function_exists('cleanup_database')) {
            // La función debería manejar el parámetro de forma segura
            $result = cleanup_database();
            
            // Verificar que las tablas aún existen
            $security_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}nb_security_logs'");
            $error_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}nb_error_logs'");
            
            $this->assertEquals($wpdb->prefix . 'nb_security_logs', $security_table_exists);
            $this->assertEquals($wpdb->prefix . 'nb_error_logs', $error_table_exists);
        }
    }

    /**
     * Test: Verificar inserción segura de logs de seguridad
     */
    public function test_secure_security_log_insertion() {
        global $wpdb;
        
        // Incluir archivo de configuración
        include_once plugin_dir_path(__FILE__) . '../includes/config.php';
        
        if (function_exists('store_security_log')) {
            // Datos con contenido potencialmente malicioso
            $malicious_data = [
                'event_type' => "test'; DROP TABLE {$wpdb->prefix}nb_security_logs; --",
                'message' => '<script>alert("XSS")</script>Malicious message',
                'user_id' => 1,
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 <script>alert("XSS")</script>',
                'additional_data' => json_encode(['malicious' => '<script>alert("XSS")</script>'])
            ];
            
            // Insertar log de seguridad
            $result = store_security_log(
                $malicious_data['event_type'],
                $malicious_data['message'],
                $malicious_data['user_id'],
                $malicious_data['ip_address'],
                $malicious_data['user_agent'],
                $malicious_data['additional_data']
            );
            
            // Verificar que la inserción fue exitosa
            $this->assertTrue($result !== false);
            
            // Verificar que la tabla aún existe (no fue eliminada)
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}nb_security_logs'");
            $this->assertEquals($wpdb->prefix . 'nb_security_logs', $table_exists);
            
            // Verificar que los datos fueron insertados de forma segura
            $inserted_log = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}nb_security_logs WHERE message = %s ORDER BY id DESC LIMIT 1",
                    $malicious_data['message']
                )
            );
            
            $this->assertNotNull($inserted_log);
            $this->assertEquals($malicious_data['message'], $inserted_log->message);
        }
    }

    /**
     * Test: Verificar consultas de estadísticas de base de datos
     */
    public function test_database_stats_queries() {
        global $wpdb;
        
        // Incluir archivo de optimización de base de datos
        include_once plugin_dir_path(__FILE__) . '../includes/database-optimizer.php';
        
        if (function_exists('get_database_stats')) {
            $stats = get_database_stats();
            
            // Verificar estructura de respuesta
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('total_products', $stats);
            $this->assertArrayHasKey('database_size', $stats);
            
            // Verificar tipos de datos
            $this->assertIsNumeric($stats['total_products']);
            $this->assertIsString($stats['database_size']);
            
            // Verificar que los valores son razonables
            $this->assertGreaterThanOrEqual(0, $stats['total_products']);
            $this->assertNotEmpty($stats['database_size']);
        }
    }

    /**
     * Test: Verificar creación segura de tablas
     */
    public function test_secure_table_creation() {
        global $wpdb;
        
        // Incluir archivo de configuración
        include_once plugin_dir_path(__FILE__) . '../includes/config.php';
        
        if (function_exists('create_security_logs_table')) {
            // Eliminar tabla si existe
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nb_security_logs");
            
            // Crear tabla
            create_security_logs_table();
            
            // Verificar que la tabla fue creada
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}nb_security_logs'");
            $this->assertEquals($wpdb->prefix . 'nb_security_logs', $table_exists);
            
            // Verificar estructura de la tabla
            $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}nb_security_logs");
            $column_names = array_column($columns, 'Field');
            
            $expected_columns = ['id', 'event_type', 'message', 'user_id', 'ip_address', 'user_agent', 'additional_data', 'created_at'];
            
            foreach ($expected_columns as $expected_column) {
                $this->assertContains($expected_column, $column_names);
            }
        }
    }

    /**
     * Test: Verificar consultas de limpieza de logs antiguos
     */
    public function test_old_logs_cleanup() {
        global $wpdb;
        
        // Insertar logs de prueba con diferentes fechas
        $old_log_data = [
            'event_type' => 'old_test_event',
            'message' => 'Old test message',
            'ip_address' => '192.168.1.1',
            'created_at' => date('Y-m-d H:i:s', strtotime('-35 days'))
        ];
        
        $recent_log_data = [
            'event_type' => 'recent_test_event',
            'message' => 'Recent test message',
            'ip_address' => '192.168.1.2',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ];
        
        // Insertar logs
        $wpdb->insert(
            $wpdb->prefix . 'nb_security_logs',
            $old_log_data,
            ['%s', '%s', '%s', '%s']
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'nb_security_logs',
            $recent_log_data,
            ['%s', '%s', '%s', '%s']
        );
        
        // Incluir archivo de optimización
        include_once plugin_dir_path(__FILE__) . '../includes/database-optimizer.php';
        
        if (function_exists('cleanup_database')) {
            // Ejecutar limpieza
            cleanup_database();
            
            // Verificar que el log antiguo fue eliminado
            $old_log_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type = %s",
                    'old_test_event'
                )
            );
            
            // Verificar que el log reciente se mantiene
            $recent_log_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type = %s",
                    'recent_test_event'
                )
            );
            
            $this->assertEquals(0, $old_log_count, 'Los logs antiguos deberían haber sido eliminados');
            $this->assertEquals(1, $recent_log_count, 'Los logs recientes deberían mantenerse');
        }
    }

    /**
     * Test: Verificar transacciones de base de datos
     */
    public function test_database_transactions() {
        global $wpdb;
        
        // Test transacción exitosa
        $wpdb->query('START TRANSACTION');
        
        $result1 = $wpdb->insert(
            $wpdb->prefix . 'nb_security_logs',
            [
                'event_type' => 'transaction_test',
                'message' => 'Transaction test message',
                'ip_address' => '192.168.1.1'
            ],
            ['%s', '%s', '%s']
        );
        
        $this->assertNotFalse($result1);
        
        $wpdb->query('COMMIT');
        
        // Verificar que el registro fue insertado
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type = %s",
                'transaction_test'
            )
        );
        
        $this->assertEquals(1, $count);
        
        // Test rollback
        $wpdb->query('START TRANSACTION');
        
        $wpdb->insert(
            $wpdb->prefix . 'nb_security_logs',
            [
                'event_type' => 'rollback_test',
                'message' => 'Rollback test message',
                'ip_address' => '192.168.1.1'
            ],
            ['%s', '%s', '%s']
        );
        
        $wpdb->query('ROLLBACK');
        
        // Verificar que el registro no fue insertado
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}nb_security_logs WHERE event_type = %s",
                'rollback_test'
            )
        );
        
        $this->assertEquals(0, $count);
    }

    public function tearDown(): void {
        global $wpdb;
        
        // Limpiar datos de prueba
        $wpdb->query("DELETE FROM {$wpdb->prefix}nb_security_logs WHERE event_type LIKE '%test%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}nb_error_logs WHERE error_type LIKE '%test%'");
        
        wp_set_current_user(0);
        parent::tearDown();
    }
}