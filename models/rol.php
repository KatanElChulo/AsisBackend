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

    // OBTENER ROL POR NOMBRE
    public static function obtenerPorNombre($nombre) {

        global $conn;

        $sql = "SELECT * FROM roles WHERE nombre = '$nombre'";

        $resultado = $conn->query($sql);

        return $resultado->fetch_assoc();
    }

    // CREAR ROL
    public static function crear($data) {

        global $conn;

        $sql = "INSERT INTO roles (
            nombre
        ) VALUES (
            '{$data['nombre']}'
        )";

        return $conn->query($sql);
    }

    // ACTUALIZAR ROL
    public static function actualizar($nombreActual, $data) {

        global $conn;

        $sql = "UPDATE roles SET

            nombre = '{$data['nombre']}'

            WHERE nombre = '$nombreActual'
        ";

        return $conn->query($sql);
    }

    // ELIMINAR ROL
    public static function eliminar($nombre) {

        global $conn;

        $sql = "DELETE FROM roles WHERE nombre = '$nombre'";

        return $conn->query($sql);
    }
}
?>