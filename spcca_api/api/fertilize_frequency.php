<?php
header("Content-Type: application/json");

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["success" => false, "message" => "Invalid request method. Only GET is allowed."]);
    exit();
}

// Include database connection
require_once "db.php";

if (!$conn) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Fetch all fertilize frequency records
$sql = "SELECT * FROM fertilize_frequency";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
} else {
    echo json_encode(["success" => false, "message" => "No records found"]);
}

$conn->close();
?>
