<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

$response = ["success" => false, "error" => "", "debug" => []];

try {
    // Get input data
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        $plant_name = isset($input['plant_name']) ? trim($input['plant_name']) : '';
        $weather_adjustment = isset($input['weather_adjustment']) ? trim($input['weather_adjustment']) : null;
        $seasonal_adjustment = isset($input['seasonal_adjustment']) ? trim($input['seasonal_adjustment']) : null;
    } else {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $plant_name = isset($_POST['plant_name']) ? trim($_POST['plant_name']) : '';
        $weather_adjustment = isset($_POST['weather_adjustment']) ? trim($_POST['weather_adjustment']) : null;
        $seasonal_adjustment = isset($_POST['seasonal_adjustment']) ? trim($_POST['seasonal_adjustment']) : null;
    }

    // Validation
    if ($user_id <= 0) {
        throw new Exception("Valid user_id is required");
    }
    
    if (empty($plant_name)) {
        throw new Exception("Plant name is required");
    }

    // Check if plant exists first
    $check_query = "SELECT id FROM water_frequency WHERE user_id = ? AND plant_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $user_id, $plant_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception("No matching record found for user_id: $user_id and plant: $plant_name");
    }

    // Get current values if adjustments are null
    if ($weather_adjustment === null || $seasonal_adjustment === null) {
        $current_query = "SELECT weather_adjustment, seasonal_adjustment FROM water_frequency WHERE user_id = ? AND plant_name = ?";
        $current_stmt = $conn->prepare($current_query);
        $current_stmt->bind_param("is", $user_id, $plant_name);
        $current_stmt->execute();
        $current_result = $current_stmt->get_result();
        $current_data = $current_result->fetch_assoc();
        
        if ($weather_adjustment === null) {
            $weather_adjustment = $current_data['weather_adjustment'] ?? '';
        }
        if ($seasonal_adjustment === null) {
            $seasonal_adjustment = $current_data['seasonal_adjustment'] ?? '';
        }
        
        $current_stmt->close();
    }

    // Update query
    $query = "UPDATE water_frequency 
              SET weather_adjustment = ?, seasonal_adjustment = ?
              WHERE user_id = ? AND plant_name = ?";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssis", $weather_adjustment, $seasonal_adjustment, $user_id, $plant_name);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $response = [
        "success" => true,
        "message" => "Adjustments updated successfully",
        "data" => [
            "user_id" => $user_id,
            "plant_name" => $plant_name,
            "weather_adjustment" => $weather_adjustment,
            "seasonal_adjustment" => $seasonal_adjustment
        ]
    ];

} catch (Exception $e) {
    $response["error"] = $e->getMessage();
    $response["debug"] = [
        "user_id" => $user_id ?? null,
        "plant_name" => $plant_name ?? null,
        "weather_adjustment" => $weather_adjustment ?? null,
        "seasonal_adjustment" => $seasonal_adjustment ?? null
    ];
    http_response_code(400);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}

echo json_encode($response);
exit();
?>