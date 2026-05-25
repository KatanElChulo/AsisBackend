<?php

require_once __DIR__ . "/../config/database.php";

class Rol
{
    private static function db()
    {
        global $conn;

        if (!isset($conn)) {
            throw new Exception("No existe la variable de conexión conn");
        }

        return $conn;
    }

    public static function obtenerTodos()
    {
        $db = self::db();

        $sql = "SELECT id, nombre FROM roles ORDER BY id ASC";
        $resultado = $db->query($sql);

        if (!$resultado) {
            throw new Exception($db->error);
        }

        $roles = [];

        while ($fila = $resultado->fetch_assoc()) {
            $roles[] = $fila;
        }

        return $roles;
    }

    public static function obtenerPorId($id)
    {
        $db = self::db();

        $sql = "SELECT id, nombre FROM roles WHERE id = ? LIMIT 1";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception($db->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public static function crear($data)
    {
        $db = self::db();

        $nombre = $data["nombre"] ?? "";

        if ($nombre === "") {
            throw new Exception("El nombre del rol es obligatorio");
        }

        $sql = "INSERT INTO roles (nombre) VALUES (?)";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception($db->error);
        }

        $stmt->bind_param("s", $nombre);

        return $stmt->execute();
    }

    public static function actualizar($id, $data)
    {
        $db = self::db();

        $nombre = $data["nombre"] ?? "";

        if ($nombre === "") {
            throw new Exception("El nombre del rol es obligatorio");
        }

        $sql = "UPDATE roles SET nombre = ? WHERE id = ?";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception($db->error);
        }

        $stmt->bind_param("si", $nombre, $id);

        return $stmt->execute();
    }

    public static function eliminar($id)
    {
        $db = self::db();

        $sql = "DELETE FROM roles WHERE id = ?";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception($db->error);
        }

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}