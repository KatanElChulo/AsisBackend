<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$db = $conexion ?? $conn ?? $mysqli ?? null;

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró conexión a la base de datos"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$empleado_id = $data["empleado_id"] ?? null;
$fecha_inicio = $data["fecha_inicio"] ?? null;
$fecha_fin = $data["fecha_fin"] ?? null;

if (!$empleado_id || !$fecha_inicio || !$fecha_fin) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

if ($fecha_inicio > $fecha_fin) {
    echo json_encode([
        "success" => false,
        "message" => "La fecha de inicio no puede ser mayor a la fecha fin"
    ]);
    exit;
}

/* OBTENER SUELDO DEL EMPLEADO */
$sqlEmpleado = "SELECT sueldo_diario FROM empleados WHERE id = ? AND activo = 1 LIMIT 1";

$stmtEmpleado = $db->prepare($sqlEmpleado);
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

$sueldo_diario = $empleado["sueldo_diario"];

/* CONTAR DÍAS HÁBILES SIN SÁBADO NI DOMINGO */
$inicio = new DateTime($fecha_inicio);
$fin = new DateTime($fecha_fin);
$fin->modify("+1 day");

$periodo = new DatePeriod($inicio, new DateInterval("P1D"), $fin);

$dias_habiles = 0;

foreach ($periodo as $fecha) {
    $diaSemana = $fecha->format("N");

    if ($diaSemana >= 1 && $diaSemana <= 5) {
        $dias_habiles++;
    }
}

/* CONTAR DÍAS TRABAJADOS SEGÚN TU TABLA ASISTENCIAS */
$sqlAsistencias = "SELECT COUNT(DISTINCT fecha) AS dias_trabajados
                   FROM asistencias
                   WHERE empleado_id = ?
                   AND fecha BETWEEN ? AND ?
                   AND hora_entrada IS NOT NULL
                   AND hora_salida IS NOT NULL";

$stmtAsistencias = $db->prepare($sqlAsistencias);
$stmtAsistencias->bind_param("iss", $empleado_id, $fecha_inicio, $fecha_fin);
$stmtAsistencias->execute();

$resultadoAsistencias = $stmtAsistencias->get_result()->fetch_assoc();

$dias_trabajados = $resultadoAsistencias["dias_trabajados"] ?? 0;

/* CALCULAR FALTAS */
$faltas = $dias_habiles - $dias_trabajados;

if ($faltas < 0) {
    $faltas = 0;
}

/* CALCULAR TOTAL */
$total_pago = $dias_trabajados * $sueldo_diario;

/* EVITAR DUPLICAR NÓMINA DEL MISMO PERIODO */
$sqlExiste = "SELECT id FROM nominas
              WHERE empleado_id = ?
              AND fecha_inicio = ?
              AND fecha_fin = ?
              LIMIT 1";

$stmtExiste = $db->prepare($sqlExiste);
$stmtExiste->bind_param("iss", $empleado_id, $fecha_inicio, $fecha_fin);
$stmtExiste->execute();

$existe = $stmtExiste->get_result()->fetch_assoc();

if ($existe) {
    echo json_encode([
        "success" => false,
        "message" => "Ya existe una nómina para este empleado en ese periodo"
    ]);
    exit;
}

/* INSERTAR NÓMINA */
$sqlInsertar = "INSERT INTO nominas
                (
                    empleado_id,
                    fecha_inicio,
                    fecha_fin,
                    dias_trabajados,
                    faltas,
                    sueldo_diario,
                    total_pago
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmtInsertar = $db->prepare($sqlInsertar);

$stmtInsertar->bind_param(
    "issiidd",
    $empleado_id,
    $fecha_inicio,
    $fecha_fin,
    $dias_trabajados,
    $faltas,
    $sueldo_diario,
    $total_pago
);

if ($stmtInsertar->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Nómina generada correctamente",
        "data" => [
            "dias_habiles" => $dias_habiles,
            "dias_trabajados" => $dias_trabajados,
            "faltas" => $faltas,
            "sueldo_diario" => $sueldo_diario,
            "total_pago" => $total_pago
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al generar nómina"
    ]);
}