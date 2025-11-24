<?php
header("Content-Type: application/json");

include 'db.php';  // Include database connection

// Get and validate input
if (!isset($_POST['user_id']) || !isset($_POST['reminder_enabled'])) {
    echo json_encode(["error" => "Both user_id and reminder_enabled are required"]);
    exit;
}

$user_id = (int)$_POST['user_id'];
$reminder_enabled = (int)$_POST['reminder_enabled'];

// Validate inputs
if ($user_id <= 0) {
    echo json_encode(["error" => "Invalid user ID"]);
    exit;
}

if ($reminder_enabled !== 0 && $reminder_enabled !== 1) {
    echo json_encode(["error" => "reminder_enabled must be 0 or 1"]);
    exit;
}

// Update only the current user's fertilizing reminders
$sql = "UPDATE fertilize_frequency 
        SET reminder_enabled = ?
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reminder_enabled, $user_id);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo json_encode([
        "success" => true,
        "message" => "Updated fertilizing reminders for user",
        "user_id" => $user_id,
        "reminder_enabled" => $reminder_enabled,
        "affected_rows" => $affected_rows
    ]);
} else {
    echo json_encode(["error" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>