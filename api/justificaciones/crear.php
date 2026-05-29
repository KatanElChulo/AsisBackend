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

/*
    VALIDAR MÉTODO
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);
    exit;
}

/*
    DATOS RECIBIDOS
    Como subiremos archivo, usamos $_POST y $_FILES.
*/

$empleado_id = $_POST["empleado_id"] ?? null;
$admin_id = $_POST["admin_id"] ?? null;
$fecha_falta = $_POST["fecha_falta"] ?? null;
$motivo = $_POST["motivo"] ?? null;

if (!$empleado_id || !$admin_id || !$fecha_falta || !$motivo) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

if (!isset($_FILES["archivo"])) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió ningún archivo"
    ]);
    exit;
}

$archivo = $_FILES["archivo"];

if ($archivo["error"] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "Error al subir el archivo",
        "error_code" => $archivo["error"]
    ]);
    exit;
}

/*
    VALIDAR TAMAÑO
    Máximo 5 MB.
*/

$tamanoMaximo = 5 * 1024 * 1024;

if ($archivo["size"] > $tamanoMaximo) {
    echo json_encode([
        "success" => false,
        "message" => "El archivo es demasiado grande. Máximo permitido: 5 MB"
    ]);
    exit;
}

/*
    VALIDAR EXTENSIÓN
*/

$nombreOriginal = $archivo["name"];
$extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

$extensionesPermitidas = ["jpg", "jpeg", "png", "pdf"];

if (!in_array($extension, $extensionesPermitidas)) {
    echo json_encode([
        "success" => false,
        "message" => "Formato no permitido. Solo se aceptan JPG, PNG o PDF"
    ]);
    exit;
}

/*
    CREAR CARPETA SI NO EXISTE
*/

$carpetaDestino = __DIR__ . "/../../uploads/justificaciones/";

if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0755, true);
}

/*
    GENERAR NOMBRE SEGURO
*/

$nombreArchivo = "justificacion_" .
    intval($empleado_id) . "_" .
    str_replace("-", "", $fecha_falta) . "_" .
    time() . "." .
    $extension;

$rutaFinal = $carpetaDestino . $nombreArchivo;

/*
    MOVER ARCHIVO
*/

if (!move_uploaded_file($archivo["tmp_name"], $rutaFinal)) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo guardar el archivo en el servidor"
    ]);
    exit;
}

/*
    RUTA QUE SE GUARDARÁ EN BASE DE DATOS
*/

$rutaBD = "uploads/justificaciones/" . $nombreArchivo;

/*
    EVITAR DUPLICADOS PENDIENTES O APROBADOS PARA LA MISMA FECHA
*/

$sqlExiste = "SELECT id, estado
              FROM justificaciones
              WHERE empleado_id = ?
              AND fecha_falta = ?
              AND estado IN ('PENDIENTE', 'APROBADA')
              LIMIT 1";

$stmtExiste = $db->prepare($sqlExiste);

if (!$stmtExiste) {
    echo json_encode([
        "success" => false,
        "message" => "Error al validar justificación existente",
        "error" => $db->error
    ]);
    exit;
}

$stmtExiste->bind_param("is", $empleado_id, $fecha_falta);
$stmtExiste->execute();

$existe = $stmtExiste->get_result()->fetch_assoc();

if ($existe) {
    echo json_encode([
        "success" => false,
        "message" => "Ya existe una justificación pendiente o aprobada para esa fecha"
    ]);
    exit;
}

/*
    INSERTAR JUSTIFICACIÓN
*/

$sql = "INSERT INTO justificaciones
        (
            empleado_id,
            admin_id,
            fecha_falta,
            motivo,
            archivo,
            estado,
            fecha_solicitud
        )
        VALUES (?, ?, ?, ?, ?, 'PENDIENTE', NOW())";

$stmt = $db->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar registro",
        "error" => $db->error
    ]);
    exit;
}

$stmt->bind_param(
    "iisss",
    $empleado_id,
    $admin_id,
    $fecha_falta,
    $motivo,
    $rutaBD
);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar justificación",
        "error" => $stmt->error
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Justificación enviada correctamente. Queda pendiente de revisión."
]);