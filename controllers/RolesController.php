<?php

require_once __DIR__ . "/../models/rol.php";

class RolesController
{
    public function index()
    {
        try {
            if (isset($_GET["id"])) {
                $rol = Rol::obtenerPorId($_GET["id"]);

                echo json_encode($rol);
                return;
            }

            echo json_encode(Rol::obtenerTodos());

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al obtener roles",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function store()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            Rol::crear($data);

            echo json_encode([
                "success" => true,
                "message" => "Rol creado correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al crear rol",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function update($id)
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            Rol::actualizar($id, $data);

            echo json_encode([
                "success" => true,
                "message" => "Rol actualizado correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al actualizar rol",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function delete($id)
    {
        try {
            Rol::eliminar($id);

            echo json_encode([
                "success" => true,
                "message" => "Rol eliminado correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al eliminar rol",
                "error" => $e->getMessage()
            ]);
        }
    }
}