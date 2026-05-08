<?php
header("Content-Type: application/json");
include "../config/db.php";

$result = $conn->query("SELECT * FROM teams");

$teams = [];

while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}

echo json_encode($teams);
?>