<?php
header("Content-Type: application/json");
error_reporting(0);

require_once 'db.php';

try {
    if (empty($_POST['user_id']) || empty($_POST['id']) || !isset($_POST['is_favorite'])) {
        throw new Exception("Missing required fields");
    }

    $userId = $_POST['user_id'];
    $plantId = $_POST['id'];
    $isFavorite = filter_var($_POST['is_favorite'], FILTER_VALIDATE_BOOLEAN);
    $isFavoriteValue = $isFavorite ? 1 : 0;

    // Check if the plant exists
    $checkStmt = $conn->prepare("SELECT id FROM my_plants WHERE user_id = ? AND id = ?");
    $checkStmt->bind_param("ii", $userId, $plantId);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        throw new Exception("No matching plant found for the given user_id and id.");
    }
    $checkStmt->close();

    // Perform the update
    $stmt = $conn->prepare("UPDATE my_plants SET is_favorite = ? WHERE user_id = ? AND id = ?");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iii", $isFavoriteValue, $userId, $plantId);
    $stmt->execute();

    echo json_encode(["success" => true]);
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
