<?php
header("Content-Type: application/json");

include 'db.php';  // Ensure your database connection is correct

// Get the POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($_POST['user_id']) || !isset($_POST['reminder_enabled'])) {
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

$user_id = (int)$_POST['user_id'];
$reminder_enabled = (int)$_POST['reminder_enabled'];

// Validate user_id is positive integer
if ($user_id <= 0) {
    echo json_encode(["error" => "Invalid user ID"]);
    exit;
}

// Validate reminder_enabled is 0 or 1
if ($reminder_enabled != 0 && $reminder_enabled != 1) {
    echo json_encode(["error" => "Invalid reminder status value"]);
    exit;
}

// Update only rows for the specific user (directly in water_frequency table)
$sql = "UPDATE water_frequency 
        SET reminder_enabled = ?
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reminder_enabled, $user_id);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo json_encode([
        "success" => true,
        "message" => "Updated $affected_rows records for user $user_id",
        "user_id" => $user_id,
        "reminder_enabled" => $reminder_enabled
    ]);
} else {
    echo json_encode(["error" => "Failed to update reminder status: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>