<?php

require_once "../models/Empleado.php";

class EmpleadosController {

    public function index() {
        echo json_encode(
            Empleado::obtenerTodos()
        );
    }

    public function store() {

        $data = json_decode(file_get_contents("php://input"), true);

        $resultado = Empleado::crear($data);

        echo json_encode([
            "success" => $resultado
        ]);
    }
    public function show($id) {

    echo json_encode(
        Empleado::obtenerPorId($id)
    );
}
public function update($id) {

    $data = json_decode(file_get_contents("php://input"), true);

    $resultado = Empleado::actualizar($id, $data);

    echo json_encode([
        "success" => $resultado
    ]);
}

public function delete($id) {

    $resultado = Empleado::eliminar($id);

    echo json_encode([
        "success" => $resultado
    ]);
}
}
?>