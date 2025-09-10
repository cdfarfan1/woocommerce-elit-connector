<?php
/**
 * Debug script para ver la respuesta exacta de ELIT API
 */

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://clientes.elit.com.ar/v1/api/productos?limit=3',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(array(
        'user_id' => 24560,
        'token' => 'z9qrpjjgnwq'
    )),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

echo "🔍 ANALIZANDO RESPUESTA DE ELIT API\n";
echo "===================================\n\n";

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "📊 HTTP Code: $http_code\n\n";

if ($http_code === 200) {
    echo "📄 RESPUESTA RAW:\n";
    echo "=================\n";
    echo $response . "\n\n";
    
    $data = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "📋 RESPUESTA PARSEADA:\n";
        echo "======================\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        
        echo "📊 ANÁLISIS:\n";
        echo "============\n";
        echo "• Tipo de datos: " . gettype($data) . "\n";
        
        if (is_array($data)) {
            echo "• Cantidad de elementos: " . count($data) . "\n";
            echo "• Claves del array: " . implode(', ', array_keys($data)) . "\n";
            
            if (!empty($data)) {
                $first = reset($data);
                echo "• Tipo del primer elemento: " . gettype($first) . "\n";
                
                if (is_array($first)) {
                    echo "• Claves del primer elemento: " . implode(', ', array_keys($first)) . "\n";
                }
            }
        }
    } else {
        echo "❌ Error JSON: " . json_last_error_msg() . "\n";
    }
} else {
    echo "❌ Error HTTP: $http_code\n";
    echo "📄 Respuesta: $response\n";
}

echo "\n🎯 FIN DEL ANÁLISIS\n";
?>
