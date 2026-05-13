<?php

require_once __DIR__ . "/../config/database.php";

class Asistencia {

    // Registrar asistencia por QR
    public static function registrarPorQR(string $codigo_qr): string {

        global $conn;

        // Buscar empleado por QR
        $sql = "SELECT * FROM empleados WHERE codigo_qr = '$codigo_qr' AND activo = 1";
        $resultado = $conn->query($sql);

        if ($resultado->num_rows == 0) {
            return "QR no válido";
        }

        $empleado = $resultado->fetch_assoc();
        $empleado_id = $empleado['id'];

        $fecha_hoy = date("Y-m-d");

        // Verificar si ya registró hoy
        $sql2 = "SELECT * FROM asistencias 
                 WHERE empleado_id = $empleado_id 
                 AND DATE(fecha) = '$fecha_hoy'";

        $res2 = $conn->query($sql2);

        if ($res2->num_rows == 0) {
            // Registrar ENTRADA
            $sql3 = "INSERT INTO asistencias (empleado_id, fecha, tipo)
                     VALUES ($empleado_id, NOW(), 'entrada')";

            $conn->query($sql3);

            return "Entrada registrada";
        } else {
            // Registrar SALIDA
            $sql4 = "INSERT INTO asistencias (empleado_id, fecha, tipo)
                     VALUES ($empleado_id, NOW(), 'salida')";

            $conn->query($sql4);

            return "Salida registrada";
        }
    }

    // Obtener asistencias de un empleado
  public static function obtenerPorEmpleado(int $empleado_id): array {

        global $conn;

        $sql = "SELECT * FROM asistencias 
                WHERE empleado_id = $empleado_id
                ORDER BY fecha DESC";

        $resultado = $conn->query($sql);

        $asistencias = [];

        while($fila = $resultado->fetch_assoc()) {
            $asistencias[] = $fila;
        }

        return $asistencias;
    }

    // Obtener todas las asistencias
    public static function obtenerTodas() {

        global $conn;

        $sql = "SELECT a.*, e.nombre, e.apellido_paterno
                FROM asistencias a
                INNER JOIN empleados e ON a.empleado_id = e.id
                ORDER BY a.fecha DESC";

        $resultado = $conn->query($sql);

        $data = [];

        while($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }

        return $data;
    }
}
?>