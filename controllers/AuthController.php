<?php

require_once __DIR__ . "/../models/Empleado.php";

class AuthController
{
    public function login()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (!$data) {
                echo json_encode([
                    "success" => false,
                    "message" => "No se recibieron datos"
                ]);
                return;
            }

            $correo = trim($data["correo"] ?? "");
            $password = trim($data["password"] ?? "");

            if ($correo === "" || $password === "") {
                echo json_encode([
                    "success" => false,
                    "message" => "Correo y contraseña son obligatorios"
                ]);
                return;
            }

            $empleado = Empleado::login($correo);

            if (!$empleado) {
                echo json_encode([
                    "success" => false,
                    "message" => "Usuario no encontrado o inactivo"
                ]);
                return;
            }

            if ($password !== $empleado["password"]) {
                echo json_encode([
                    "success" => false,
                    "message" => "Contraseña incorrecta"
                ]);
                return;
            }

            $rol = $empleado["rol_nombre"] ?? "EMPLEADO";

            session_start();

            $_SESSION["usuario"] = [
                "id" => $empleado["id"],
                "nombre" => $empleado["nombre"],
                "rol" => $rol
            ];

            echo json_encode([
                "success" => true,
                "message" => "Login correcto",
                "rol" => $rol,
                "usuario" => $_SESSION["usuario"]
            ]);

        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Error interno en login",
                "error" => $e->getMessage()
            ]);
        }
    }
}

?>