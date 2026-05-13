<?php

require_once "../models/asistencias.php";

class AsistenciaController {

    // OBTENER TODAS LAS ASISTENCIAS
    public function index(): void {

        try {

            echo json_encode([
                "success" => true,
                "data" => Asistencia::obtenerTodas()
            ]);

        } catch (Exception $e) {

            echo json_encode([
                "success" => false,
                "message" => "Error al obtener asistencias"
            ]);
        }
    }

    // REGISTRAR POR QR
    public function registrar(): void {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['qr']) || empty($data['qr'])) {

            echo json_encode([
                "success" => false,
                "message" => "QR no enviado"
            ]);
            return;
        }

        $resultado = Asistencia::registrarPorQR($data['qr']);

        echo json_encode([
            "success" => true,
            "message" => $resultado
        ]);
    }

    // OBTENER POR EMPLEADO
    public function obtenerPorEmpleado(int $empleado_id): void {

        try {

            echo json_encode([
                "success" => true,
                "data" => Asistencia::obtenerPorEmpleado($empleado_id)
            ]);

        } catch (Exception $e) {

            echo json_encode([
                "success" => false,
                "message" => "Error al obtener asistencias del empleado"
            ]);
        }
    }

    // ACTUALIZAR (PENDIENTE)
    public function update(int $id): void {

        echo json_encode([
            "success" => false,
            "message" => "Método update no implementado"
        ]);
    }

    // ELIMINAR (PENDIENTE)
    public function delete(int $id): void {

        echo json_encode([
            "success" => false,
            "message" => "Método delete no implementado"
        ]);
    }
}

?>