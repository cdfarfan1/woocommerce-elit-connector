
<?php
// Cargar WordPress para acceder a sus funciones
require_once ''.__DIR__ . '/../../../wp-load.php';

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener los datos del POST
$user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';

if (empty($user_id) || empty($token)) {
    wp_send_json_error(['message' => 'Error: El User ID y el Token son obligatorios.']);
    exit;
}

// URL de la API de ELIT para la autenticación/prueba
$api_url = 'https://www.elit.com.ar/v1/api/productos/all/key/' . $token . '/user_id/' . $user_id;

// Realizar la petición a la API de ELIT
$response = wp_remote_get($api_url);

// Comprobar si hubo un error en la petición
if (is_wp_error($response)) {
    wp_send_json_error(['message' => 'Error de WP_Error: ' . $response->get_error_message()]);
    exit;
}

// Obtener el cuerpo de la respuesta
$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);

// Comprobar si la decodificación del JSON fue exitosa
if (json_last_error() !== JSON_ERROR_NONE) {
    wp_send_json_error(['message' => 'Respuesta de la API no es un JSON válido.']);
    exit;
}

// La API de ELIT devuelve 'OK' en el campo 'status' si la autenticación es correcta.
if (isset($data['status']) && $data['status'] === 'OK') {
    wp_send_json_success(['message' => '¡Conexión exitosa! Las credenciales son válidas.']);
} else {
    // Si el 'status' no es 'OK' o no existe, asumimos que falló la autenticación.
    $error_message = isset($data['message']) ? $data['message'] : 'Credenciales inválidas o error desconocido en la API.';
    wp_send_json_error(['message' => $error_message]);
}
?>
