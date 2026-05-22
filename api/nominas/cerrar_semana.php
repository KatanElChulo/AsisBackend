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

$hoy = new DateTime();

$lunes = clone $hoy;
$lunes->modify("monday this week");

$viernes = clone $lunes;
$viernes->modify("+4 days");

$fecha_inicio = $lunes->format("Y-m-d");
$fecha_fin = $viernes->format("Y-m-d");

function contarDiasHabiles($inicio, $fin)
{
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

$dias_habiles_semana = contarDiasHabiles($fecha_inicio, $fecha_fin);

/*
    Obtener empleados activos.
*/
$sqlEmpleados = "SELECT 
                    id,
                    sueldo_diario
                 FROM empleados
                 WHERE activo = 1";

$resultEmpleados = $db->query($sqlEmpleados);

$guardadas = 0;
$actualizadas = 0;

while ($empleado = $resultEmpleados->fetch_assoc()) {
    $empleado_id = $empleado["id"];
    $sueldo_diario = floatval($empleado["sueldo_diario"]);

    /*
        Contar días trabajados de toda la semana.
    */
    $sqlTrabajados = "SELECT COUNT(DISTINCT fecha) AS dias_trabajados
                      FROM asistencias
                      WHERE empleado_id = ?
                      AND fecha BETWEEN ? AND ?
                      AND hora_entrada IS NOT NULL
                      AND hora_salida IS NOT NULL";

    $stmtTrabajados = $db->prepare($sqlTrabajados);
    $stmtTrabajados->bind_param("iss", $empleado_id, $fecha_inicio, $fecha_fin);
    $stmtTrabajados->execute();

    $resultadoTrabajados = $stmtTrabajados->get_result()->fetch_assoc();

    $dias_trabajados = intval($resultadoTrabajados["dias_trabajados"] ?? 0);

    $faltas = $dias_habiles_semana - $dias_trabajados;

    if ($faltas < 0) {
        $faltas = 0;
    }

    $total_pago = $dias_trabajados * $sueldo_diario;

    /*
        Revisar si ya existe nómina para ese empleado y esa semana.
    */
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
        /*
            Si ya existe, la actualizamos.
        */
        $nomina_id = $existe["id"];

        $sqlActualizar = "UPDATE nominas SET
                            dias_trabajados = ?,
                            faltas = ?,
                            sueldo_diario = ?,
                            total_pago = ?
                          WHERE id = ?";

        $stmtActualizar = $db->prepare($sqlActualizar);
        $stmtActualizar->bind_param(
            "iiddi",
            $dias_trabajados,
            $faltas,
            $sueldo_diario,
            $total_pago,
            $nomina_id
        );

        if ($stmtActualizar->execute()) {
            $actualizadas++;
        }

    } else {
        /*
            Si no existe, se crea.
        */
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
            $guardadas++;
        }
    }
}

echo json_encode([
    "success" => true,
    "message" => "Semana cerrada correctamente",
    "data" => [
        "fecha_inicio" => $fecha_inicio,
        "fecha_fin" => $fecha_fin,
        "guardadas" => $guardadas,
        "actualizadas" => $actualizadas
    ]
]);