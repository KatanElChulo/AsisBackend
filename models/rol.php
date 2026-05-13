<?php

require_once __DIR__ . "/../config/database.php";

class Rol {


     //Consulta
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
      // (Buscar) Obtener rol por id
    public static function obtenerPorId($id) {

        global $conn;

        $sql = "SELECT * FROM roles WHERE id = $id";

        $resultado = $conn->query($sql);

        return $resultado->fetch_assoc();
    }
    //Crear
     public static function crear($data) {

        global $conn;

        $sql = "INSERT INTO roles (
            nombre
        ) VALUES (
            '{$data['nombre']}'
        )";

        return $conn->query($sql);
    }

    //Actualizar
        public static function actualizar($id, $data) {

        global $conn;

        $sql = "UPDATE roles SET

            nombre = '{$data['nombre']}'

            WHERE id = $id
        ";

        return $conn->query($sql);
    }

    //Borrar
     public static function eliminar($id) {

        global $conn;

        $sql = "DELETE FROM roles WHERE id = $id";

        return $conn->query($sql);
    }
}
?>