<?php
include "../config/database.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST");

require_once "../controllers/empleadosController.php";

$controller = new EmpleadosController();

$method = $_SERVER['REQUEST_METHOD'];

if($method == "GET") {

    if(isset($_GET['id'])) {

        $controller->show($_GET['id']);

    } else {

        $controller->index();
    }
}

if($method == "POST") {
    $controller->store();
}
?>