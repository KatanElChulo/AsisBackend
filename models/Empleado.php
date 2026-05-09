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
    public static function actualizar($id, $data) {

    global $conn;

    $sql = "UPDATE empleados SET

        rol_id = '{$data['rol_id']}',
        nombre = '{$data['nombre']}',
        apellido_paterno = '{$data['apellido_paterno']}',
        apellido_materno = '{$data['apellido_materno']}',
        correo = '{$data['correo']}',
        password = '{$data['password']}',
        telefono = '{$data['telefono']}',
        sueldo_diario = '{$data['sueldo_diario']}',
        horario_entrada = '{$data['horario_entrada']}',
        horario_salida = '{$data['horario_salida']}'

        WHERE id = $id
    ";

    return $conn->query($sql);
}

public static function eliminar($id) {

    global $conn;

    $sql = "DELETE FROM empleados WHERE id = $id";

    return $conn->query($sql);
}
}
?>