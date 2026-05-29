<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? null;
$admin_id = $data["admin_id"] ?? null;
$accion = strtoupper(trim($data["accion"] ?? ""));
$observacion = trim($data["observacion_admin"] ?? "");

if (!$id || !$admin_id || !$accion) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

if ($accion !== "APROBAR" && $accion !== "RECHAZAR") {
    echo json_encode([
        "success" => false,
        "message" => "Acción inválida"
    ]);
    exit;
}

$nuevoEstado = $accion === "APROBAR"
    ? "APROBADA"
    : "RECHAZADA";

/*
    Validar que la justificación exista,
    esté pendiente y pertenezca al admin seleccionado.
*/

$sqlBuscar = "SELECT
                    id,
                    empleado_id,
                    admin_id,
                    fecha_falta,
                    estado
              FROM justificaciones
              WHERE id = ?
              AND admin_id = ?
              LIMIT 1";

$stmtBuscar = $db->prepare($sqlBuscar);

if (!$stmtBuscar) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar búsqueda",
        "error" => $db->error
    ]);
    exit;
}

$stmtBuscar->bind_param("ii", $id, $admin_id);
$stmtBuscar->execute();

$justificacion = $stmtBuscar->get_result()->fetch_assoc();

if (!$justificacion) {
    echo json_encode([
        "success" => false,
        "message" => "Justificación no encontrada para este administrador"
    ]);
    exit;
}

if ($justificacion["estado"] !== "PENDIENTE") {
    echo json_encode([
        "success" => false,
        "message" => "Esta justificación ya fue revisada"
    ]);
    exit;
}

/*
    Actualizar estado.
*/

$sqlActualizar = "UPDATE justificaciones
                  SET estado = ?,
                      observacion_admin = ?,
                      fecha_revision = NOW()
                  WHERE id = ?
                  AND admin_id = ?";

$stmtActualizar = $db->prepare($sqlActualizar);

if (!$stmtActualizar) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar actualización",
        "error" => $db->error
    ]);
    exit;
}

$stmtActualizar->bind_param(
    "ssii",
    $nuevoEstado,
    $observacion,
    $id,
    $admin_id
);

if (!$stmtActualizar->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar justificación",
        "error" => $stmtActualizar->error
    ]);
    exit;
}

/*
    IMPORTANTE:
    Por ahora solo cambia el estado de la justificación.
    En el siguiente paso modificaremos mi_nomina.php y semana_actual.php
    para que las APROBADAS ya no cuenten como falta.
*/

echo json_encode([
    "success" => true,
    "message" => $nuevoEstado === "APROBADA"
        ? "Justificación aprobada correctamente"
        : "Justificación rechazada correctamente",
    "estado" => $nuevoEstado
]);