<?php
include "../config/db.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
$sql = "SELECT * FROM empleados";
$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header("Content-Type: application/json");
echo json_encode($data);

?>