<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../config/database.php";

echo json_encode([
    "success" => true,
    "message" => "database.php cargó",
    "conn_existe" => isset($conn),
    "conexion_existe" => isset($conexion),
    "mysqli_existe" => isset($mysqli)
]);

exit;