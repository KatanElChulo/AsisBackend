<?php

require_once __DIR__ . "/../config/database.php";

class Rol
{
    private static function getDB()
    {
        global $conn, $conexion, $mysqli;

        if (isset($conn) && $conn instanceof mysqli) {
            return $conn;
        }

        if (isset($conexion) && $conexion instanceof mysqli) {
            return $conexion;
        }

        if (isset($mysqli) && $mysqli instanceof mysqli) {
            return $mysqli;
        }

        throw new Exception("No se encontró una conexión válida a la base de datos");
    }

    public static function obtenerTodos()
    {
        $db = self::getDB();

        $sql = "SELECT id, nombre FROM roles ORDER BY id ASC";

        $resultado = $db->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar roles: " . $db->error);
        }

        $roles = [];

        while ($fila = $resultado->fetch_assoc()) {
            $roles[] = $fila;
        }

        return $roles;
    }

    public static function obtenerPorId($id)
    {
        $db = self::getDB();

        $sql = "SELECT id, nombre FROM roles WHERE id = ? LIMIT 1";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $db->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->fetch_assoc();
    }

    public static function crear($data)
    {
        $db = self::getDB();

        $nombre = trim($data["nombre"] ?? "");

        if ($nombre === "") {
            throw new Exception("El nombre del rol es obligatorio");
        }

        $sql = "INSERT INTO roles (nombre) VALUES (?)";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar inserción: " . $db->error);
        }

        $stmt->bind_param("s", $nombre);

        if (!$stmt->execute()) {
            throw new Exception("Error al crear rol: " . $stmt->error);
        }

        return true;
    }

    public static function actualizar($id, $data)
    {
        $db = self::getDB();

        $nombre = trim($data["nombre"] ?? "");

        if ($nombre === "") {
            throw new Exception("El nombre del rol es obligatorio");
        }

        $sql = "UPDATE roles SET nombre = ? WHERE id = ?";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar actualización: " . $db->error);
        }

        $stmt->bind_param("si", $nombre, $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar rol: " . $stmt->error);
        }

        return true;
    }

    public static function eliminar($id)
    {
        $db = self::getDB();

        $sql = "DELETE FROM roles WHERE id = ?";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar eliminación: " . $db->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar rol: " . $stmt->error);
        }

        return true;
    }
}