<?php
include 'db.php';
// Get POST data
$user_id = $_POST['user_id'];
$climate = $_POST['climate'];
$pet_safety = $_POST['pet_safety'];
$maintenance = $_POST['maintenance'];

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO user_preferences (user_id, climate, pet_safety, maintenance) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                        climate = VALUES(climate), 
                        pet_safety = VALUES(pet_safety), 
                        maintenance = VALUES(maintenance)");
$stmt->bind_param("ssss", $user_id, $climate, $pet_safety, $maintenance);

// Execute the query
if ($stmt->execute()) {
    echo "success";
} else {
    echo "error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>