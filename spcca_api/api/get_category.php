<?php
header('Content-Type: application/json');
include 'db.php'; // Make sure this sets up $conn

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['id'])) {
    echo json_encode(["error" => "ID required"]);
    exit;
}

$id = $data['id'];

// Prepare and execute the SQL query
$stmt = $conn->prepare("SELECT Plant_Type FROM water_frequency WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode(["category" => $row['Plant_Type']]);
    } else {
        echo json_encode(["error" => "Plant not found"]);
    }
} else {
    echo json_encode(["error" => "Query execution failed"]);
}

$stmt->close();
$conn->close();
?>
