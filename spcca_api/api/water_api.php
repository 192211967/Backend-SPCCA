<?php
header("Content-Type: application/json");

// Database connection
$servername = "localhost"; // Change to your server
$username = "your_username";
$password = "your_password";
$dbname = "spcca"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

// Fetch watering data
$query = "SELECT id, plant_name, Plant_Type, watering_days, last_watered FROM water_frequency";
$result = $conn->query($query);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
