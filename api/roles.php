<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . "/../controllers/RolesController.php";

$controller = new RolesController();

$controller->index();