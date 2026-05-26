<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once __DIR__ . "/../config/database.php";

$db = $conn ?? $conexion ?? $mysqli ?? null;

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró conexión a la base de datos"
    ]);
    exit;
}

try {

    $token = bin2hex(random_bytes(16));

    /*
        Desactiva tokens anteriores.
        Así solo el QR más reciente queda válido.
    */
    $sqlDesactivar = "UPDATE qr_tokens SET activo = 0 WHERE activo = 1";

    if (!$db->query($sqlDesactivar)) {
        echo json_encode([
            "success" => false,
            "message" => "Error al desactivar tokens anteriores",
            "error" => $db->error
        ]);
        exit;
    }

    /*
        Inserta el nuevo token.
        Este código espera que qr_tokens tenga:
        id, token, activo, fecha_creacion
    */
    $sqlInsertar = "INSERT INTO qr_tokens 
                    (
                        token,
                        activo,
                        fecha_creacion
                    )
                    VALUES (?, 1, NOW())";

    $stmt = $db->prepare($sqlInsertar);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Error al preparar inserción del token",
            "error" => $db->error
        ]);
        exit;
    }

    $stmt->bind_param("s", $token);

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Error al guardar token QR",
            "error" => $stmt->error
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "QR generado correctamente",
        "token" => $token
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Error al generar QR",
        "error" => $e->getMessage()
    ]);
}dasd