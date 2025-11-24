<?php
ob_start();
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

include 'db.php';

// Debugging: Log the request
error_log("API request received for user_id: " . ($_GET['user_id'] ?? 'NULL'));

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["error" => "User ID is required"]);
    exit();
}

// Get user preferences
$pref_sql = "SELECT climate, maintenance, pet_safety FROM user_preferences WHERE user_id = ?";
$pref_stmt = $conn->prepare($pref_sql);
$pref_stmt->bind_param("i", $user_id);
$pref_stmt->execute();
$pref_result = $pref_stmt->get_result();

if ($pref_result->num_rows === 0) {
    echo json_encode(["error" => "User preferences not found"]);
    exit();
}

$user_pref = $pref_result->fetch_assoc();
$climate_pref = trim($user_pref['climate']);

// Debugging: Log user preferences
error_log("User preferences - Climate: " . $climate_pref . 
         ", Pet Safety: " . $user_pref['pet_safety'] . 
         ", Maintenance: " . $user_pref['maintenance']);

// Convert comma-separated values to arrays
$toxicity_prefs = array_map('trim', explode(',', $user_pref['pet_safety']));
$maintenance_prefs = array_map('trim', explode(',', $user_pref['maintenance']));
$toxicity_count = count($toxicity_prefs);
$maintenance_count = count($maintenance_prefs);

// Build SQL with explicit ordering
$plants_sql = "
    SELECT 
        p.Plant_Name, 
        p.Category, 
        p.Species,
        p.Toxicity, 
        p.image_url,
        p.Climate,
        p.Maintenance
    FROM plants p
    ORDER BY p.Plant_ID ASC  /* Using primary key for consistent order */
";

// Debugging: Log the SQL query
error_log("Executing query: " . $plants_sql);

$plants_stmt = $conn->prepare($plants_sql);
$plants_stmt->execute();
$plants_result = $plants_stmt->get_result();

// Debugging: Count total plants
$total_plants = $plants_result->num_rows;
error_log("Total plants found: " . $total_plants);

// Match and classify
$recommended = [];
$others = [];
$plant_counter = 0;

while ($row = $plants_result->fetch_assoc()) {
    $plant_counter++;
    
    // Debug first 3 plants
    if ($plant_counter <= 3) {
        error_log("Plant #$plant_counter: " . $row['Plant_Name'] . 
                 " | Climate: " . ($row['Climate'] ?? 'NULL') . 
                 " | Toxicity: " . ($row['Toxicity'] ?? 'NULL') . 
                 " | Maintenance: " . ($row['Maintenance'] ?? 'NULL'));
    }

    $climate_match = !empty($row['Climate']) && strpos($row['Climate'], $climate_pref) !== false;
    $toxicity_match = in_array($row['Toxicity'], $toxicity_prefs);
    $maintenance_match = in_array($row['Maintenance'], $maintenance_prefs);

    // Your exact matching logic preserved
    if ($toxicity_count === 1 && $maintenance_count === 1) {
        if ($climate_match && $toxicity_match && $maintenance_match) {
            $recommended[] = $row;
        } else {
            $others[] = $row;
        }
    } else {
        if ($climate_match && $toxicity_match && $maintenance_match) {
            $recommended[] = $row;
        } else {
            $others[] = $row;
        }
    }
}

// Debugging counts
error_log("Recommended plants count: " . count($recommended));
error_log("Other plants count: " . count($others));

if (!empty($recommended)) {
    error_log("First recommended plant: " . json_encode($recommended[0]));
} else {
    error_log("No recommended plants found");
}

if (!empty($others)) {
    error_log("First other plant: " . json_encode($others[0]));
} else {
    error_log("No other plants found");
}

// Final output
$response = [
    "recommended_plants" => [
        "heading" => "Recommended For You",
        "plants" => $recommended
    ],
    "other_plants" => [
        "heading" => "Other Plants You May Like",
        "plants" => $others
    ],
    "_debug" => [  // Additional debug info
        "total_plants_processed" => $plant_counter,
        "first_recommended_exists" => !empty($recommended),
        "first_other_exists" => !empty($others)
    ]
];

ob_end_clean();
echo json_encode($response, JSON_PRETTY_PRINT);

$plants_stmt->close();
$pref_stmt->close();
$conn->close();
?>