<?php
header("Content-Type: application/json");
require_once 'db.php';

try {
    if (empty($_GET['user_id'])) {
        throw new Exception("User ID is required");
    }

    $userId = intval($_GET['user_id']); // Ensure it's an integer

    $stmt = $conn->prepare("SELECT Name FROM my_plants WHERE user_id = ? AND is_favorite = 1");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $plantNames = array();
    while ($row = $result->fetch_assoc()) {
        $plantNames[] = $row['Name'];
    }

    echo json_encode([
        "success" => true,
        "plant_names" => $plantNames
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>
