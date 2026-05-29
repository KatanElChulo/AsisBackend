<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . "/../../config/database.php";

$db = $conn ?? $conexion ?? $mysqli ?? null;

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró conexión a la base de datos"
    ]);
    exit;
}

/*
    Se puede recibir admin_id por:
    - GET: listar_admin.php?admin_id=1
    - POST JSON: { "admin_id": 1 }
*/

$admin_id = $_GET["admin_id"] ?? null;

if (!$admin_id && $_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $admin_id = $data["admin_id"] ?? null;
}

if (!$admin_id) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió el administrador"
    ]);
    exit;
}

$sql = "SELECT
            j.id,
            j.empleado_id,
            j.admin_id,
            j.fecha_falta,
            j.motivo,
            j.archivo,
            j.estado,
            j.observacion_admin,
            j.fecha_solicitud,
            j.fecha_revision,

            e.nombre,
            e.apellido_paterno,
            e.apellido_materno,
            e.correo
        FROM justificaciones j
        INNER JOIN empleados e
        ON j.empleado_id = e.id
        WHERE j.admin_id = ?
        ORDER BY 
            CASE 
                WHEN j.estado = 'PENDIENTE' THEN 1
                WHEN j.estado = 'APROBADA' THEN 2
                WHEN j.estado = 'RECHAZADA' THEN 3
                ELSE 4
            END,
            j.fecha_solicitud DESC";

$stmt = $db->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $db->error
    ]);
    exit;
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();

$resultado = $stmt->get_result();

$justificaciones = [];

while ($fila = $resultado->fetch_assoc()) {
    $fila["empleado"] = trim(
        $fila["nombre"] . " " .
        $fila["apellido_paterno"] . " " .
        $fila["apellido_materno"]
    );

    /*
        La ruta real guardada es:
        uploads/justificaciones/archivo.pdf

        Desde el frontend se puede abrir con:
        ../../AsisBackend/ + archivo
    */
    $fila["archivo_url"] = "../../AsisBackend/" . $fila["archivo"];

    $justificaciones[] = $fila;
}

echo json_encode([
    "success" => true,
    "data" => $justificaciones
]);