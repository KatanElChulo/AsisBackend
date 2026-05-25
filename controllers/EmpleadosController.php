<?php

require_once __DIR__ . "/../models/Empleado.php";

class EmpleadosController
{
    public function index()
    {
        try {
            echo json_encode(Empleado::obtenerTodos());

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al obtener empleados",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $empleado = Empleado::obtenerPorId($id);

            if (!$empleado) {
                echo json_encode([
                    "success" => false,
                    "message" => "Empleado no encontrado"
                ]);
                return;
            }

            echo json_encode($empleado);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al obtener empleado",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function store()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            Empleado::crear($data);

            echo json_encode([
                "success" => true,
                "message" => "Empleado creado correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al crear empleado",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function update($id)
    {
        try {
            if (!$id) {
                echo json_encode([
                    "success" => false,
                    "message" => "ID no recibido"
                ]);
                return;
            }

            $data = json_decode(file_get_contents("php://input"), true);

            Empleado::actualizar($id, $data);

            echo json_encode([
                "success" => true,
                "message" => "Empleado actualizado correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al actualizar empleado",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function delete($id)
    {
        try {
            if (!$id) {
                echo json_encode([
                    "success" => false,
                    "message" => "ID no recibido"
                ]);
                return;
            }

            Empleado::eliminar($id);

            echo json_encode([
                "success" => true,
                "message" => "Empleado eliminado correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al eliminar empleado",
                "error" => $e->getMessage()
            ]);
        }
    }
}