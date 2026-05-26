<?php

require_once __DIR__ . "/../models/Nomina.php";

class NominaController
{
    private $model;

    public function __construct($conexion)
    {
        $this->model = new Nomina($conexion);
    }

    public function listar()
    {
        try {
            echo json_encode([
                "success" => true,
                "data" => $this->model->listar()
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al listar nóminas",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function obtener($id)
    {
        try {
            $nomina = $this->model->obtener($id);

            if (!$nomina) {
                echo json_encode([
                    "success" => false,
                    "message" => "Nómina no encontrada"
                ]);
                return;
            }

            echo json_encode([
                "success" => true,
                "data" => $nomina
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al obtener nómina",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function crear($data)
    {
        try {
            if (!$data) {
                echo json_encode([
                    "success" => false,
                    "message" => "No se recibieron datos"
                ]);
                return;
            }

            $resultado = $this->model->crear($data);

            echo json_encode([
                "success" => $resultado,
                "message" => "Nómina registrada correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al registrar nómina",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function actualizar($id, $data)
    {
        try {
            if (!$id) {
                echo json_encode([
                    "success" => false,
                    "message" => "ID no recibido"
                ]);
                return;
            }

            if (!$data) {
                echo json_encode([
                    "success" => false,
                    "message" => "No se recibieron datos"
                ]);
                return;
            }

            $resultado = $this->model->actualizar($id, $data);

            echo json_encode([
                "success" => $resultado,
                "message" => "Nómina actualizada correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al actualizar nómina",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function eliminar($id)
    {
        try {
            if (!$id) {
                echo json_encode([
                    "success" => false,
                    "message" => "ID no recibido"
                ]);
                return;
            }

            $resultado = $this->model->eliminar($id);

            echo json_encode([
                "success" => $resultado,
                "message" => "Nómina eliminada correctamente"
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error al eliminar nómina",
                "error" => $e->getMessage()
            ]);
        }
    }
}