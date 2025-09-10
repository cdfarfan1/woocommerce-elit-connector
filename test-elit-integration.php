<?php
/**
 * Test file for ELIT integration
 * 
 * This file can be used to test the ELIT API integration
 * Run this from WordPress admin or via WP-CLI
 */

// Only run if WordPress is loaded
if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

/**
 * Test ELIT API integration
 */
function test_elit_integration() {
    // Check if required classes exist
    if (!class_exists('ELIT_API_Manager')) {
        return 'Error: ELIT_API_Manager class not found. Make sure the plugin is activated.';
    }
    
    echo "<h2>Prueba de Integración ELIT</h2>";
    
    // Test 1: Check credentials
    echo "<h3>1. Verificando credenciales...</h3>";
    $user_id = get_option('elit_user_id');
    $token = get_option('elit_token');
    
    if (empty($user_id) || empty($token)) {
        echo "<p style='color: red;'>❌ Credenciales no configuradas</p>";
        echo "<p>Configure User ID y Token en Ajustes > Conector ELIT</p>";
        return;
    }
    
    echo "<p style='color: green;'>✅ Credenciales configuradas (User ID: {$user_id})</p>";
    
    // Test 2: Test connection
    echo "<h3>2. Probando conexión con ELIT API...</h3>";
    $connection_test = ELIT_API_Manager::test_connection();
    
    if ($connection_test['success']) {
        echo "<p style='color: green;'>✅ {$connection_test['message']}</p>";
    } else {
        echo "<p style='color: red;'>❌ {$connection_test['message']}</p>";
        return;
    }
    
    // Test 3: Get sample products
    echo "<h3>3. Obteniendo productos de muestra...</h3>";
    $products = ELIT_API_Manager::get_all_products(5); // Get only 5 products for testing
    
    if (empty($products)) {
        echo "<p style='color: red;'>❌ No se pudieron obtener productos</p>";
        return;
    }
    
    echo "<p style='color: green;'>✅ Se obtuvieron " . count($products) . " productos</p>";
    
    // Test 4: Transform product data
    echo "<h3>4. Probando transformación de datos...</h3>";
    $sample_product = $products[0];
    $transformed = ELIT_API_Manager::transform_product_data($sample_product);
    
    if ($transformed) {
        echo "<p style='color: green;'>✅ Transformación exitosa</p>";
        echo "<h4>Producto de ejemplo:</h4>";
        echo "<ul>";
        echo "<li><strong>SKU:</strong> " . ($transformed['sku'] ?? 'N/A') . "</li>";
        echo "<li><strong>Nombre:</strong> " . ($transformed['name'] ?? 'N/A') . "</li>";
        echo "<li><strong>Precio:</strong> $" . ($transformed['price'] ?? '0') . "</li>";
        echo "<li><strong>Stock:</strong> " . ($transformed['stock_quantity'] ?? '0') . "</li>";
        echo "<li><strong>Categorías:</strong> " . implode(', ', $transformed['categories'] ?? array()) . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Error en transformación de datos</p>";
        return;
    }
    
    // Test 5: Check sync functionality
    echo "<h3>5. Verificando funciones de sincronización...</h3>";
    
    if (function_exists('elit_callback')) {
        echo "<p style='color: green;'>✅ Función elit_callback disponible</p>";
    } else {
        echo "<p style='color: red;'>❌ Función elit_callback no encontrada</p>";
    }
    
    if (class_exists('NB_Product_Sync')) {
        echo "<p style='color: green;'>✅ Clase NB_Product_Sync disponible</p>";
    } else {
        echo "<p style='color: red;'>❌ Clase NB_Product_Sync no encontrada</p>";
    }
    
    echo "<h3>✅ Prueba completada exitosamente</h3>";
    echo "<p>El plugin está listo para sincronizar productos de ELIT.</p>";
    
    // Show raw data for debugging (first product only)
    echo "<h3>Datos en bruto (primer producto):</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
    echo htmlspecialchars(json_encode($sample_product, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre>";
}

// Run test if accessed directly via admin
if (is_admin() && isset($_GET['test_elit'])) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info">';
        test_elit_integration();
        echo '</div>';
    });
}

/**
 * Add test link to admin menu
 */
function add_elit_test_menu() {
    add_submenu_page(
        'options-general.php',
        'Test ELIT Integration',
        'Test ELIT',
        'manage_options',
        'test-elit',
        'test_elit_integration'
    );
}

// Only add menu if we're in admin
if (is_admin()) {
    add_action('admin_menu', 'add_elit_test_menu');
}
