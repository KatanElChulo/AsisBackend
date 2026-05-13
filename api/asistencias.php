<?php
include "../config/database.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once "../controllers/AsistenciaController.php";

$controller = new AsistenciaController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == "GET") {

    // Obtener asistencias (todas o por empleado)
    if (isset($_GET['empleado_id'])) {

        $controller->obtenerPorEmpleado((int)$_GET['empleado_id']);

    } else {

        $controller->index();
    }
}

if ($method == "POST") {

    // Registrar asistencia por QR
    $controller->registrar();
}

if ($method == "PUT") {

    // (Opcional) actualizar asistencia
    if (isset($_GET['id'])) {

        $controller->update((int)$_GET['id']);
    }
}

if ($method == "DELETE") {

    // (Opcional) eliminar registro
    if (isset($_GET['id'])) {

        $controller->delete((int)$_GET['id']);
    }
}
?>