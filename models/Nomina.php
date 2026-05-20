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

        $nominas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $nominas[] = $fila;
        }

        return $nominas;
    }

    public function obtener($id)
    {
        $sql = "SELECT * FROM nominas WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function crear($data)
    {
        $empleado_id = $data["empleado_id"];
        $fecha_inicio = $data["fecha_inicio"];
        $fecha_fin = $data["fecha_fin"];
        $dias_trabajados = $data["dias_trabajados"];
        $faltas = $data["faltas"];
        $sueldo_diario = $data["sueldo_diario"];

        $dias_pagados = $dias_trabajados - $faltas;

        if ($dias_pagados < 0) {
            $dias_pagados = 0;
        }

        $total_pago = $dias_pagados * $sueldo_diario;

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

        return $stmt->execute();
    }

    public function actualizar($id, $data)
    {
        $empleado_id = $data["empleado_id"];
        $fecha_inicio = $data["fecha_inicio"];
        $fecha_fin = $data["fecha_fin"];
        $dias_trabajados = $data["dias_trabajados"];
        $faltas = $data["faltas"];
        $sueldo_diario = $data["sueldo_diario"];

        $dias_pagados = $dias_trabajados - $faltas;

        if ($dias_pagados < 0) {
            $dias_pagados = 0;
        }

        $total_pago = $dias_pagados * $sueldo_diario;

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

        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM nominas WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}