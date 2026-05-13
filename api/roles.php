<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . "/../controllers/RolesController.php";

$controller = new RolesController();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {

    case "GET":
        $controller->index();
        break;

    case "POST":
        $controller->store();
        break;

    case "PUT":
        $controller->update($_GET['id']);
        break;

    case "DELETE":
        $controller->delete($_GET['id']);
        break;
}
?>