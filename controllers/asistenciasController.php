<?php

require_once "../models/Asistencias.php";

class AsistenciasController {

    public function index(): void {
        echo json_encode(
            Asistencias::obtenerTodas()
        );
    }

    public function registrar(): void {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['qr'])) {
            echo json_encode([
                "success" => false,
                "message" => "QR no enviado"
            ]);
            return;
        }

        $resultado = Asistencias::registrarPorQR($data['qr']);

        echo json_encode([
            "success" => true,
            "message" => $resultado
        ]);
    }

    public function obtenerPorEmpleado(int $empleado_id): void {

        echo json_encode(
            Asistencias::obtenerPorEmpleado($empleado_id)
        );
    }

    public function update(int $id): void {
        echo json_encode([
            "message" => "No implementado"
        ]);
    }

    public function delete(int $id): void {
        echo json_encode([
            "message" => "No implementado"
        ]);
    }
}
?>