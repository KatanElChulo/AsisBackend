<?php
$host = "162.241.62.187";
$db = "diazdel1_asistencias";
$user = "diazdel1_asistenciaProyecto";
$pass = "DFMosyk707vk";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión");
}
?>