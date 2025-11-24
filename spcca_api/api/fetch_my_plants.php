<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include 'db.php';

$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(["error" => "User ID required"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM my_plants WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $plants = array();
    while($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }

    echo json_encode($plants);
} catch (Exception $e) {
    echo json_encode(["error" => "Database error"]);
}

$stmt->close();
$conn->close();
?>