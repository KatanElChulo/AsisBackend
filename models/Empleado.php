<?php

require_once __DIR__ . "/../config/database.php";

class Empleado
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

        $sql = "SELECT 
                    empleados.*,
                    roles.nombre AS rol_nombre
                FROM empleados
                INNER JOIN roles
                ON empleados.rol_id = roles.id
                WHERE empleados.activo = 1
                ORDER BY empleados.id DESC";

        $resultado = $db->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar empleados: " . $db->error);
        }

        $empleados = [];

        while ($fila = $resultado->fetch_assoc()) {
            $empleados[] = $fila;
        }

        return $empleados;
    }

    public static function obtenerPorId($id)
    {
        $db = self::db();

        $sql = "SELECT * FROM empleados WHERE id = ? LIMIT 1";

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
        $db = self::db();

        $rol_id = $data["rol_id"] ?? null;
        $nombre = $data["nombre"] ?? "";
        $apellido_paterno = $data["apellido_paterno"] ?? "";
        $apellido_materno = $data["apellido_materno"] ?? "";
        $correo = $data["correo"] ?? "";
        $password = $data["password"] ?? "123456";
        $telefono = $data["telefono"] ?? "";
        $sueldo_diario = $data["sueldo_diario"] ?? 0;
        $horario_entrada = $data["horario_entrada"] ?? "08:00:00";
        $horario_salida = $data["horario_salida"] ?? "17:00:00";

        if (!$rol_id || $nombre === "" || $correo === "") {
            throw new Exception("Datos incompletos");
        }

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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar inserción: " . $db->error);
        }

        $stmt->bind_param(
            "issssssdss",
            $rol_id,
            $nombre,
            $apellido_paterno,
            $apellido_materno,
            $correo,
            $password,
            $telefono,
            $sueldo_diario,
            $horario_entrada,
            $horario_salida
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al crear empleado: " . $stmt->error);
        }

        return true;
    }

    public static function actualizar($id, $data)
    {
        $db = self::db();

        $rol_id = $data["rol_id"] ?? null;
        $nombre = $data["nombre"] ?? "";
        $apellido_paterno = $data["apellido_paterno"] ?? "";
        $apellido_materno = $data["apellido_materno"] ?? "";
        $correo = $data["correo"] ?? "";
        $telefono = $data["telefono"] ?? "";
        $sueldo_diario = $data["sueldo_diario"] ?? 0;
        $horario_entrada = $data["horario_entrada"] ?? "08:00:00";
        $horario_salida = $data["horario_salida"] ?? "17:00:00";

        if (!$rol_id || $nombre === "" || $correo === "") {
            throw new Exception("Datos incompletos");
        }

        /*
            Si llega password, se actualiza.
            Si no llega, se conserva el password actual.
        */

        if (isset($data["password"]) && $data["password"] !== "") {
            $password = $data["password"];

            $sql = "UPDATE empleados SET
                        rol_id = ?,
                        nombre = ?,
                        apellido_paterno = ?,
                        apellido_materno = ?,
                        correo = ?,
                        password = ?,
                        telefono = ?,
                        sueldo_diario = ?,
                        horario_entrada = ?,
                        horario_salida = ?
                    WHERE id = ?";

            $stmt = $db->prepare($sql);

            if (!$stmt) {
                throw new Exception("Error al preparar actualización: " . $db->error);
            }

            $stmt->bind_param(
                "issssssdsii",
                $rol_id,
                $nombre,
                $apellido_paterno,
                $apellido_materno,
                $correo,
                $password,
                $telefono,
                $sueldo_diario,
                $horario_entrada,
                $horario_salida,
                $id
            );

        } else {
            $sql = "UPDATE empleados SET
                        rol_id = ?,
                        nombre = ?,
                        apellido_paterno = ?,
                        apellido_materno = ?,
                        correo = ?,
                        telefono = ?,
                        sueldo_diario = ?,
                        horario_entrada = ?,
                        horario_salida = ?
                    WHERE id = ?";

            $stmt = $db->prepare($sql);

            if (!$stmt) {
                throw new Exception("Error al preparar actualización: " . $db->error);
            }

            $stmt->bind_param(
                "isssssdssi",
                $rol_id,
                $nombre,
                $apellido_paterno,
                $apellido_materno,
                $correo,
                $telefono,
                $sueldo_diario,
                $horario_entrada,
                $horario_salida,
                $id
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar empleado: " . $stmt->error);
        }

        return true;
    }

    public static function eliminar($id)
    {
        $db = self::db();

        /*
            Baja lógica.
            No borramos físicamente porque empleados se relaciona con:
            asistencias, nominas y qr_tokens.
        */

        $sql = "UPDATE empleados SET activo = 0 WHERE id = ?";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar eliminación: " . $db->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar empleado: " . $stmt->error);
        }

        return true;
    }

    public static function login($correo)
    {
        $db = self::db();

        $sql = "SELECT 
                    empleados.*,
                    roles.nombre AS rol_nombre
                FROM empleados
                INNER JOIN roles
                ON empleados.rol_id = roles.id
                WHERE empleados.correo = ?
                AND empleados.activo = 1
                LIMIT 1";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar login: " . $db->error);
        }

        $stmt->bind_param("s", $correo);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->fetch_assoc();
    }
}