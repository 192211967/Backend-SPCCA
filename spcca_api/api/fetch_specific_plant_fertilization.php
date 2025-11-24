<?php
header("Content-Type: application/json");

// Include database connection
require_once "db.php";

// Ensure POST method is used
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Only POST method allowed"]);
    exit;
}

// Check if 'name' is provided in form-data
if (!isset($_POST["name"]) || empty($_POST["name"])) {
    echo json_encode(["success" => false, "message" => "Plant name is required"]);
    exit;
}

$name = $_POST["name"];

// Prepare SQL statement
$sql = "SELECT * FROM water_frequency WHERE name = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "SQL prepare failed"]);
    exit;
}

// Bind parameter (ensure the column matches)
$stmt->bind_param("s", $name);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
} else {
    echo json_encode(["success" => false, "message" => "No records found"]);
}

$stmt->close();
$conn->close();
?>
