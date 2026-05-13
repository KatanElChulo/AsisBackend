<?php

include "../config/database.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . "/../controllers/asistenciasController.php";
$controller = new AsistenciaController();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // =========================
    // GET
    // =========================
    case "GET":

        if (isset($_GET['empleado_id'])) {

            $controller->obtenerPorEmpleado((int) $_GET['empleado_id']);

        } else {

            $controller->index();
        }

        break;

    // =========================
    // POST
    // =========================
    case "POST":

        $controller->registrar();

        break;

    // =========================
    // PUT
    // =========================
    case "PUT":

        if (!isset($_GET['id'])) {

            echo json_encode([
                "success" => false,
                "message" => "ID no proporcionado"
            ]);
            exit;
        }

        $controller->update((int) $_GET['id']);

        break;

    // =========================
    // DELETE
    // =========================
    case "DELETE":

        if (!isset($_GET['id'])) {

            echo json_encode([
                "success" => false,
                "message" => "ID no proporcionado"
            ]);
            exit;
        }

        $controller->delete((int) $_GET['id']);

        break;

    // =========================
    // MÉTODO NO SOPORTADO
    // =========================
    default:

        echo json_encode([
            "success" => false,
            "message" => "Método no permitido"
        ]);

        break;
}

?>