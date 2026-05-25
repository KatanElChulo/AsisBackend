<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once __DIR__ . "/../controllers/RolesController.php";

$controller = new RolesController();

$method = $_SERVER["REQUEST_METHOD"];
$id = $_GET["id"] ?? null;

switch ($method) {
    case "GET":
        $controller->index();
        break;

    case "POST":
        $controller->store();
        break;

    case "PUT":
        $controller->update($id);
        break;

    case "DELETE":
        $controller->delete($id);
        break;

    default:
        echo json_encode([
            "success" => false,
            "message" => "Método no permitido"
        ]);
        break;
}