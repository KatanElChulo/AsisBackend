<?php

require_once __DIR__ . "/../models/Rol.php";

class RolesController {

    // OBTENER ROLES
    public function index() {

        if(isset($_GET['id'])) {

            echo json_encode(
                Rol::obtenerPorId($_GET['id'])
            );
        } else {

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
    public function update($id) {

        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        Rol::actualizar($id, $data);

        echo json_encode([
            "mensaje" => "Rol actualizado"
        ]);
    }

    // ELIMINAR ROL
    public function delete($id) {

        Rol::eliminar($id);

        echo json_encode([
            "mensaje" => "Rol eliminado"
        ]);
    }
}

?>