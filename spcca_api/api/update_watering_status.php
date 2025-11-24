<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';

$response = ["success" => false, "error" => ""];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests allowed", 405);
    }

    // Get input
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $plant_name = trim($input['name'] ?? '');
    $user_id = (int)($input['user_id'] ?? 0);

    // Validate
    if (empty($plant_name)) throw new Exception("Plant name required", 400);
    if ($user_id <= 0) throw new Exception("Valid user ID required", 400);

    // 1. Get species from water_frequency
    $stmt = $conn->prepare("SELECT spicies FROM water_frequency WHERE plant_name = ? AND user_id = ?");
    $stmt->bind_param("si", $plant_name, $user_id);
    $stmt->execute();
    $wf_data = $stmt->get_result()->fetch_assoc();
    if (!$wf_data) throw new Exception("Plant not found for user", 404);
    
    $species = trim($wf_data['spicies']);
    if (empty($species)) throw new Exception("Species name missing in water_frequency", 400);

    // 2. Get watering info from plants table using ONLY species match
    $stmt = $conn->prepare("SELECT Watering FROM plants WHERE species = ?");
    $stmt->bind_param("s", $species);
    $stmt->execute();
    $plant_data = $stmt->get_result()->fetch_assoc();
    if (!$plant_data) throw new Exception("Species '$species' not found in plants table", 404);

    // 3. Calculate watering days (strict matching only)
    $watering_text = strtolower(trim($plant_data['Watering']));
    $days_map = [
        'daily' => 1,
        'every 2 days' => 2,
        'every 2-3 days' => 3,
        'every 3-4 days' => 4,
        'every 4-5 days' => 5,
        'every 5-7 days' => 7,
        'weekly' => 7,
        'once a week' => 7,
        'twice a week' => 3,
        'every 10-14 days' => 12
    ];
    
    $watering_days = null;
    foreach ($days_map as $pattern => $days) {
        if (strpos($watering_text, $pattern) !== false) {
            $watering_days = $days;
            break;
        }
    }
    
    if ($watering_days === null) {
        throw new Exception("Unrecognized watering pattern: " . $plant_data['Watering'], 400);
    }

    // 4. Update water_frequency
    $stmt = $conn->prepare("UPDATE water_frequency SET 
                          last_watered = NOW(),
                          next_watering = DATE_ADD(NOW(), INTERVAL ? DAY),
                          watering_days = ?
                          WHERE plant_name = ? AND user_id = ?");
    $stmt->bind_param("iisi", $watering_days, $watering_days, $plant_name, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update watering schedule", 500);
    }

    $response = [
        "success" => true,
        "species_match" => $species,
        "watering_days" => $watering_days,
        "next_watering" => date('Y-m-d', strtotime("+$watering_days days"))
    ];

} catch (Exception $e) {
    $response["error"] = $e->getMessage();
    http_response_code($e->getCode() ?: 400);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}

echo json_encode($response);
?>