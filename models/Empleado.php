<?php

require_once __DIR__ . "/../config/database.php";

class Empleado {

    public static function obtenerTodos() {

        global $conn;

        $sql = "SELECT * FROM empleados";

        $resultado = $conn->query($sql);

        $empleados = [];

        while($fila = $resultado->fetch_assoc()) {
            $empleados[] = $fila;
        }

        return $empleados;
    }

    public static function obtenerPorId($id) {

        global $conn;

        $sql = "SELECT * FROM empleados WHERE id = $id";

        $resultado = $conn->query($sql);

        return $resultado->fetch_assoc();
    }

    public static function crear($data) {

        global $conn;

        $sql = "INSERT INTO empleados (
            rol_id,
            nombre,
            apellido_paterno,
            apellido_materno,
            correo,
            password,
            telefono,
            sueldo_diario,
            horario_entrada,
            horario_salida,
            activo
        ) VALUES (
            '{$data['rol_id']}',
            '{$data['nombre']}',
            '{$data['apellido_paterno']}',
            '{$data['apellido_materno']}',
            '{$data['correo']}',
            '{$data['password']}',
            '{$data['telefono']}',
            '{$data['sueldo_diario']}',
            '{$data['horario_entrada']}',
            '{$data['horario_salida']}',
            1
        )";

        return $conn->query($sql);
    }
}
?>