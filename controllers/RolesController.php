<?php

require_once __DIR__ . "/../models/Rol.php";

class RolesController {

    public function index() {

        echo json_encode(
            Rol::obtenerTodos()
        );
    }
}
?>