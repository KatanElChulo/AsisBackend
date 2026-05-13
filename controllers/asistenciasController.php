<?php

<<<<<<< HEAD
require_once "../models/asistencias.php";
=======
require_once "../models/Asistencias.php";
>>>>>>> acfd6cfe4bef54244906ad245c8f120e51dad102

class AsistenciasController {

    // OBTENER TODAS LAS ASISTENCIAS
    public function index(): void {
<<<<<<< HEAD

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
=======
        echo json_encode(
            Asistencias::obtenerTodas()
        );
>>>>>>> acfd6cfe4bef54244906ad245c8f120e51dad102
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

        $resultado = Asistencias::registrarPorQR($data['qr']);

        echo json_encode([
            "success" => true,
            "message" => $resultado
        ]);
    }

    // OBTENER POR EMPLEADO
    public function obtenerPorEmpleado(int $empleado_id): void {

<<<<<<< HEAD
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
=======
        echo json_encode(
            Asistencias::obtenerPorEmpleado($empleado_id)
        );
>>>>>>> acfd6cfe4bef54244906ad245c8f120e51dad102
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