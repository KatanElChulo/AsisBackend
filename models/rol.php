<?php

require_once __DIR__ . "/../config/database.php";

class Rol {

    // CONSULTAR TODOS
    public static function obtenerTodos() {

        global $conn;

        $sql = "SELECT * FROM roles";

        $resultado = $conn->query($sql);

        $roles = [];

        while($fila = $resultado->fetch_assoc()) {
            $roles[] = $fila;
        }

        return $roles;
    }

    // OBTENER ROL POR ID
    public static function obtenerPorId($id) {

        global $conn;

        $sql = "SELECT * FROM roles WHERE id = $id";

        $resultado = $conn->query($sql);

        return $resultado->fetch_assoc();
    }

    // CREAR ROL
    public static function crear($data) {

        global $conn;

        $sql = "INSERT INTO roles (nombre)
                VALUES ('{$data['nombre']}')";

        return $conn->query($sql);
    }

    // ACTUALIZAR ROL POR ID
    public static function actualizar($id, $data) {

        global $conn;

        $sql = "UPDATE roles SET
                    nombre = '{$data['nombre']}'
                WHERE id = $id";

        return $conn->query($sql);
    }

    // ELIMINAR ROL POR ID
    public static function eliminar($id) {

        global $conn;

        $sql = "DELETE FROM roles WHERE id = $id";

        return $conn->query($sql);
    }
}

?>