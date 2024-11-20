<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Leer datos enviados desde la solicitud
$input = json_decode(file_get_contents("php://input"), true);
$provinceCode = $input['code'] ?? null;
$provinceName = $input['name'] ?? null;

if (!$provinceCode || !$provinceName) {
    echo json_encode(['success' => false, 'message' => 'El código y el nombre de la provincia son requeridos.']);
    exit;
}

// Consultar la API de el-tiempo.net
$weatherApiUrl = "https://www.el-tiempo.net/api/json/v2/provincias/$provinceCode";
$weatherData = file_get_contents($weatherApiUrl);

if (!$weatherData) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener datos de la API de el-tiempo.net.']);
    exit;
}

$data = json_decode($weatherData, true);

// Comprobar si la respuesta es válida
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error al analizar los datos de la API.']);
    exit;
}

// Extraer los datos de las ciudades
$cityData = $data['ciudades'] ?? [];

if (empty($cityData)) {
    echo json_encode(['success' => false, 'message' => 'No se encontraron ciudades para la provincia seleccionada.']);
    exit;
}

// Configuración para la API de WordPress
$wpApiUrl = 'http://www.wordpress.lan/wp-json/wp/v2/posts/';
$wpAuthHeader = 'Authorization: Basic YWxiZXJ0bzpxWEJLIG5ncUsgdVdzMiB1N0JlIFVtZjkgSlFyRQo=';

foreach ($cityData as $city) {
    $cityName = $city['name'] ?? 'No disponible';
    $provinceName = $city['nameProvince'] ?? 'No disponible';
    $stateDescription = $city['stateSky']['description'] ?? 'No disponible';
    $maxTemp = $city['temperatures']['max'] ?? 'No disponible';
    $minTemp = $city['temperatures']['min'] ?? 'No disponible';

    // Preparar el contenido para el post en WordPress
    $title = "El tiempo en $cityName, $provinceName";
    $content = "
        <h3>Pronóstico del día para $cityName</h3>
        <p><strong>Provincia:</strong> $provinceName</p>
        <p><strong>Ciudad:</strong> $cityName</p>
        <p><strong>Estado del Cielo:</strong> $stateDescription</p>
        <p><strong>Temperatura Máxima:</strong> $maxTemp °C</p>
        <p><strong>Temperatura Mínima:</strong> $minTemp °C</p>
    ";

    // Datos para enviar a la API de WordPress
    $postData = [
        'title' => $title,
        'content' => $content,
        'status' => 'publish'
    ];

    // Enviar datos a la API de WordPress
    $ch = curl_init($wpApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        $wpAuthHeader
    ]);

    // Ejecutar la petición cURL
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Comprobar si la publicación fue exitosa
    if ($httpCode !== 201) {
        echo json_encode(['success' => false, 'message' => 'Error al publicar en WordPress para la ciudad ' . $cityName]);
        exit;
    }
}

// Si todo fue bien
echo json_encode(['success' => true, 'message' => 'Datos meteorológicos publicados correctamente.']);
?>
