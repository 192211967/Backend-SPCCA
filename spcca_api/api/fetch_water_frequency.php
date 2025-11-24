<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection
$conn = new mysqli("localhost", "root", "", "spcca");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

$plant_id = isset($_GET['plant_id']) ? intval($_GET['plant_id']) : 1;

$sql = "SELECT last_watered, next_watering FROM watering WHERE plant_id = $plant_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(["success" => true, "data" => [$data]]);
} else {
    echo json_encode(["success" => 
    false, "message" => "No records found"]);
}

$conn->close();
?>
