<?php

class Nomina
{
    private $db;

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    public function listar()
    {
        $sql = "SELECT
                    n.id,
                    n.empleado_id,
                    e.nombre,
                    e.apellido_paterno,
                    e.apellido_materno,
                    n.fecha_inicio,
                    n.fecha_fin,
                    n.dias_trabajados,
                    n.faltas,
                    n.sueldo_diario,
                    n.total_pago,
                    n.fecha_generacion
                FROM nominas n
                INNER JOIN empleados e
                ON n.empleado_id = e.id
                ORDER BY n.id DESC";

        $resultado = $this->db->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar nóminas: " . $this->db->error);
        }

        $nominas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $nominas[] = $fila;
        }

        return $nominas;
    }

    public function obtener($id)
    {
        $sql = "SELECT * FROM nominas WHERE id = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $this->db->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function crear($data)
    {
        $empleado_id = $data["empleado_id"] ?? null;
        $fecha_inicio = $data["fecha_inicio"] ?? null;
        $fecha_fin = $data["fecha_fin"] ?? null;
        $dias_trabajados = $data["dias_trabajados"] ?? 0;
        $faltas = $data["faltas"] ?? 0;
        $sueldo_diario = $data["sueldo_diario"] ?? 0;

        if (!$empleado_id || !$fecha_inicio || !$fecha_fin) {
            throw new Exception("Datos incompletos");
        }

        /*
            Como dias_trabajados ya representa los días reales trabajados,
            el total debe ser:
            dias_trabajados * sueldo_diario
        */
        $total_pago = $dias_trabajados * $sueldo_diario;

        $sql = "INSERT INTO nominas
                (
                    empleado_id,
                    fecha_inicio,
                    fecha_fin,
                    dias_trabajados,
                    faltas,
                    sueldo_diario,
                    total_pago
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar inserción: " . $this->db->error);
        }

        $stmt->bind_param(
            "issiidd",
            $empleado_id,
            $fecha_inicio,
            $fecha_fin,
            $dias_trabajados,
            $faltas,
            $sueldo_diario,
            $total_pago
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al crear nómina: " . $stmt->error);
        }

        return true;
    }

    public function actualizar($id, $data)
    {
        $empleado_id = $data["empleado_id"] ?? null;
        $fecha_inicio = $data["fecha_inicio"] ?? null;
        $fecha_fin = $data["fecha_fin"] ?? null;
        $dias_trabajados = $data["dias_trabajados"] ?? 0;
        $faltas = $data["faltas"] ?? 0;
        $sueldo_diario = $data["sueldo_diario"] ?? 0;

        if (!$empleado_id || !$fecha_inicio || !$fecha_fin) {
            throw new Exception("Datos incompletos");
        }

        $total_pago = $dias_trabajados * $sueldo_diario;

        $sql = "UPDATE nominas SET
                    empleado_id = ?,
                    fecha_inicio = ?,
                    fecha_fin = ?,
                    dias_trabajados = ?,
                    faltas = ?,
                    sueldo_diario = ?,
                    total_pago = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar actualización: " . $this->db->error);
        }

        $stmt->bind_param(
            "issiiddi",
            $empleado_id,
            $fecha_inicio,
            $fecha_fin,
            $dias_trabajados,
            $faltas,
            $sueldo_diario,
            $total_pago,
            $id
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar nómina: " . $stmt->error);
        }

        return true;
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM nominas WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar eliminación: " . $this->db->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar nómina: " . $stmt->error);
        }

        return true;
    }
}