<?php

require_once __DIR__ . "/../config/database.php";

class Rol {

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
}
?>