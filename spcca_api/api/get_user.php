<?php
header('Content-Type: application/json');
include 'db.php';

try {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception("User ID is required");
    }

    $query = "SELECT user_id, First_Name, Last_Name, Username, Email, Phone_No, Gender, profile_image 
              FROM registration 
              WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $user = $result->fetch_assoc();
    
    // Ensure profile_image is not null
    if ($user['profile_image'] === null) {
        $user['profile_image'] = "";
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>