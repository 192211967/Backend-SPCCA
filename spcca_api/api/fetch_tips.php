<?php
include 'db.php';

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get input from Android
$plant_type = $_GET['plant_type'];
$symptom = $_GET['symptom'];
$temperature = $_GET['temperature'];

// Fetch data
$sql = "SELECT eco_friendly_tip FROM plant_tips 
        WHERE plant_type = ? AND symptom = ? AND temperature = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $plant_type, $symptom, $temperature);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["tip" => $row['eco_friendly_tip']]);
} else {
    echo json_encode(["tip" => "No matching eco-friendly tip found."]);
}

$conn->close();
?>
