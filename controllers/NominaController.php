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
        echo json_encode([
            "success" => true,
            "data" => $this->model->listar()
        ]);
    }

    public function obtener($id)
    {
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
    }

    public function crear($data)
    {
        if (
            empty($data["empleado_id"]) ||
            empty($data["fecha_inicio"]) ||
            empty($data["fecha_fin"]) ||
            !isset($data["dias_trabajados"]) ||
            !isset($data["faltas"]) ||
            !isset($data["sueldo_diario"])
        ) {
            echo json_encode([
                "success" => false,
                "message" => "Datos incompletos"
            ]);
            return;
        }

        $resultado = $this->model->crear($data);

        echo json_encode([
            "success" => $resultado,
            "message" => $resultado ? "Nómina registrada correctamente" : "Error al registrar nómina"
        ]);
    }

    public function actualizar($id, $data)
    {
        if (!$id) {
            echo json_encode([
                "success" => false,
                "message" => "ID no recibido"
            ]);
            return;
        }

        $resultado = $this->model->actualizar($id, $data);

        echo json_encode([
            "success" => $resultado,
            "message" => $resultado ? "Nómina actualizada correctamente" : "Error al actualizar nómina"
        ]);
    }

    public function eliminar($id)
    {
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
            "message" => $resultado ? "Nómina eliminada correctamente" : "Error al eliminar nómina"
        ]);
    }
}