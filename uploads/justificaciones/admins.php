<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$db = $conn ?? $conexion ?? $mysqli ?? null;

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró conexión a la base de datos"
    ]);
    exit;
}

$sql = "SELECT 
            e.id,
            e.nombre,
            e.apellido_paterno,
            e.apellido_materno,
            e.correo
        FROM empleados e
        INNER JOIN roles r
        ON e.rol_id = r.id
        WHERE e.activo = 1
        AND UPPER(r.nombre) = 'ADMIN'
        ORDER BY e.nombre ASC";

$resultado = $db->query($sql);

if (!$resultado) {
    echo json_encode([
        "success" => false,
        "message" => "Error al consultar administradores",
        "error" => $db->error
    ]);
    exit;
}

$admins = [];

while ($fila = $resultado->fetch_assoc()) {
    $fila["nombre_completo"] = trim(
        $fila["nombre"] . " " .
        $fila["apellido_paterno"] . " " .
        $fila["apellido_materno"]
    );

    $admins[] = $fila;
}

echo json_encode([
    "success" => true,
    "data" => $admins
]);