<?php

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

try {

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
        Generar token compatible aunque random_bytes no funcione.
    */
    if (function_exists("random_bytes")) {
        $token = bin2hex(random_bytes(16));
    } else {
        $token = md5(uniqid(mt_rand(), true));
    }

    /*
        Revisar columnas reales de qr_tokens.
    */
    $columnasResultado = $db->query("SHOW COLUMNS FROM qr_tokens");

    if (!$columnasResultado) {
        echo json_encode([
            "success" => false,
            "message" => "No se pudo leer la estructura de qr_tokens",
            "error" => $db->error
        ]);
        exit;
    }

    $columnas = [];

    while ($fila = $columnasResultado->fetch_assoc()) {
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

    /*
        Desactivar tokens anteriores si existe columna activo.
    */
    if (in_array("activo", $columnas)) {
        $db->query("UPDATE qr_tokens SET activo = 0 WHERE activo = 1");
    }

    /*
        Marcar tokens anteriores como usados si existe columna usado.
    */
    if (in_array("usado", $columnas)) {
        $db->query("UPDATE qr_tokens SET usado = 1 WHERE usado = 0");
    }

    /*
        Armar INSERT según las columnas que sí existen.
    */
    $campos = [];
    $valores = [];
    $tipos = "";
    $parametros = [];

    $campos[] = "token";
    $valores[] = "?";
    $tipos .= "s";
    $parametros[] = $token;

    if (in_array("activo", $columnas)) {
        $campos[] = "activo";
        $valores[] = "1";
    }

    if (in_array("usado", $columnas)) {
        $campos[] = "usado";
        $valores[] = "0";
    }

    if (in_array("fecha_creacion", $columnas)) {
        $campos[] = "fecha_creacion";
        $valores[] = "NOW()";
    }

    if (in_array("creado_en", $columnas)) {
        $campos[] = "creado_en";
        $valores[] = "NOW()";
    }

    if (in_array("created_at", $columnas)) {
        $campos[] = "created_at";
        $valores[] = "NOW()";
    }

    if (in_array("fecha_expiracion", $columnas)) {
        $campos[] = "fecha_expiracion";
        $valores[] = "DATE_ADD(NOW(), INTERVAL 30 SECOND)";
    }

    if (in_array("expira_en", $columnas)) {
        $campos[] = "expira_en";
        $valores[] = "DATE_ADD(NOW(), INTERVAL 30 SECOND)";
    }

    $sql = "INSERT INTO qr_tokens (" . implode(", ", $campos) . ")
            VALUES (" . implode(", ", $valores) . ")";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Error al preparar INSERT",
            "sql" => $sql,
            "error" => $db->error,
            "columnas_detectadas" => $columnas
        ]);
        exit;
    }

    if (!empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Error al guardar token QR",
            "sql" => $sql,
            "error" => $stmt->error,
            "columnas_detectadas" => $columnas
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "QR generado correctamente",
        "token" => $token,
        "columnas_detectadas" => $columnas
    ]);

} catch (Throwable $e) {

    echo json_encode([
        "success" => false,
        "message" => "Error fatal en generar_qr.php",
        "error" => $e->getMessage(),
        "archivo" => $e->getFile(),
        "linea" => $e->getLine()
    ]);
}