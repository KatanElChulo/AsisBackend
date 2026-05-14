<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/conexion.php";

$token = bin2hex(random_bytes(32));
$expira_en = date("Y-m-d H:i:s", strtotime("+30 seconds"));

$sql = "INSERT INTO qr_tokens (token, tipo, expira_en)
        VALUES (?, 'general', ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $token, $expira_en);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "token" => $token,
        "expira_en" => $expira_en
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al generar QR"
    ]);
}