<?php
include "../config/database.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . "/../controllers/empleadosController.php";
require_once __DIR__ . "/../config/database.php";
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
if($method == "PUT") {

    if(isset($_GET['id'])) {

        $controller->update($_GET['id']);
    }
    
}
if($method == "DELETE") {

    if(isset($_GET['id'])) {

        $controller->delete($_GET['id']);
    }

}
?>