<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . "/../../config/database.php";

$db = $conexion ?? $conn ?? $mysqli ?? null;

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró conexión a la base de datos"
    ]);
    exit;
}

/*
    Semana actual:
    Lunes a viernes.
*/

$hoy = new DateTime();

$lunes = clone $hoy;
$lunes->modify("monday this week");

$viernes = clone $lunes;
$viernes->modify("+4 days");

$fecha_inicio = $lunes->format("Y-m-d");
$fecha_fin = $viernes->format("Y-m-d");

/*
    Para faltas en tiempo real:
    NO contamos el día actual como falta todavía.
*/

$ayer = clone $hoy;
$ayer->modify("-1 day");

$limite_faltas = $ayer;

if ($limite_faltas > $viernes) {
    $limite_faltas = $viernes;
}

$fecha_limite_faltas =
    $limite_faltas->format("Y-m-d");

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

/*
    Obtener todos los empleados activos.
*/

$sqlEmpleados = "SELECT 
                    id,
                    nombre,
                    apellido_paterno,
                    apellido_materno,
                    sueldo_diario
                 FROM empleados
                 WHERE activo = 1
                 ORDER BY nombre ASC";

$resultEmpleados = $db->query($sqlEmpleados);

if (!$resultEmpleados) {
    echo json_encode([
        "success" => false,
        "message" => "Error al consultar empleados",
        "error" => $db->error
    ]);
    exit;
}

$nomina_actual = [];

while ($empleado = $resultEmpleados->fetch_assoc()) {
    $empleado_id = $empleado["id"];

    /*
        Días trabajados:
        Cuenta los días donde tenga entrada y salida.
    */

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

    $resultadoTrabajados =
        $stmtTrabajados->get_result()->fetch_assoc();

    $dias_trabajados =
        intval($resultadoTrabajados["dias_trabajados"] ?? 0);

    /*
        Días trabajados transcurridos para faltas.
    */

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

    $resultadoTranscurridos =
        $stmtTranscurridos->get_result()->fetch_assoc();

    $dias_trabajados_transcurridos =
        intval($resultadoTranscurridos["dias_trabajados_transcurridos"] ?? 0);

    /*
        Justificaciones aprobadas de toda la semana.
    */

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

    $resultadoJustificadas =
        $stmtJustificadas->get_result()->fetch_assoc();

    $justificadas =
        intval($resultadoJustificadas["justificadas"] ?? 0);

    /*
        Justificaciones aprobadas transcurridas para faltas.
    */

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

    $resultadoJustificadasTranscurridas =
        $stmtJustificadasTranscurridas->get_result()->fetch_assoc();

    $justificadas_transcurridas =
        intval($resultadoJustificadasTranscurridas["justificadas_transcurridas"] ?? 0);

    /*
        Faltas:
        días transcurridos - trabajados - justificadas aprobadas.
    */

    $faltas =
        $dias_habiles_transcurridos -
        $dias_trabajados_transcurridos -
        $justificadas_transcurridas;

    if ($faltas < 0) {
        $faltas = 0;
    }

    /*
        Retardos:
        Cuenta asistencias con estatus RETARDO.
    */

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

    $resultadoRetardos =
        $stmtRetardos->get_result()->fetch_assoc();

    $retardos =
        intval($resultadoRetardos["retardos"] ?? 0);

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

    $nomina_actual[] = [
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
        "total_pago" => $total_pago
    ];
}

echo json_encode([
    "success" => true,
    "message" => "Nómina semanal actual cargada correctamente",
    "semana" => [
        "fecha_inicio" => $fecha_inicio,
        "fecha_fin" => $fecha_fin,
        "dias_habiles_semana" => $dias_habiles_semana,
        "dias_habiles_transcurridos" => $dias_habiles_transcurridos
    ],
    "data" => $nomina_actual
]);