<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$host = "localhost";
$username = "root";
$password = "";  // Ensure this is correct
$database = "spcca";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Connection failed: " . $conn->connect_error
    ]));
}

// Get current month boundaries
$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth = date('Y-m-t');
$today = date('Y-m-d');

// Query to get fertilizing progress data using only the fertilize_history table
$sql = "SELECT 
            p.plant_id,
            p.plant_name,
            p.name,
            p.spicies,
            p.last_fertilized,
            p.next_fertilizing,
            p.image_url,
            -- Calculate if fertilizing is due today or overdue
            CASE 
                WHEN p.next_fertilizing IS NULL THEN 0
                WHEN p.next_fertilizing <= '$today' THEN 1
                ELSE 0
            END AS is_fert_due,
            -- Calculate days until next fertilizing (negative if overdue)
            DATEDIFF(p.next_fertilizing, '$today') AS days_until_next_fert,
            -- Calculate days since last fertilizing
            DATEDIFF('$today', p.last_fertilized) AS days_since_last_fert,
            -- Estimate fertilizing count this month based on last_fertilized date
            CASE 
                WHEN p.last_fertilized IS NULL THEN 0
                WHEN p.last_fertilized BETWEEN '$firstDayOfMonth' AND '$lastDayOfMonth' THEN 1
                ELSE 0
            END AS fert_count_this_month
        FROM fertilize_history p";

$result = $conn->query($sql);

if (!$result) {
    die(json_encode([
        "success" => false,
        "message" => "Error executing query: " . $conn->error
    ]));
}

$plants = [];
while ($row = $result->fetch_assoc()) {
    $fertCountThisMonth = (int)$row['fert_count_this_month'];
    $isFertDue = (bool)$row['is_fert_due'];
    $daysUntilNextFert = (int)$row['days_until_next_fert'];
    $daysSinceLastFert = (int)$row['days_since_last_fert'];
    
    // Default monthly fertilizing target (2 times per month)
    $expectedFerts = 2;
    
    // Calculate progress percentage
    $fertProgress = ($fertCountThisMonth / $expectedFerts) * 100;
    
    // Determine status
    $status = "Healthy";
    if ($isFertDue && $daysUntilNextFert < -3) {
        $status = "Overdue";
    } elseif ($isFertDue) {
        $status = "Due Now";
    } elseif ($fertCountThisMonth == 0 && date('j') >= date('t')) { // Check after full 30 days
        $status = "Not Started";
    } elseif ($fertCountThisMonth < $expectedFerts && $daysSinceLastFert > 20) {
        $status = "Needs Attention";
    }

    $plants[] = [
        "plant_id" => (int)$row['plant_id'],
        "plant_name" => $row['plant_name'],
        "name" => $row['name'],
        "spicies" => $row['spicies'],
        "last_fertilized" => $row['last_fertilized'],
        "next_fertilizing" => $row['next_fertilizing'],
        "image_url" => $row['image_url'],
        "fert_count" => $fertCountThisMonth,
        "fert_progress" => round($fertProgress, 2),
        "fert_status" => $status,
        "expected_ferts" => $expectedFerts,
        "days_since_last_fert" => $daysSinceLastFert,
        "days_until_next_fert" => $daysUntilNextFert,
        "is_fert_due" => $isFertDue
    ];
}

// Close connection
$conn->close();

// Return the response
echo json_encode([
    "success" => true,
    "data" => $plants
]);
?>
