<?php

require_once "../models/Empleado.php";

class AuthController {

    public function login() {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['correo']) || !isset($data['password'])) {
            echo json_encode([
                "success" => false,
                "message" => "Correo y contraseña son obligatorios"
            ]);
            return;
        }

        $correo = $data['correo'];
        $password = $data['password'];

        $empleado = Empleado::login($correo);

        if (!$empleado) {
            echo json_encode([
                "success" => false,
                "message" => "Usuario no encontrado o inactivo"
            ]);
            return;
        }

        if ($password !== $empleado['password']) {
            echo json_encode([
                "success" => false,
                "message" => "Contraseña incorrecta"
            ]);
            return;
        }

        session_start();

        $_SESSION['usuario'] = [
            "id" => $empleado['id'],
            "nombre" => $empleado['nombre'],
            "rol" => $empleado['rol_nombre']
        ];

        echo json_encode([
            "success" => true,
            "message" => "Login correcto",
            "rol" => $empleado['rol_nombre'],
            "usuario" => $_SESSION['usuario']
        ]);
    }
}
?>