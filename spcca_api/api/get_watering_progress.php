<?php
include 'db.php';
$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
        throw new Exception('Missing user_id parameter.');
    }

    $user_id = intval($_GET['user_id']); // Ensure user_id is an integer

    // Get watering data for the past week for the specific user
    $sql = "SELECT 
                plant_id, 
                plant_name,
                name,
                image_url,
                watering_days,
                GROUP_CONCAT(DATE(last_watered) ORDER BY last_watered DESC) as last_watereds
            FROM water_history
            WHERE user_id = ? AND last_watered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY plant_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $wateringDates = $row['last_watereds'] ? explode(',', $row['last_watereds']) : [];
            $wateringDays = (int)$row['watering_days'];
            
            // Analyze watering consistency
            $analysis = analyzeWateringConsistency($wateringDates, $wateringDays);
            
            $response['data'][] = array_merge($row, $analysis);
        }
        $response['success'] = true;
    } else {
        $response['message'] = 'No watering history found for this user.';
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);

function analyzeWateringConsistency($wateringDates, $wateringInterval) {
    $today = new DateTime();
    $status = '';
    $progress = 0;
    $consecutiveMissed = 0;
    $maxConsecutiveMissed = 0;
    $expectedCount = 0;
    $sequentialMissPenalty = 0;
    
    // Convert watering dates to DateTime objects for easier comparison
    $wateringDateObjects = array_map(fn($date) => new DateTime($date), $wateringDates);
    
    // Check sequential watering pattern
    for ($i = 0; $i < 7; $i++) {
        $checkDate = (clone $today)->modify("-$i days")->format('Y-m-d');
        
        if ($i % $wateringInterval === 0) {
            $expectedCount++;
            if (in_array($checkDate, $wateringDates)) {
                $consecutiveMissed = 0;
            } else {
                $consecutiveMissed++;
                $maxConsecutiveMissed = max($maxConsecutiveMissed, $consecutiveMissed);
            }
        }
    }
    
    $wateredCount = count($wateringDates);
    $missedCount = $expectedCount - $wateredCount;
    
    // Check for sequential missed waterings (1-2 days apart from expected)
    if (count($wateringDateObjects) > 1) {
        sort($wateringDateObjects);
        for ($i = 1; $i < count($wateringDateObjects); $i++) {
            $diff = $wateringDateObjects[$i]->diff($wateringDateObjects[$i-1])->days;
            if ($diff > $wateringInterval && $diff <= $wateringInterval + 2) {
                // Apply penalty for sequential misses (1-2 days late)
                $sequentialMissPenalty += 0.1; // 10% penalty per sequential miss
            }
        }
    }
    
    
    // Calculate progress percentage with penalty
    $progress = $expectedCount > 0 ? ($wateredCount / $expectedCount) * 100 : 0;
    $progress = max(0, $progress - ($sequentialMissPenalty * 100));
    
    return [
        'last_watereds' => $wateringDates,
        'expected_waterings' => $expectedCount,
        'consecutive_missed' => $maxConsecutiveMissed,
        'watered_count' => $wateredCount,
        'watering_progress' => round($progress),
        'watering_status' => $status,
        'sequential_miss_penalty' => $sequentialMissPenalty
    ];
}
?>
