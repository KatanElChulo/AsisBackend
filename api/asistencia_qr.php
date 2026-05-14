<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/conexion.php";

$data = json_decode(file_get_contents("php://input"), true);

$empleado_id = $data["empleado_id"] ?? null;
$tipo = $data["tipo"] ?? null;
$token = $data["token"] ?? null;
$latitud = $data["latitud"] ?? null;
$longitud = $data["longitud"] ?? null;

if (!$empleado_id || !$tipo || !$token) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

if ($tipo !== "entrada" && $tipo !== "salida") {
    echo json_encode([
        "success" => false,
        "message" => "Tipo de registro inválido"
    ]);
    exit;
}

/* VALIDAR QR */
$sqlQR = "SELECT id FROM qr_tokens
          WHERE token = ?
          AND expira_en >= NOW()
          LIMIT 1";

$stmtQR = $conexion->prepare($sqlQR);
$stmtQR->bind_param("s", $token);
$stmtQR->execute();

$resultQR = $stmtQR->get_result();

if ($resultQR->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "QR inválido o expirado"
    ]);
    exit;
}

$qr = $resultQR->fetch_assoc();
$qr_id = $qr["id"];

$fecha = date("Y-m-d");
$ahora = date("Y-m-d H:i:s");

/* BUSCAR ASISTENCIA DEL DÍA */
$sqlBuscar = "SELECT id, hora_entrada, hora_salida
              FROM asistencias
              WHERE empleado_id = ?
              AND fecha = ?
              LIMIT 1";

$stmtBuscar = $conexion->prepare($sqlBuscar);
$stmtBuscar->bind_param("is", $empleado_id, $fecha);
$stmtBuscar->execute();

$resultBuscar = $stmtBuscar->get_result();

if ($tipo === "entrada") {

    if ($resultBuscar->num_rows > 0) {

        $asistencia = $resultBuscar->fetch_assoc();

        if ($asistencia["hora_entrada"] !== null) {
            echo json_encode([
                "success" => false,
                "message" => "Ya registraste tu entrada hoy"
            ]);
            exit;
        }
    }

    $sql = "INSERT INTO asistencias (
                empleado_id,
                fecha,
                hora_entrada,
                latitud_entrada,
                longitud_entrada,
                token_entrada,
                metodo_registro,
                estatus
            )
            VALUES (?, ?, ?, ?, ?, ?, 'QR', 'ASISTENCIA')
            ON DUPLICATE KEY UPDATE
                hora_entrada = VALUES(hora_entrada),
                latitud_entrada = VALUES(latitud_entrada),
                longitud_entrada = VALUES(longitud_entrada),
                token_entrada = VALUES(token_entrada),
                metodo_registro = 'QR',
                estatus = 'ASISTENCIA'";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "issdds",
        $empleado_id,
        $fecha,
        $ahora,
        $latitud,
        $longitud,
        $token
    );

} else {

    if ($resultBuscar->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Primero debes registrar entrada"
        ]);
        exit;
    }

    $asistencia = $resultBuscar->fetch_assoc();

    if ($asistencia["hora_salida"] !== null) {
        echo json_encode([
            "success" => false,
            "message" => "Ya registraste tu salida hoy"
        ]);
        exit;
    }

    $sql = "UPDATE asistencias
            SET hora_salida = ?,
                latitud_salida = ?,
                longitud_salida = ?,
                token_salida = ?,
                metodo_registro = 'QR'
            WHERE empleado_id = ?
            AND fecha = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "sddsis",
        $ahora,
        $latitud,
        $longitud,
        $token,
        $empleado_id,
        $fecha
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => $tipo === "entrada"
            ? "Entrada registrada correctamente"
            : "Salida registrada correctamente"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al registrar asistencia"
    ]);
}