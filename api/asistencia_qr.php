<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . "/../config/database.php";

$db = $conn ?? $conexion ?? $mysqli ?? null;

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró conexión a la base de datos"
    ]);
    exit;
}

/*
    CONFIGURACIÓN DE UBICACIÓN DEL NEGOCIO
*/

$latitudNegocio = 19.597306585859915;
$longitudNegocio = -99.05703136532902;
$radioPermitidoMetros = 100;

/*
    CONFIGURACIÓN DE HORARIOS

    Ejemplo:
    Horario entrada: 07:00
    Anticipación permitida: 20 minutos
    Tolerancia retardo: 20 minutos

    Antes de 06:40 = NO PERMITIDO
    06:40 a 07:20 = ASISTENCIA
    07:21 en adelante = RETARDO

    Salida:
    Si horario_salida es 16:00
    Antes de 16:00 = NO PERMITIDO
    16:00 en adelante = PERMITIDO
*/

$toleranciaMinutos = 20;
$anticipacionEntradaMinutos = 20;

/*
    FUNCIÓN PARA CALCULAR DISTANCIA EN METROS
*/

function calcularDistanciaMetros($lat1, $lon1, $lat2, $lon2)
{
    $radioTierra = 6371000;

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);

    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $radioTierra * $c;
}

/*
    FUNCIÓN PARA DETERMINAR SI ES ASISTENCIA O RETARDO
*/

function calcularEstatusEntrada($fecha, $horaReal, $horarioEntrada, $toleranciaMinutos)
{
    if (!$horarioEntrada) {
        return [
            "estatus" => "ASISTENCIA",
            "minutos_retardo" => 0
        ];
    }

    $horaPermitida = new DateTime($fecha . " " . $horarioEntrada);
    $horaPermitida->modify("+" . intval($toleranciaMinutos) . " minutes");

    $horaRegistro = new DateTime($horaReal);

    if ($horaRegistro > $horaPermitida) {
        $horaOficial = new DateTime($fecha . " " . $horarioEntrada);
        $diferencia = $horaOficial->diff($horaRegistro);

        $minutosRetardo =
            ($diferencia->h * 60) +
            $diferencia->i;

        return [
            "estatus" => "RETARDO",
            "minutos_retardo" => $minutosRetardo
        ];
    }

    return [
        "estatus" => "ASISTENCIA",
        "minutos_retardo" => 0
    ];
}

$data = json_decode(file_get_contents("php://input"), true);

$empleado_id = $data["empleado_id"] ?? null;
$tipo = $data["tipo"] ?? null;
$token = $data["token"] ?? null;
$latitud = $data["latitud"] ?? null;
$longitud = $data["longitud"] ?? null;
$precision = $data["precision"] ?? null;

if (!$empleado_id || !$tipo || !$token) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos",
        "debug" => [
            "empleado_id" => $empleado_id,
            "tipo" => $tipo,
            "token" => $token
        ]
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

/*
    VALIDAR EMPLEADO Y OBTENER HORARIOS
*/

$sqlEmpleado = "SELECT 
                    id,
                    nombre,
                    horario_entrada,
                    horario_salida,
                    activo
                FROM empleados
                WHERE id = ?
                AND activo = 1
                LIMIT 1";

$stmtEmpleado = $db->prepare($sqlEmpleado);

if (!$stmtEmpleado) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta de empleado",
        "error" => $db->error
    ]);
    exit;
}

$stmtEmpleado->bind_param("i", $empleado_id);
$stmtEmpleado->execute();

$empleado = $stmtEmpleado->get_result()->fetch_assoc();

if (!$empleado) {
    echo json_encode([
        "success" => false,
        "message" => "Empleado no encontrado o inactivo"
    ]);
    exit;
}

$horarioEntrada = $empleado["horario_entrada"];
$horarioSalida = $empleado["horario_salida"];

/*
    VALIDAR UBICACIÓN
*/

if ($latitud === null || $longitud === null || $latitud === "" || $longitud === "") {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió tu ubicación. Activa el GPS y permite la ubicación."
    ]);
    exit;
}

$latitud = floatval($latitud);
$longitud = floatval($longitud);
$precision = $precision !== null ? floatval($precision) : null;

/*
    VALIDAR PRECISIÓN GPS
*/

if ($precision !== null && $precision > 150) {
    echo json_encode([
        "success" => false,
        "message" => "La precisión de tu ubicación es muy baja. Activa GPS de alta precisión e intenta de nuevo.",
        "precision_metros" => round($precision, 2)
    ]);
    exit;
}

/*
    VALIDAR DISTANCIA AL NEGOCIO
*/

$distanciaMetros = calcularDistanciaMetros(
    $latitudNegocio,
    $longitudNegocio,
    $latitud,
    $longitud
);

if ($distanciaMetros > $radioPermitidoMetros) {
    echo json_encode([
        "success" => false,
        "message" => "Estás fuera del área permitida para registrar asistencia. Distancia aproximada: " . round($distanciaMetros) . " metros.",
        "distancia_metros" => round($distanciaMetros, 2),
        "radio_permitido_metros" => $radioPermitidoMetros
    ]);
    exit;
}

/*
    VALIDAR QR
*/

$sqlColumnas = "SHOW COLUMNS FROM qr_tokens";
$resultColumnas = $db->query($sqlColumnas);

if (!$resultColumnas) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo leer la tabla qr_tokens",
        "error" => $db->error
    ]);
    exit;
}

$columnas = [];

while ($fila = $resultColumnas->fetch_assoc()) {
    $columnas[] = $fila["Field"];
}

if (!in_array("token", $columnas)) {
    echo json_encode([
        "success" => false,
        "message" => "La tabla qr_tokens no tiene columna token",
        "columnas_detectadas" => $columnas
    ]);
    exit;
}

if (in_array("expira_en", $columnas) && in_array("activo", $columnas)) {

    $sqlQR = "SELECT id FROM qr_tokens
              WHERE token = ?
              AND activo = 1
              AND expira_en >= NOW()
              LIMIT 1";

} elseif (in_array("expira_en", $columnas)) {

    $sqlQR = "SELECT id FROM qr_tokens
              WHERE token = ?
              AND expira_en >= NOW()
              LIMIT 1";

} elseif (in_array("activo", $columnas)) {

    $sqlQR = "SELECT id FROM qr_tokens
              WHERE token = ?
              AND activo = 1
              LIMIT 1";

} else {

    $sqlQR = "SELECT id FROM qr_tokens
              WHERE token = ?
              LIMIT 1";
}

$stmtQR = $db->prepare($sqlQR);

if (!$stmtQR) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar validación QR",
        "error" => $db->error,
        "sql" => $sqlQR
    ]);
    exit;
}

$stmtQR->bind_param("s", $token);
$stmtQR->execute();

$resultQR = $stmtQR->get_result();

if ($resultQR->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "QR inválido o expirado",
        "token_recibido" => $token,
        "columnas_qr_tokens" => $columnas
    ]);
    exit;
}

$fecha = date("Y-m-d");
$ahora = date("Y-m-d H:i:s");

/*
    BUSCAR ASISTENCIA DEL DÍA
*/

$sqlBuscar = "SELECT id, hora_entrada, hora_salida, estatus
              FROM asistencias
              WHERE empleado_id = ?
              AND fecha = ?
              LIMIT 1";

$stmtBuscar = $db->prepare($sqlBuscar);

if (!$stmtBuscar) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar búsqueda de asistencia",
        "error" => $db->error
    ]);
    exit;
}

$stmtBuscar->bind_param("is", $empleado_id, $fecha);
$stmtBuscar->execute();

$resultBuscar = $stmtBuscar->get_result();

/*
    REGISTRAR ENTRADA
*/

if ($tipo === "entrada") {

    if ($resultBuscar->num_rows > 0) {

        $asistencia = $resultBuscar->fetch_assoc();

        if ($asistencia["hora_entrada"] !== null && $asistencia["hora_entrada"] !== "") {
            echo json_encode([
                "success" => false,
                "message" => "Ya registraste tu entrada hoy"
            ]);
            exit;
        }
    }

    /*
        VALIDAR QUE NO REGISTRE ENTRADA DEMASIADO TEMPRANO
    */

    if ($horarioEntrada !== null && $horarioEntrada !== "") {

        $horaEntradaMinima =
            new DateTime($fecha . " " . $horarioEntrada);

        $horaEntradaMinima->modify(
            "-" . intval($anticipacionEntradaMinutos) . " minutes"
        );

        $horaRegistroEntrada =
            new DateTime($ahora);

        if ($horaRegistroEntrada < $horaEntradaMinima) {

            echo json_encode([
                "success" => false,
                "message" => "No puedes registrar entrada tan temprano. Puedes registrar desde las " . $horaEntradaMinima->format("H:i") . ".",
                "horario_entrada" => $horarioEntrada,
                "hora_minima_registro" => $horaEntradaMinima->format("H:i:s"),
                "hora_actual" => date("H:i:s")
            ]);

            exit;
        }
    }

    /*
        CALCULAR ASISTENCIA O RETARDO
    */

    $resultadoEstatus = calcularEstatusEntrada(
        $fecha,
        $ahora,
        $horarioEntrada,
        $toleranciaMinutos
    );

    $estatusEntrada = $resultadoEstatus["estatus"];
    $minutosRetardo = $resultadoEstatus["minutos_retardo"];

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
            VALUES (?, ?, ?, ?, ?, ?, 'QR', ?)
            ON DUPLICATE KEY UPDATE
                hora_entrada = VALUES(hora_entrada),
                latitud_entrada = VALUES(latitud_entrada),
                longitud_entrada = VALUES(longitud_entrada),
                token_entrada = VALUES(token_entrada),
                metodo_registro = 'QR',
                estatus = VALUES(estatus)";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Error al preparar registro de entrada",
            "error" => $db->error
        ]);
        exit;
    }

    $stmt->bind_param(
        "issddss",
        $empleado_id,
        $fecha,
        $ahora,
        $latitud,
        $longitud,
        $token,
        $estatusEntrada
    );

/*
    REGISTRAR SALIDA
*/

} else {

    if ($resultBuscar->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Primero debes registrar entrada"
        ]);
        exit;
    }

    $asistencia = $resultBuscar->fetch_assoc();

    if ($asistencia["hora_salida"] !== null && $asistencia["hora_salida"] !== "") {
        echo json_encode([
            "success" => false,
            "message" => "Ya registraste tu salida hoy"
        ]);
        exit;
    }

    /*
        VALIDAR QUE NO REGISTRE SALIDA ANTES DE SU HORARIO
    */

    if ($horarioSalida !== null && $horarioSalida !== "") {

        $horaSalidaPermitida =
            new DateTime($fecha . " " . $horarioSalida);

        $horaRegistroSalida =
            new DateTime($ahora);

        if ($horaRegistroSalida < $horaSalidaPermitida) {

            echo json_encode([
                "success" => false,
                "message" => "No puedes registrar salida antes de tu horario. Tu salida es a las " . substr($horarioSalida, 0, 5) . ".",
                "horario_salida" => $horarioSalida,
                "hora_actual" => date("H:i:s")
            ]);

            exit;
        }
    }

    /*
        REGISTRAR SALIDA
        No cambiamos estatus para no borrar RETARDO.
    */

    $sql = "UPDATE asistencias
            SET hora_salida = ?,
                latitud_salida = ?,
                longitud_salida = ?,
                token_salida = ?,
                metodo_registro = 'QR'
            WHERE empleado_id = ?
            AND fecha = ?";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Error al preparar registro de salida",
            "error" => $db->error
        ]);
        exit;
    }

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

/*
    EJECUTAR REGISTRO
*/

if ($stmt->execute()) {

    if ($tipo === "entrada") {

        if ($estatusEntrada === "RETARDO") {
            $mensaje = "Entrada registrada con RETARDO. Retardo aproximado: " . $minutosRetardo . " minutos.";
        } else {
            $mensaje = "Entrada registrada correctamente";
        }

    } else {
        $mensaje = "Salida registrada correctamente";
    }

    echo json_encode([
        "success" => true,
        "message" => $mensaje,
        "estatus" => $tipo === "entrada" ? $estatusEntrada : ($asistencia["estatus"] ?? null),
        "minutos_retardo" => $tipo === "entrada" ? $minutosRetardo : 0,
        "horario_entrada" => $horarioEntrada,
        "horario_salida" => $horarioSalida,
        "distancia_metros" => round($distanciaMetros, 2)
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Error al registrar asistencia",
        "error" => $stmt->error
    ]);
}