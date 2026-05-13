<?php

require_once __DIR__ . "/../models/Rol.php";

class RolesController {

    // OBTENER ROLES
    public function index() {

        if(isset($_GET['nombre'])) {

            echo json_encode(
                Rol::obtenerPorNombre($_GET['nombre'])
            );
        }
        else {

            echo json_encode(
                Rol::obtenerTodos()
            );
        }
    }

    // CREAR ROL
    public function store() {

        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        Rol::crear($data);

        echo json_encode([
            "mensaje" => "Rol creado"
        ]);
    }

    // ACTUALIZAR ROL
    public function update($nombre) {

        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        Rol::actualizar($nombre, $data);

        echo json_encode([
            "mensaje" => "Rol actualizado"
        ]);
    }

    // ELIMINAR ROL
    public function delete($nombre) {

        Rol::eliminar($nombre);

        echo json_encode([
            "mensaje" => "Rol eliminado"
        ]);
    }
}
?>