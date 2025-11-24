<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

$response = ["success" => false, "error" => ""];

try {
    // Validate and get user_id from request
    $user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
    
    if ($user_id <= 0) {
        throw new Exception("Valid user_id is required");
    }

    // Fetch fertilization tasks for the given user_id
    $query = "SELECT id, plant_name, image_url, last_fertilized, next_fertilizing, reminder_enabled, fertilizing_days
              FROM fertilize_frequency
              WHERE user_id = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $tasks = [];

    while ($row = $result->fetch_assoc()) {
        // Format dates and handle null values
        $tasks[] = [
            'id' => (int) $row['id'],
            'name' => $row['plant_name'],
            'image_url' => !empty($row['image_url']) ? $row['image_url'] : null,
            'last_fertilized' => ($row['last_fertilized'] === '0000-00-00') ? null : $row['last_fertilized'],
            'next_fertilizing' => ($row['next_fertilizing'] === '0000-00-00') ? null : $row['next_fertilizing'],
            'reminder_enabled' => (bool) $row['reminder_enabled'],
            'frequency_days' => (int) $row['fertilizing_days']
        ];
    }

    $response = [
        "success" => true,
        "tasks" => $tasks,
        "count" => count($tasks),
        "user_id" => $user_id
    ];

} catch (Exception $e) {
    $response["error"] = $e->getMessage();
    http_response_code(400);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}

echo json_encode($response);
exit();
?>
