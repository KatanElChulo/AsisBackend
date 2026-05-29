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

$data = json_decode(file_get_contents("php://input"), true);

$empleado_id = $data["empleado_id"] ?? null;

if (!$empleado_id) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió el empleado"
    ]);
    exit;
}

/* OBTENER EMPLEADO */

$sqlEmpleado = "SELECT 
                    id,
                    nombre,
                    apellido_paterno,
                    apellido_materno,
                    sueldo_diario
                FROM empleados
                WHERE id = ?
                AND activo = 1
                LIMIT 1";

$stmtEmpleado = $db->prepare($sqlEmpleado);

if (!$stmtEmpleado) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar empleado",
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

/* SEMANA ACTUAL LUNES A VIERNES */

$hoy = new DateTime();

$lunes = clone $hoy;
$lunes->modify("monday this week");

$viernes = clone $lunes;
$viernes->modify("+4 days");

$fecha_inicio = $lunes->format("Y-m-d");
$fecha_fin = $viernes->format("Y-m-d");

/* PARA FALTAS: NO CONTAMOS EL DÍA ACTUAL */

$ayer = clone $hoy;
$ayer->modify("-1 day");

$limite_faltas = $ayer;

if ($limite_faltas > $viernes) {
    $limite_faltas = $viernes;
}

$fecha_limite_faltas = $limite_faltas->format("Y-m-d");

/* FUNCIÓN DÍAS HÁBILES */

function contarDiasHabiles($inicio, $fin)
{
    if ($fin < $inicio) {
        return 0;
    }

    $inicioDT = new DateTime($inicio);
    $finDT = new DateTime($fin);
    $finDT->modify("+1 day");

    $periodo = new DatePeriod($inicioDT, new DateInterval("P1D"), $finDT);

    $dias = 0;

    foreach ($periodo as $fecha) {
        $diaSemana = $fecha->format("N");

        if ($diaSemana >= 1 && $diaSemana <= 5) {
            $dias++;
        }
    }

    return $dias;
}

$dias_habiles_semana =
    contarDiasHabiles($fecha_inicio, $fecha_fin);

$dias_habiles_transcurridos =
    contarDiasHabiles($fecha_inicio, $fecha_limite_faltas);

/* DÍAS TRABAJADOS COMPLETOS */

$sqlTrabajados = "SELECT COUNT(DISTINCT fecha) AS dias_trabajados
                  FROM asistencias
                  WHERE empleado_id = ?
                  AND fecha BETWEEN ? AND ?
                  AND hora_entrada IS NOT NULL
                  AND hora_salida IS NOT NULL";

$stmtTrabajados = $db->prepare($sqlTrabajados);

if (!$stmtTrabajados) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar días trabajados",
        "error" => $db->error
    ]);
    exit;
}

$stmtTrabajados->bind_param(
    "iss",
    $empleado_id,
    $fecha_inicio,
    $fecha_fin
);

$stmtTrabajados->execute();

$resultTrabajados =
    $stmtTrabajados->get_result()->fetch_assoc();

$dias_trabajados =
    intval($resultTrabajados["dias_trabajados"] ?? 0);

/* DÍAS TRABAJADOS TRANSCURRIDOS PARA FALTAS */

$sqlTrabajadosTranscurridos = "SELECT COUNT(DISTINCT fecha) AS dias_trabajados_transcurridos
                               FROM asistencias
                               WHERE empleado_id = ?
                               AND fecha BETWEEN ? AND ?
                               AND hora_entrada IS NOT NULL
                               AND hora_salida IS NOT NULL";

$stmtTranscurridos = $db->prepare($sqlTrabajadosTranscurridos);

if (!$stmtTranscurridos) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar faltas",
        "error" => $db->error
    ]);
    exit;
}

$stmtTranscurridos->bind_param(
    "iss",
    $empleado_id,
    $fecha_inicio,
    $fecha_limite_faltas
);

$stmtTranscurridos->execute();

$resultTranscurridos =
    $stmtTranscurridos->get_result()->fetch_assoc();

$dias_trabajados_transcurridos =
    intval($resultTranscurridos["dias_trabajados_transcurridos"] ?? 0);

/* JUSTIFICACIONES APROBADAS DE TODA LA SEMANA */

$sqlJustificadas = "SELECT COUNT(*) AS justificadas
                    FROM justificaciones j
                    WHERE j.empleado_id = ?
                    AND j.fecha_falta BETWEEN ? AND ?
                    AND j.estado = 'APROBADA'
                    AND NOT EXISTS (
                        SELECT 1
                        FROM asistencias a
                        WHERE a.empleado_id = j.empleado_id
                        AND a.fecha = j.fecha_falta
                        AND a.hora_entrada IS NOT NULL
                        AND a.hora_salida IS NOT NULL
                    )";

$stmtJustificadas = $db->prepare($sqlJustificadas);

if (!$stmtJustificadas) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar justificaciones",
        "error" => $db->error
    ]);
    exit;
}

$stmtJustificadas->bind_param(
    "iss",
    $empleado_id,
    $fecha_inicio,
    $fecha_fin
);

$stmtJustificadas->execute();

$resultJustificadas =
    $stmtJustificadas->get_result()->fetch_assoc();

$justificadas =
    intval($resultJustificadas["justificadas"] ?? 0);

/* JUSTIFICACIONES APROBADAS TRANSCURRIDAS PARA FALTAS */

$sqlJustificadasTranscurridas = "SELECT COUNT(*) AS justificadas_transcurridas
                                 FROM justificaciones j
                                 WHERE j.empleado_id = ?
                                 AND j.fecha_falta BETWEEN ? AND ?
                                 AND j.estado = 'APROBADA'
                                 AND NOT EXISTS (
                                    SELECT 1
                                    FROM asistencias a
                                    WHERE a.empleado_id = j.empleado_id
                                    AND a.fecha = j.fecha_falta
                                    AND a.hora_entrada IS NOT NULL
                                    AND a.hora_salida IS NOT NULL
                                 )";

$stmtJustificadasTranscurridas =
    $db->prepare($sqlJustificadasTranscurridas);

if (!$stmtJustificadasTranscurridas) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar justificaciones transcurridas",
        "error" => $db->error
    ]);
    exit;
}

$stmtJustificadasTranscurridas->bind_param(
    "iss",
    $empleado_id,
    $fecha_inicio,
    $fecha_limite_faltas
);

$stmtJustificadasTranscurridas->execute();

$resultJustificadasTranscurridas =
    $stmtJustificadasTranscurridas->get_result()->fetch_assoc();

$justificadas_transcurridas =
    intval($resultJustificadasTranscurridas["justificadas_transcurridas"] ?? 0);

/* FALTAS */

$faltas =
    $dias_habiles_transcurridos -
    $dias_trabajados_transcurridos -
    $justificadas_transcurridas;

if ($faltas < 0) {
    $faltas = 0;
}

/* RETARDOS */

$sqlRetardos = "SELECT COUNT(*) AS retardos
                FROM asistencias
                WHERE empleado_id = ?
                AND fecha BETWEEN ? AND ?
                AND estatus = 'RETARDO'";

$stmtRetardos = $db->prepare($sqlRetardos);

if (!$stmtRetardos) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar retardos",
        "error" => $db->error
    ]);
    exit;
}

$stmtRetardos->bind_param(
    "iss",
    $empleado_id,
    $fecha_inicio,
    $fecha_fin
);

$stmtRetardos->execute();

$resultRetardos =
    $stmtRetardos->get_result()->fetch_assoc();

$retardos =
    intval($resultRetardos["retardos"] ?? 0);

/* PAGO */

$sueldo_diario =
    floatval($empleado["sueldo_diario"]);

$dias_pagados =
    $dias_trabajados + $justificadas;

$total_pago =
    $dias_pagados * $sueldo_diario;

$nombre_completo = trim(
    $empleado["nombre"] . " " .
    $empleado["apellido_paterno"] . " " .
    $empleado["apellido_materno"]
);

/* DETALLE DE ASISTENCIAS */

$sqlDetalle = "SELECT 
                    fecha,
                    hora_entrada,
                    hora_salida,
                    estatus
               FROM asistencias
               WHERE empleado_id = ?
               AND fecha BETWEEN ? AND ?
               ORDER BY fecha ASC";

$stmtDetalle = $db->prepare($sqlDetalle);

if (!$stmtDetalle) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar detalle",
        "error" => $db->error
    ]);
    exit;
}

$stmtDetalle->bind_param(
    "iss",
    $empleado_id,
    $fecha_inicio,
    $fecha_fin
);

$stmtDetalle->execute();

$resultDetalle =
    $stmtDetalle->get_result();

$asistencias = [];

while ($fila = $resultDetalle->fetch_assoc()) {
    $asistencias[$fila["fecha"]] = $fila;
}

/* DETALLE DE JUSTIFICACIONES APROBADAS */

$sqlDetalleJustificaciones = "SELECT
                                fecha_falta,
                                motivo,
                                archivo,
                                estado
                              FROM justificaciones
                              WHERE empleado_id = ?
                              AND fecha_falta BETWEEN ? AND ?
                              AND estado = 'APROBADA'";

$stmtDetalleJustificaciones =
    $db->prepare($sqlDetalleJustificaciones);

if (!$stmtDetalleJustificaciones) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar detalle de justificaciones",
        "error" => $db->error
    ]);
    exit;
}

$stmtDetalleJustificaciones->bind_param(
    "iss",
    $empleado_id,
    $fecha_inicio,
    $fecha_fin
);

$stmtDetalleJustificaciones->execute();

$resultDetalleJustificaciones =
    $stmtDetalleJustificaciones->get_result();

$justificaciones = [];

while ($fila = $resultDetalleJustificaciones->fetch_assoc()) {
    $justificaciones[$fila["fecha_falta"]] = $fila;
}

/* ARMAR DÍAS LUNES A VIERNES */

$detalle_semana = [];

$inicioDT = new DateTime($fecha_inicio);
$finDT = new DateTime($fecha_fin);
$finDT->modify("+1 day");

$periodo = new DatePeriod($inicioDT, new DateInterval("P1D"), $finDT);

foreach ($periodo as $fechaObj) {
    $fecha = $fechaObj->format("Y-m-d");
    $diaSemana = $fechaObj->format("N");

    if ($diaSemana > 5) {
        continue;
    }

    $registro =
        $asistencias[$fecha] ?? null;

    $justificacion =
        $justificaciones[$fecha] ?? null;

    if ($registro && $registro["estatus"] === "RETARDO") {
        $estado = "Retardo";
    } elseif ($registro && $registro["hora_entrada"] && $registro["hora_salida"]) {
        $estado = "Asistencia";
    } elseif ($registro && $registro["hora_entrada"] && !$registro["hora_salida"]) {
        $estado = "Entrada sin salida";
    } elseif ($justificacion) {
        $estado = "Justificada";
    } else {
        if ($fecha < date("Y-m-d")) {
            $estado = "Falta";
        } elseif ($fecha === date("Y-m-d")) {
            $estado = "En curso";
        } else {
            $estado = "Pendiente";
        }
    }

    $detalle_semana[] = [
        "fecha" => $fecha,
        "hora_entrada" => $registro["hora_entrada"] ?? null,
        "hora_salida" => $registro["hora_salida"] ?? null,
        "estatus" => $registro["estatus"] ?? null,
        "estado" => $estado,
        "justificacion_motivo" => $justificacion["motivo"] ?? null,
        "justificacion_archivo" => $justificacion["archivo"] ?? null
    ];
}

echo json_encode([
    "success" => true,
    "message" => "Mi nómina semanal cargada correctamente",
    "data" => [
        "empleado_id" => $empleado_id,
        "empleado" => $nombre_completo,
        "fecha_inicio" => $fecha_inicio,
        "fecha_fin" => $fecha_fin,
        "dias_habiles_semana" => $dias_habiles_semana,
        "dias_habiles_transcurridos" => $dias_habiles_transcurridos,
        "dias_trabajados" => $dias_trabajados,
        "justificadas" => $justificadas,
        "dias_pagados" => $dias_pagados,
        "faltas" => $faltas,
        "retardos" => $retardos,
        "sueldo_diario" => $sueldo_diario,
        "total_pago" => $total_pago,
        "detalle_semana" => $detalle_semana
    ]
]);