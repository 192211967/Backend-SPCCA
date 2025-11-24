<?php
// Include database connection
require_once 'db.php';

// Get and sanitize parameters
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$plant_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$plant_name = isset($_GET['Name']) ? trim($_GET['Name']) : null;

// Validate required parameters
if (!$user_id || (!$plant_id && !$plant_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters: user_id and either id or name']);
    exit;
}

// Build base SQL query
$sql = "SELECT 
            id, user_id, Name, Plant_Name, Plant_Type, Scientific_Name, 
            Place, Last_fertilized, Last_watered, next_fertilizing, 
            next_watering, weather_adjustment, seasonal_adjustment, 
            image_url, overall_score, diagnosis, recommendation, last_health_check, Toxicity, Fertilizer
        FROM my_plants 
        WHERE user_id = ?";

// Add condition for plant ID or plant name
if ($plant_id) {
    $sql .= " AND id = ?";
} else {
    $sql .= " AND Name = ?";
}

$sql .= " LIMIT 1";

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters
if ($plant_id) {
    $stmt->bind_param("ii", $user_id, $plant_id);
} else {
    $stmt->bind_param("is", $user_id, $plant_name);
}

// Execute query
$stmt->execute();
$result = $stmt->get_result();
$plant = $result->fetch_assoc();

// Return result
header('Content-Type: application/json');
if ($plant) {
    echo json_encode([
        'success' => true,
        'data' => $plant
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Plant not found']);
}

$stmt->close();
$conn->close();
?>