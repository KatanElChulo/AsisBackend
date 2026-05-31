<?php

require_once __DIR__ . "/../../config/database.php";

$db = $conn ?? $conexion ?? $mysqli ?? null;

if (!$db) {
    http_response_code(500);
    echo "No se encontró conexión a la base de datos";
    exit;
}

$id = $_GET["id"] ?? null;

if (!$id) {
    http_response_code(400);
    echo "No se recibió el ID del archivo";
    exit;
}

$sql = "SELECT archivo
        FROM justificaciones
        WHERE id = ?
        LIMIT 1";

$stmt = $db->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo "Error al preparar consulta";
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result()->fetch_assoc();

if (!$resultado) {
    http_response_code(404);
    echo "Justificación no encontrada";
    exit;
}

$archivoBD = $resultado["archivo"];

if (!$archivoBD) {
    http_response_code(404);
    echo "La justificación no tiene archivo";
    exit;
}

$archivoBD = ltrim($archivoBD, "/");

/*
    En la BD se guarda:
    uploads/justificaciones/nombre_archivo.png

    Este archivo está físicamente en:
    AsisBackend/uploads/justificaciones/nombre_archivo.png
*/

$rutaFisica = __DIR__ . "/../../" . $archivoBD;

if (!file_exists($rutaFisica)) {
    http_response_code(404);
    echo "Archivo no encontrado en el servidor: " . htmlspecialchars($archivoBD);
    exit;
}

$extension = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));

$tiposPermitidos = [
    "jpg" => "image/jpeg",
    "jpeg" => "image/jpeg",
    "png" => "image/png",
    "pdf" => "application/pdf"
];

if (!isset($tiposPermitidos[$extension])) {
    http_response_code(403);
    echo "Tipo de archivo no permitido";
    exit;
}

header("Content-Type: " . $tiposPermitidos[$extension]);
header("Content-Disposition: inline; filename=\"" . basename($rutaFisica) . "\"");
header("Content-Length: " . filesize($rutaFisica));

readfile($rutaFisica);
exit;