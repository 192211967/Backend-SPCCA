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

    // 1. Get species from water_frequency table (assuming same structure)
    $stmt = $conn->prepare("SELECT spicies FROM water_frequency WHERE plant_name = ? AND user_id = ?");
    $stmt->bind_param("si", $plant_name, $user_id);
    $stmt->execute();
    $wf_data = $stmt->get_result()->fetch_assoc();
    if (!$wf_data) throw new Exception("Plant not found for user", 404);
    
    $species = trim($wf_data['spicies']);
    if (empty($species)) throw new Exception("Species name missing", 400);

    // 2. Get fertilizing info from plants table using species match
    $stmt = $conn->prepare("SELECT Fertilizing FROM plants WHERE species = ?");
    $stmt->bind_param("s", $species);
    $stmt->execute();
    $plant_data = $stmt->get_result()->fetch_assoc();
    if (!$plant_data) throw new Exception("Species '$species' not found in plants table", 404);

    // 3. Calculate fertilizing days
    $fertilizing_text = strtolower(trim($plant_data['Fertilizing'] ?? ''));
    $days_map = [
        'monthly' => 30,
        'every month' => 30,
        'bi-weekly' => 14,
        'every 2 weeks' => 14,
        'bi-monthly' => 60,
        'every 2 months' => 60,
        'seasonal' => 90,
        'every season' => 90,
        'quarterly' => 90,
        'yearly' => 365,
        'annually' => 365
    ];
    
    $fertilizing_days = 30; // Default to monthly if not specified
    foreach ($days_map as $pattern => $days) {
        if (strpos($fertilizing_text, $pattern) !== false) {
            $fertilizing_days = $days;
            break;
        }
    }

    // 4. Update or create fertilize_frequency record
    // Check if record exists
    $stmt = $conn->prepare("SELECT id FROM fertilize_frequency WHERE plant_name = ? AND user_id = ?");
    $stmt->bind_param("si", $plant_name, $user_id);
    $stmt->execute();
    $fertilize_data = $stmt->get_result()->fetch_assoc();
    
    if ($fertilize_data) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE fertilize_frequency SET 
                              last_fertilized = NOW(),
                              next_fertilizing = DATE_ADD(NOW(), INTERVAL ? DAY),
                              fertilizing_days = ?
                              WHERE plant_name = ? AND user_id = ?");
        $stmt->bind_param("iisi", $fertilizing_days, $fertilizing_days, $plant_name, $user_id);
    } else {
        // Create new record
        $stmt = $conn->prepare("INSERT INTO fertilize_frequency 
                              (plant_name, user_id, last_fertilized, next_fertilizing, fertilizing_days)
                              VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), ?)");
        $stmt->bind_param("siii", $plant_name, $user_id, $fertilizing_days, $fertilizing_days);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update fertilizing schedule", 500);
    }

    $response = [
        "success" => true,
        "species_match" => $species,
        "fertilizing_pattern" => $fertilizing_text,
        "fertilizing_days" => $fertilizing_days,
        "last_fertilized" => date('Y-m-d H:i:s'),
        "next_fertilizing" => date('Y-m-d', strtotime("+$fertilizing_days days"))
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