<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../config/database.php";

echo json_encode([
    "paso" => "database.php cargó",
    "conn_existe" => isset($conn),
    "conexion_existe" => isset($conexion),
    "mysqli_existe" => isset($mysqli)
]);

exit;