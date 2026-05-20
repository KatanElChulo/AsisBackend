<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../controllers/NominaController.php";

$db = $conexion ?? $conn ?? $mysqli ?? null;

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró la conexión a la base de datos"
    ]);
    exit;
}

$controller = new NominaController($db);

$method = $_SERVER["REQUEST_METHOD"];
$data = json_decode(file_get_contents("php://input"), true);
$id = $_GET["id"] ?? null;

switch ($method) {
    case "GET":
        if ($id) {
            $controller->obtener($id);
        } else {
            $controller->listar();
        }
        break;

    case "POST":
        $controller->crear($data);
        break;

    case "PUT":
        $controller->actualizar($id, $data);
        break;

    case "DELETE":
        $controller->eliminar($id);
        break;

    default:
        echo json_encode([
            "success" => false,
            "message" => "Método no permitido"
        ]);
        break;
}