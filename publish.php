<?php
// Habilitar CORS para solicitudes entre dominios
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Obtener datos del cuerpo de la solicitud
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!isset($data['title']) || !isset($data['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos.']);
    exit();
}

$title = $data['title'];
$content = $data['content'];

// Configurar la solicitud a la API de WordPress
$wp_api_url = 'http://www.wordpress.lan/wp-json/wp/v2/posts/';
$wp_auth_token = 'YWxiZXJ0bzpxWEJLIG5ncUsgdVdzMiB1N0JlIFVtZjkgSlFyRQo='; // Token codificado en base64

$post_data = [
    'status' => 'publish',
    'title' => $title,
    'content' => $content
];

$ch = curl_init($wp_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . $wp_auth_token
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_status === 201) {
    echo json_encode(['success' => true]);
} else {
    $error = curl_error($ch);
    echo json_encode(['success' => false, 'error' => $error ? $error : $response]);
}

curl_close($ch);
?>
