<?php

require_once __DIR__ . "/../config/database.php";

class Asistencia {

    // Registrar asistencia por QR
    public static function registrarPorQR(string $codigo_qr): string {

        global $conn;

        // Buscar empleado por QR (SEGURIDAD: prepared statement)
        $stmt = $conn->prepare("
            SELECT id 
            FROM empleados 
            WHERE codigo_qr = ? AND activo = 1
        ");

        $stmt->bind_param("s", $codigo_qr);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows == 0) {
            return "QR no válido";
        }

        $empleado = $resultado->fetch_assoc();
        $empleado_id = $empleado['id'];

        $fecha_hoy = date("Y-m-d");

        // Verificar si ya tiene registro hoy
        $stmt2 = $conn->prepare("
            SELECT id 
            FROM asistencias 
            WHERE empleado_id = ? 
            AND DATE(fecha) = ?
        ");

        $stmt2->bind_param("is", $empleado_id, $fecha_hoy);
        $stmt2->execute();

        $res2 = $stmt2->get_result();

        if ($res2->num_rows == 0) {

            // ENTRADA
            $stmt3 = $conn->prepare("
                INSERT INTO asistencias (empleado_id, fecha, tipo)
                VALUES (?, NOW(), 'entrada')
            ");

            $stmt3->bind_param("i", $empleado_id);
            $stmt3->execute();

            return "Entrada registrada";

        } else {

            // SALIDA
            $stmt4 = $conn->prepare("
                INSERT INTO asistencias (empleado_id, fecha, tipo)
                VALUES (?, NOW(), 'salida')
            ");

            $stmt4->bind_param("i", $empleado_id);
            $stmt4->execute();

            return "Salida registrada";
        }
    }

    // Obtener asistencias por empleado
    public static function obtenerPorEmpleado(int $empleado_id): array {

        global $conn;

        $stmt = $conn->prepare("
            SELECT * 
            FROM asistencias 
            WHERE empleado_id = ?
            ORDER BY fecha DESC
        ");

        $stmt->bind_param("i", $empleado_id);
        $stmt->execute();

        $resultado = $stmt->get_result();

        $asistencias = [];

        while ($fila = $resultado->fetch_assoc()) {
            $asistencias[] = $fila;
        }

        return $asistencias;
    }

    // Obtener todas las asistencias
    public static function obtenerTodas(): array {

        global $conn;

        $sql = "
            SELECT a.*, e.nombre, e.apellido_paterno
            FROM asistencias a
            INNER JOIN empleados e ON a.empleado_id = e.id
            ORDER BY a.fecha DESC
        ";

        $resultado = $conn->query($sql);

        $data = [];

        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }

        return $data;
    }
}
?>