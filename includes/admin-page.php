<?php
/**
 * NewBytes WooCommerce Connector - Admin Page
 * 
 * @package NewBytes_WooCommerce_Connector
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Admin Page Class
 *
 * Handles the WordPress admin interface for the NewBytes WooCommerce Connector.
 * Provides dashboard, settings, and logs pages with AJAX functionality.
 *
 * @since 1.0.0
 */
class NB_Admin_Page {
    
    private static $instance = null;
    private $page_hook;
    
    /**
     * Get singleton instance
     *
     * Returns the single instance of the admin page class.
     *
     * @since 1.0.0
     * @return NB_Admin_Page The singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     *
     * Private constructor to prevent direct instantiation.
     * Use get_instance() instead.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize admin page
     *
     * Sets up WordPress hooks for admin menu, scripts, and AJAX handlers.
     *
     * @since 1.0.0
     * @return void
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_nb_sync_products', array($this, 'ajax_sync_products'));
        add_action('wp_ajax_nb_get_sync_status', array($this, 'ajax_get_sync_status'));
        add_action('wp_ajax_nb_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_nb_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_nb_test_connection', array($this, 'ajax_test_connection'));
    }
    
    /**
     * Add admin menu
     *
     * Creates the main menu page and submenus for the plugin in WordPress admin.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu() {
        $this->page_hook = add_menu_page(
            'NewBytes Connector',
            'NewBytes',
            'manage_woocommerce',
            'newbytes-connector',
            array($this, 'render_admin_page'),
            'dashicons-update',
            56
        );
        
        // Add submenu pages
        add_submenu_page(
            'newbytes-connector',
            'Dashboard',
            'Dashboard',
            'manage_woocommerce',
            'newbytes-connector',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'newbytes-connector',
            'Configuración',
            'Configuración',
            'manage_woocommerce',
            'newbytes-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'newbytes-connector',
            'Logs',
            'Logs',
            'manage_woocommerce',
            'newbytes-logs',
            array($this, 'render_logs_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * Loads CSS and JavaScript files for the admin pages, including localization.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'newbytes') === false) {
            return;
        }
        
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        
        // Enqueue custom styles
        wp_enqueue_style(
            'nb-admin-style',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array(),
            NB_VERSION
        );
        
        // Enqueue custom scripts
        wp_enqueue_script(
            'nb-admin-script',
            plugin_dir_url(__FILE__) . '../assets/js/admin.js',
            array('jquery', 'wp-util'),
            NB_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('nb-admin-script', 'nbAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nb_admin_nonce'),
            'strings' => array(
                'confirmSync' => '¿Estás seguro de que quieres sincronizar los productos?',
                'confirmClearLogs' => '¿Estás seguro de que quieres limpiar todos los logs?',
                'syncInProgress' => 'Sincronización en progreso...',
                'syncCompleted' => 'Sincronización completada',
                'syncFailed' => 'Error en la sincronización',
                'connectionTesting' => 'Probando conexión...',
                'connectionSuccess' => 'Conexión exitosa',
                'connectionFailed' => 'Error de conexión'
            )
        ));
    }
    
    /**
     * Render main admin page
     *
     * Displays the main dashboard with statistics, sync controls, and system information.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        
        // Get statistics
        $stats = $this->get_dashboard_stats();
        $sync_status = get_option('nb_sync_status', 'idle');
        $last_sync = get_option('nb_last_sync_time', 'Nunca');
        
        ?>
        <div class="wrap nb-admin-wrap">
            <h1 class="nb-page-title">
                <span class="dashicons dashicons-update"></span>
                NewBytes WooCommerce Connector
            </h1>
            
            <div class="nb-admin-header">
                <div class="nb-status-cards">
                    <div class="nb-card nb-card-primary">
                        <div class="nb-card-icon">
                            <span class="dashicons dashicons-products"></span>
                        </div>
                        <div class="nb-card-content">
                            <h3><?php echo number_format($stats['total_products']); ?></h3>
                            <p>Productos Totales</p>
                        </div>
                    </div>
                    
                    <div class="nb-card nb-card-success">
                        <div class="nb-card-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="nb-card-content">
                            <h3><?php echo number_format($stats['nb_products']); ?></h3>
                            <p>Productos NewBytes</p>
                        </div>
                    </div>
                    
                    <div class="nb-card nb-card-warning">
                        <div class="nb-card-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="nb-card-content">
                            <h3><?php echo esc_html($last_sync !== 'Nunca' ? human_time_diff(strtotime($last_sync)) . ' ago' : 'Nunca'); ?></h3>
                            <p>Última Sincronización</p>
                        </div>
                    </div>
                    
                    <div class="nb-card nb-card-info">
                        <div class="nb-card-icon">
                            <span class="dashicons dashicons-database"></span>
                        </div>
                        <div class="nb-card-content">
                            <h3><?php echo esc_html($stats['database_size']); ?></h3>
                            <p>Tamaño BD</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="nb-admin-content">
                <div class="nb-main-panel">
                    <div class="nb-panel nb-sync-panel">
                        <div class="nb-panel-header">
                            <h2><span class="dashicons dashicons-update"></span> Sincronización de Productos</h2>
                        </div>
                        <div class="nb-panel-content">
                            <div class="nb-sync-status">
                                <div class="nb-status-indicator nb-status-<?php echo esc_attr($sync_status); ?>">
                                    <span class="nb-status-dot"></span>
                                    <span class="nb-status-text">
                                        <?php 
                                        switch($sync_status) {
                                            case 'running':
                                                echo 'Sincronización en progreso';
                                                break;
                                            case 'completed':
                                                echo 'Última sincronización completada';
                                                break;
                                            case 'error':
                                                echo 'Error en la última sincronización';
                                                break;
                                            default:
                                                echo 'Listo para sincronizar';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="nb-sync-actions">
                                <button type="button" class="button button-primary nb-btn-sync" 
                                        <?php echo esc_attr($sync_status === 'running' ? 'disabled' : ''); ?>>
                                    <span class="dashicons dashicons-update"></span>
                                    Sincronizar Productos
                                </button>
                                
                                <button type="button" class="button nb-btn-test-connection">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    Probar Conexión
                                </button>
                            </div>
                            
                            <div class="nb-sync-progress" style="display: none;">
                                <div class="nb-progress-bar">
                                    <div class="nb-progress-fill"></div>
                                </div>
                                <div class="nb-progress-text">Preparando sincronización...</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="nb-panel nb-recent-activity">
                        <div class="nb-panel-header">
                            <h2><span class="dashicons dashicons-list-view"></span> Actividad Reciente</h2>
                        </div>
                        <div class="nb-panel-content">
                            <div class="nb-activity-list">
                                <?php $this->render_recent_activity(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="nb-sidebar">
                    <div class="nb-panel nb-system-info">
                        <div class="nb-panel-header">
                            <h3><span class="dashicons dashicons-info"></span> Información del Sistema</h3>
                        </div>
                        <div class="nb-panel-content">
                            <div class="nb-info-list">
                                <div class="nb-info-item">
                                    <span class="nb-info-label">Plugin Version:</span>
                                    <span class="nb-info-value"><?php echo NB_VERSION; ?></span>
                                </div>
                                <div class="nb-info-item">
                                    <span class="nb-info-label">WordPress:</span>
                                    <span class="nb-info-value"><?php echo get_bloginfo('version'); ?></span>
                                </div>
                                <div class="nb-info-item">
                                    <span class="nb-info-label">WooCommerce:</span>
                                    <span class="nb-info-value"><?php echo defined('WC_VERSION') ? WC_VERSION : 'No instalado'; ?></span>
                                </div>
                                <div class="nb-info-item">
                                    <span class="nb-info-label">PHP:</span>
                                    <span class="nb-info-value"><?php echo PHP_VERSION; ?></span>
                                </div>
                                <div class="nb-info-item">
                                    <span class="nb-info-label">Memoria PHP:</span>
                                    <span class="nb-info-value"><?php echo ini_get('memory_limit'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="nb-panel nb-quick-actions">
                        <div class="nb-panel-header">
                            <h3><span class="dashicons dashicons-admin-tools"></span> Acciones Rápidas</h3>
                        </div>
                        <div class="nb-panel-content">
                            <div class="nb-quick-actions-list">
                                <a href="<?php echo admin_url('admin.php?page=newbytes-settings'); ?>" class="nb-quick-action">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    Configuración
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=newbytes-logs'); ?>" class="nb-quick-action">
                                    <span class="dashicons dashicons-text-page"></span>
                                    Ver Logs
                                </a>
                                <button type="button" class="nb-quick-action nb-btn-clear-cache">
                                    <span class="dashicons dashicons-trash"></span>
                                    Limpiar Caché
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     *
     * Displays the plugin settings form with API configuration, sync options, and advanced settings.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['nb_settings_nonce'], 'nb_save_settings')) {
            $this->save_settings();
        }
        
        $settings = $this->get_settings();
        
        ?>
        <div class="wrap nb-admin-wrap">
            <h1 class="nb-page-title">
                <span class="dashicons dashicons-admin-settings"></span>
                Configuración NewBytes
            </h1>
            
            <form method="post" action="" class="nb-settings-form">
                <?php wp_nonce_field('nb_save_settings', 'nb_settings_nonce'); ?>
                
                <div class="nb-settings-tabs">
                    <nav class="nb-tab-nav">
                        <a href="#api-settings" class="nb-tab-link active">API Settings</a>
                        <a href="#sync-settings" class="nb-tab-link">Sincronización</a>
                        <a href="#advanced-settings" class="nb-tab-link">Avanzado</a>
                    </nav>
                    
                    <div class="nb-tab-content">
                        <div id="api-settings" class="nb-tab-panel active">
                            <h2>Configuración de API</h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">URL de la API</th>
                                    <td>
                                        <input type="url" name="nb_api_url" value="<?php echo esc_attr($settings['api_url']); ?>" 
                                               class="regular-text" placeholder="https://api.newbytes.com" />
                                        <p class="description">URL base de la API de NewBytes</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Usuario API</th>
                                    <td>
                                        <input type="text" name="nb_api_user" value="<?php echo esc_attr($settings['api_user']); ?>" 
                                               class="regular-text" />
                                        <p class="description">Nombre de usuario para la API</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Contraseña API</th>
                                    <td>
                                        <input type="password" name="nb_api_password" value="<?php echo esc_attr($settings['api_password']); ?>" 
                                               class="regular-text" />
                                        <p class="description">Contraseña para la API</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Prefijo SKU</th>
                                    <td>
                                        <input type="text" name="nb_sku_prefix" value="<?php echo esc_attr($settings['sku_prefix']); ?>" 
                                               class="regular-text" placeholder="NB" />
                                        <p class="description">Prefijo para los SKUs de productos NewBytes</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div id="sync-settings" class="nb-tab-panel">
                            <h2>Configuración de Sincronización</h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Sincronización Automática</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="nb_auto_sync" value="1" 
                                                   <?php checked($settings['auto_sync'], 1); ?> />
                                            Habilitar sincronización automática
                                        </label>
                                        <p class="description">Sincronizar productos automáticamente cada hora</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Intervalo de Sincronización</th>
                                    <td>
                                        <select name="nb_sync_interval">
                                            <option value="hourly" <?php selected($settings['sync_interval'], 'hourly'); ?>>Cada hora</option>
                                            <option value="twicedaily" <?php selected($settings['sync_interval'], 'twicedaily'); ?>>Dos veces al día</option>
                                            <option value="daily" <?php selected($settings['sync_interval'], 'daily'); ?>>Diariamente</option>
                                        </select>
                                        <p class="description">Frecuencia de sincronización automática</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Límite de Productos por Lote</th>
                                    <td>
                                        <input type="number" name="nb_batch_size" value="<?php echo esc_attr($settings['batch_size']); ?>" 
                                               min="10" max="500" class="small-text" />
                                        <p class="description">Número de productos a procesar por lote (10-500)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Timeout de API</th>
                                    <td>
                                        <input type="number" name="nb_api_timeout" value="<?php echo esc_attr($settings['api_timeout']); ?>" 
                                               min="10" max="300" class="small-text" /> segundos
                                        <p class="description">Tiempo límite para las llamadas a la API (10-300 segundos)</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div id="advanced-settings" class="nb-tab-panel">
                            <h2>Configuración Avanzada</h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Modo Debug</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="nb_debug_mode" value="1" 
                                                   <?php checked($settings['debug_mode'], 1); ?> />
                                            Habilitar modo debug
                                        </label>
                                        <p class="description">Registrar información detallada en los logs</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Duración de Caché</th>
                                    <td>
                                        <input type="number" name="nb_cache_duration" value="<?php echo esc_attr($settings['cache_duration']); ?>" 
                                               min="60" max="3600" class="small-text" /> segundos
                                        <p class="description">Duración del caché para consultas de productos (60-3600 segundos)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Retención de Logs</th>
                                    <td>
                                        <input type="number" name="nb_log_retention" value="<?php echo esc_attr($settings['log_retention']); ?>" 
                                               min="7" max="365" class="small-text" /> días
                                        <p class="description">Días para mantener los logs antes de eliminarlos (7-365 días)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Notificaciones por Email</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="nb_email_notifications" value="1" 
                                                   <?php checked($settings['email_notifications'], 1); ?> />
                                            Enviar notificaciones por email para errores críticos
                                        </label>
                                        <br><br>
                                        <input type="email" name="nb_notification_email" 
                                               value="<?php echo esc_attr($settings['notification_email']); ?>" 
                                               class="regular-text" placeholder="admin@example.com" />
                                        <p class="description">Email para recibir notificaciones</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Guardar Configuración" />
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.'));
        }
        
        $log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : 'all';
        $logs = $this->get_logs($log_type);
        
        ?>
        <div class="wrap nb-admin-wrap">
            <h1 class="nb-page-title">
                <span class="dashicons dashicons-text-page"></span>
                Logs del Sistema
            </h1>
            
            <div class="nb-logs-header">
                <div class="nb-logs-filters">
                    <select id="nb-log-type-filter">
                        <option value="all" <?php selected($log_type, 'all'); ?>>Todos los logs</option>
                        <option value="sync" <?php selected($log_type, 'sync'); ?>>Sincronización</option>
                        <option value="error" <?php selected($log_type, 'error'); ?>>Errores</option>
                        <option value="security" <?php selected($log_type, 'security'); ?>>Seguridad</option>
                    </select>
                    
                    <button type="button" class="button nb-btn-refresh-logs">
                        <span class="dashicons dashicons-update"></span>
                        Actualizar
                    </button>
                    
                    <button type="button" class="button nb-btn-clear-logs">
                        <span class="dashicons dashicons-trash"></span>
                        Limpiar Logs
                    </button>
                </div>
            </div>
            
            <div class="nb-logs-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Nivel</th>
                            <th>Mensaje</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="nb-no-logs">No hay logs disponibles</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="nb-log-row nb-log-<?php echo esc_attr($log->level); ?>">
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->timestamp))); ?></td>
                                    <td><span class="nb-log-type"><?php echo esc_html($log->log_type); ?></span></td>
                                    <td><span class="nb-log-level nb-level-<?php echo esc_attr($log->level); ?>"><?php echo esc_html($log->level); ?></span></td>
                                    <td><?php echo esc_html($log->message); ?></td>
                                    <td>
                                        <?php if (!empty($log->context)): ?>
                                            <button type="button" class="button-link nb-show-details" data-details="<?php echo esc_attr($log->context); ?>">
                                                Ver detalles
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Modal for log details -->
        <div id="nb-log-details-modal" class="nb-modal" style="display: none;">
            <div class="nb-modal-content">
                <div class="nb-modal-header">
                    <h3>Detalles del Log</h3>
                    <button type="button" class="nb-modal-close">&times;</button>
                </div>
                <div class="nb-modal-body">
                    <pre id="nb-log-details-content"></pre>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     *
     * Retrieves statistics for the dashboard including product counts and database size.
     *
     * @since 1.0.0
     * @return array Array containing dashboard statistics
     */
    private function get_dashboard_stats() {
        if (function_exists('nb_get_database_stats')) {
            return nb_get_database_stats();
        }
        
        // Fallback if database optimizer is not available
        global $wpdb;
        
        $stats = array(
            'total_products' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'product')),
            'nb_products' => 0,
            'database_size' => 'N/A'
        );
        
        $nb_prefix = get_option('nb_sku_prefix', 'NB');
        $stats['nb_products'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'product'
             AND pm.meta_key = '_sku'
             AND pm.meta_value LIKE %s",
            $nb_prefix . '%'
        ));
        
        return $stats;
    }
    
    /**
     * Render recent activity
     *
     * Displays the most recent log entries in the dashboard activity section.
     *
     * @since 1.0.0
     * @return void
     */
    private function render_recent_activity() {
        $logs = $this->get_logs('all', 5);
        
        if (empty($logs)) {
            echo '<div class="nb-no-activity">No hay actividad reciente</div>';
            return;
        }
        
        foreach ($logs as $log) {
            $icon = $this->get_log_icon($log->log_type, $log->level);
            $time_ago = human_time_diff(strtotime($log->timestamp));
            
            echo '<div class="nb-activity-item">';
            echo '<div class="nb-activity-icon">' . $icon . '</div>';
            echo '<div class="nb-activity-content">';
            echo '<div class="nb-activity-message">' . esc_html($log->message) . '</div>';
            echo '<div class="nb-activity-time">' . $time_ago . ' ago</div>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Get log icon based on type and level
     *
     * Returns the appropriate dashicon class for a log entry based on its type and level.
     *
     * @since 1.0.0
     * @param string $type Log type (sync, error, security, info)
     * @param string $level Log level (error, warning, success, info)
     * @return string HTML string with dashicon span element
     */
    private function get_log_icon($type, $level) {
        $icons = array(
            'sync' => 'dashicons-update',
            'error' => 'dashicons-warning',
            'security' => 'dashicons-shield',
            'info' => 'dashicons-info'
        );
        
        $icon_class = isset($icons[$type]) ? $icons[$type] : 'dashicons-admin-generic';
        
        if ($level === 'error' || $level === 'critical') {
            $icon_class = 'dashicons-dismiss';
        } elseif ($level === 'warning') {
            $icon_class = 'dashicons-warning';
        } elseif ($level === 'success') {
            $icon_class = 'dashicons-yes-alt';
        }
        
        return '<span class="dashicons ' . $icon_class . '"></span>';
    }
    
    /**
     * Get settings
     *
     * Retrieves all plugin settings from WordPress options with default values.
     *
     * @since 1.0.0
     * @return array Array of plugin settings
     */
    private function get_settings() {
        return array(
            'api_url' => get_option('nb_api_url', ''),
            'api_user' => get_option('nb_api_user', ''),
            'api_password' => get_option('nb_api_password', ''),
            'sku_prefix' => get_option('nb_sku_prefix', 'NB'),
            'auto_sync' => get_option('nb_auto_sync', 0),
            'sync_interval' => get_option('nb_sync_interval', 'hourly'),
            'batch_size' => get_option('nb_batch_size', 50),
            'api_timeout' => get_option('nb_api_timeout', 30),
            'debug_mode' => get_option('nb_debug_mode', 0),
            'cache_duration' => get_option('nb_cache_duration', 300),
            'log_retention' => get_option('nb_log_retention', 30),
            'email_notifications' => get_option('nb_email_notifications', 0),
            'notification_email' => get_option('nb_notification_email', get_option('admin_email'))
        );
    }
    
    /**
     * Save settings
     *
     * Processes and saves plugin settings from the settings form.
     * Updates cron schedules and displays success notice.
     *
     * @since 1.0.0
     * @return void
     */
    private function save_settings() {
        $settings = array(
            'nb_api_url' => sanitize_url($_POST['nb_api_url']),
            'nb_api_user' => sanitize_text_field($_POST['nb_api_user']),
            'nb_api_password' => sanitize_text_field($_POST['nb_api_password']),
            'nb_sku_prefix' => sanitize_text_field($_POST['nb_sku_prefix']),
            'nb_auto_sync' => isset($_POST['nb_auto_sync']) ? 1 : 0,
            'nb_sync_interval' => sanitize_text_field($_POST['nb_sync_interval']),
            'nb_batch_size' => intval($_POST['nb_batch_size']),
            'nb_api_timeout' => intval($_POST['nb_api_timeout']),
            'nb_debug_mode' => isset($_POST['nb_debug_mode']) ? 1 : 0,
            'nb_cache_duration' => intval($_POST['nb_cache_duration']),
            'nb_log_retention' => intval($_POST['nb_log_retention']),
            'nb_email_notifications' => isset($_POST['nb_email_notifications']) ? 1 : 0,
            'nb_notification_email' => sanitize_email($_POST['nb_notification_email'])
        );
        
        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
        
        // Update cron schedule if auto sync settings changed
        if (isset($_POST['nb_auto_sync']) && $_POST['nb_auto_sync']) {
            wp_clear_scheduled_hook('nb_cron_sync_event');
            wp_schedule_event(time(), $_POST['nb_sync_interval'], 'nb_cron_sync_event');
        } else {
            wp_clear_scheduled_hook('nb_cron_sync_event');
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
        });
    }
    
    /**
     * Get logs from database
     *
     * Retrieves log entries from various log tables based on type and limit.
     *
     * @since 1.0.0
     * @param string $type Type of logs to retrieve (all, sync, error, security)
     * @param int $limit Maximum number of log entries to return
     * @return array Array of log objects
     */
    private function get_logs($type = 'all', $limit = 50) {
        global $wpdb;
        
        $tables = array();
        
        if ($type === 'all' || $type === 'sync') {
            $tables[] = $wpdb->prefix . 'nb_sync_logs';
        }
        if ($type === 'all' || $type === 'error') {
            $tables[] = $wpdb->prefix . 'nb_error_logs';
        }
        if ($type === 'all' || $type === 'security') {
            $tables[] = $wpdb->prefix . 'nb_security_logs';
        }
        
        if (empty($tables)) {
            return array();
        }
        
        $union_queries = array();
        
        foreach ($tables as $table) {
            // Check if table exists
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if ($table_exists) {
                if (strpos($table, 'sync_logs') !== false) {
                    $union_queries[] = "SELECT timestamp, 'sync' as log_type, 'info' as level, message, context FROM {$table}";
                } elseif (strpos($table, 'error_logs') !== false) {
                    $union_queries[] = "SELECT timestamp, error_type as log_type, 'error' as level, message, context FROM {$table}";
                } elseif (strpos($table, 'security_logs') !== false) {
                    $union_queries[] = "SELECT timestamp, event_type as log_type, 'warning' as level, message, context FROM {$table}";
                }
            }
        }
        
        if (empty($union_queries)) {
            return array();
        }
        
        $sql = '(' . implode(') UNION (', $union_queries) . ') ORDER BY timestamp DESC LIMIT %d';
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Manejador AJAX para sincronización de productos
     *
     * Procesa las solicitudes AJAX de sincronización de productos,
     * verifica permisos y nonce, e inicia el proceso de sincronización.
     *
     * @since 1.0.0
     * @return void Envía respuesta JSON y termina la ejecución
     */
    public function ajax_sync_products() {
        check_ajax_referer('nb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        // Set sync status to running
        update_option('nb_sync_status', 'running');
        
        try {
            // Use the product sync class if available
            if (class_exists('NB_Product_Sync')) {
                $sync = new NB_Product_Sync();
                $result = $sync->sync_products_from_api();
            } else {
                // Fallback to legacy function
                $result = array('success' => false, 'message' => 'Product sync class not available');
            }
            
            if ($result['success']) {
                update_option('nb_sync_status', 'completed');
                update_option('nb_last_sync_time', current_time('mysql'));
            } else {
                update_option('nb_sync_status', 'error');
            }
            
            wp_send_json($result);
            
        } catch (Exception $e) {
            update_option('nb_sync_status', 'error');
            wp_send_json(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Manejador AJAX para obtener el estado de sincronización
     *
     * Devuelve el estado actual de la sincronización de productos
     * incluyendo progreso, estadísticas y mensajes de estado.
     *
     * @since 1.0.0
     * @return void Envía respuesta JSON con el estado actual
     */
    public function ajax_get_sync_status() {
        check_ajax_referer('nb_admin_nonce', 'nonce');
        
        wp_send_json(array(
            'status' => get_option('nb_sync_status', 'idle'),
            'last_sync' => get_option('nb_last_sync_time', 'Nunca')
        ));
    }
    
    /**
     * Manejador AJAX para obtener estadísticas del dashboard
     *
     * Devuelve estadísticas actualizadas del sistema incluyendo
     * productos sincronizados, errores recientes y rendimiento.
     *
     * @since 1.0.0
     * @return void Envía respuesta JSON con las estadísticas
     */
    public function ajax_get_stats() {
        check_ajax_referer('nb_admin_nonce', 'nonce');
        
        wp_send_json($this->get_dashboard_stats());
    }
    
    /**
     * Manejador AJAX para limpiar logs del sistema
     *
     * Elimina todos los logs del sistema después de verificar
     * permisos de usuario y nonce de seguridad.
     *
     * @since 1.0.0
     * @return void Envía respuesta JSON con el resultado
     */
    public function ajax_clear_logs() {
        check_ajax_referer('nb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'nb_sync_logs',
            $wpdb->prefix . 'nb_error_logs',
            $wpdb->prefix . 'nb_security_logs'
        );
        
        $cleared = 0;
        
        foreach ($tables as $table) {
            $result = $wpdb->query("TRUNCATE TABLE {$table}");
            if ($result !== false) {
                $cleared++;
            }
        }
        
        wp_send_json(array(
            'success' => true,
            'message' => "Logs limpiados correctamente ({$cleared} tablas)"
        ));
    }
    
    /**
     * Manejador AJAX para probar la conexión con la API
     *
     * Verifica la conectividad con la API de NewBytes y
     * devuelve el estado de la conexión y posibles errores.
     *
     * @since 1.0.0
     * @return void Envía respuesta JSON con el resultado de la prueba
     */
    public function ajax_test_connection() {
        check_ajax_referer('nb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        try {
            // Test API connection
            if (function_exists('nb_get_token')) {
                $token = nb_get_token();
                if ($token) {
                    wp_send_json(array(
                        'success' => true,
                        'message' => 'Conexión exitosa con la API'
                    ));
                } else {
                    wp_send_json(array(
                        'success' => false,
                        'message' => 'Error al obtener token de autenticación'
                    ));
                }
            } else {
                wp_send_json(array(
                    'success' => false,
                    'message' => 'Función de conexión no disponible'
                ));
            }
        } catch (Exception $e) {
            wp_send_json(array(
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ));
        }
    }
}

// Initialize admin page
NB_Admin_Page::get_instance();