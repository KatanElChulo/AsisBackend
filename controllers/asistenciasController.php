<?php

require_once "../models/Empleado.php";

class EmpleadosController {

    public function index(): void {
        echo json_encode(
            Empleado::obtenerTodos()
        );
    }

    public function store(): void {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode([
                "success" => false,
                "message" => "Datos inválidos"
            ]);
            return;
        }

        $resultado = Empleado::crear($data);

        echo json_encode([
            "success" => $resultado
        ]);
    }

    public function show(int $id): void {

        $empleado = Empleado::obtenerPorId($id);

        echo json_encode($empleado);
    }

    public function update(int $id): void {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode([
                "success" => false,
                "message" => "Datos inválidos"
            ]);
            return;
        }

        $resultado = Empleado::actualizar($id, $data);

        echo json_encode([
            "success" => $resultado
        ]);
    }

    public function delete(int $id): void {

        $resultado = Empleado::eliminar($id);

        echo json_encode([
            "success" => $resultado
        ]);
    }
}
?>