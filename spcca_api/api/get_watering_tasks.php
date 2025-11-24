<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

$response = ["success" => false, "error" => ""];

try {
    // Get user_id from request (either GET or POST)
    $user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
    
    if ($user_id <= 0) {
        throw new Exception("Valid user_id is required");
    }

    // Prepare and execute query with user filtering
    $query = "SELECT id, plant_name, last_watered, next_watering,Plant_Type, reminder_enabled, image_url 
              FROM water_frequency 
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
        // Format dates if they exist
        if ($row['last_watered'] === '0000-00-00') $row['last_watered'] = null;
        if ($row['next_watering'] === '0000-00-00') $row['next_watering'] = null;
        
        $tasks[] = [
            'id' => $row['id'],
            'name' => $row['plant_name'], // Using plant_name as the name
            'last_watered' => $row['last_watered'],
            'next_watering' => $row['next_watering'],
            'Plant_Type'=>$row['Plant_Type'],
            'reminder_enabled' => (bool)$row['reminder_enabled'],
            'image_url' => !empty($row['image_url']) ? $row['image_url'] : null // Ensure proper handling
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
    http_response_code(400); // Bad request for client errors
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}

echo json_encode($response);
exit();
?>
