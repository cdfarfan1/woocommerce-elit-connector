<?php
/**
 * Script de prueba para la API de ELIT
 * Prueba la conexión y obtiene productos reales
 */

// Configuración de la API de ELIT
$api_url = 'https://clientes.elit.com.ar/v1/api/productos';
$user_id = 24560;
$token = 'z9qrpjjgnwq';

echo "🚀 PROBANDO CONEXIÓN CON API DE ELIT\n";
echo "=====================================\n\n";

// Configurar cURL
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $api_url . '?limit=5', // Solo 5 productos para prueba
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(array(
        'user_id' => $user_id,
        'token' => $token
    )),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

echo "📡 Realizando petición a: $api_url\n";
echo "👤 User ID: $user_id\n";
echo "🔑 Token: " . substr($token, 0, 3) . "***" . substr($token, -3) . "\n\n";

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);

curl_close($curl);

// Verificar errores de cURL
if ($error) {
    echo "❌ ERROR DE CURL: $error\n";
    exit(1);
}

echo "📊 HTTP Status Code: $http_code\n";

if ($http_code !== 200) {
    echo "❌ ERROR HTTP: Código $http_code\n";
    echo "📄 Respuesta:\n";
    echo $response . "\n";
    exit(1);
}

// Decodificar JSON
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ ERROR JSON: " . json_last_error_msg() . "\n";
    echo "📄 Respuesta raw:\n";
    echo $response . "\n";
    exit(1);
}

echo "✅ CONEXIÓN EXITOSA!\n\n";

// Mostrar información de los productos
if (is_array($data) && !empty($data)) {
    echo "📦 PRODUCTOS OBTENIDOS: " . count($data) . "\n";
    echo "=====================================\n\n";
    
    foreach ($data as $index => $product) {
        $num = intval($index) + 1;
        echo "🔸 PRODUCTO $num:\n";
        echo "   • ID: " . ($product['id'] ?? 'N/A') . "\n";
        echo "   • Código: " . ($product['codigo_producto'] ?? $product['codigo_alfa'] ?? 'N/A') . "\n";
        echo "   • Nombre: " . ($product['nombre'] ?? 'N/A') . "\n";
        echo "   • Categoría: " . ($product['categoria'] ?? 'N/A') . "\n";
        echo "   • Marca: " . ($product['marca'] ?? 'N/A') . "\n";
        echo "   • Precio ARS: $" . ($product['pvp_ars'] ?? $product['precio'] ?? '0') . "\n";
        echo "   • Precio USD: $" . ($product['pvp_usd'] ?? '0') . "\n";
        echo "   • Stock: " . ($product['stock_total'] ?? 'N/A') . "\n";
        echo "   • Moneda: " . ($product['moneda'] == 1 ? 'ARS' : ($product['moneda'] == 2 ? 'USD' : 'N/A')) . "\n";
        echo "   • Gaming: " . ($product['gamer'] ? 'Sí' : 'No') . "\n";
        
        // Mostrar imágenes si existen
        if (!empty($product['imagenes']) && is_array($product['imagenes'])) {
            echo "   • Imágenes: " . count($product['imagenes']) . " disponibles\n";
        }
        
        echo "\n";
    }
    
    echo "=====================================\n";
    echo "✅ PRUEBA COMPLETADA EXITOSAMENTE\n\n";
    
    // Mostrar estructura de un producto completo para referencia
    if (!empty($data[0])) {
        echo "🔍 ESTRUCTURA COMPLETA DEL PRIMER PRODUCTO:\n";
        echo "==========================================\n";
        echo json_encode($data[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
} else {
    echo "⚠️  No se encontraron productos o respuesta vacía\n";
    echo "📄 Respuesta completa:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n🎉 PRUEBA DE API FINALIZADA\n";
?>
