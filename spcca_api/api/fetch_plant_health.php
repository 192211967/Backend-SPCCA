<?php
header("Content-Type: application/json");

// Include database connection
require_once "db.php";

// Check if a specific plant name is requested
$filter = "";
if (isset($_GET['plant_name'])) {
    $plant_name = $conn->real_escape_string($_GET['plant_name']);
    $filter = " WHERE plant_name = '$plant_name'";
}

// Fetch data from plant_health table with optional filtering
$sql = "SELECT * FROM plant_health" . $filter;
$result = $conn->query($sql);

$plant_health_records = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plant_health_records[] = $row;
    }
}

// Close connection
$conn->close();

// Return JSON response
echo json_encode($plant_health_records, JSON_PRETTY_PRINT);
?>
