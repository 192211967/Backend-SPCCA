<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Initialize response
$response = [
    'success' => false, 
    'message' => '',
    'image_path' => ''
];

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'spcca'
];

// Connect to database with error handling
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['name']);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8mb4");
    
    // Get POST data
    $postData = json_decode(file_get_contents("php://input"), true);
    if (empty($postData)) {
        $postData = $_POST; // Fallback to regular POST
    }
    
    // Validate input
    if (!isset($postData['user_id']) || empty($postData['user_id'])) {
        throw new Exception("User ID is required", 400);
    }
    
    if (!isset($postData['image']) || empty($postData['image'])) {
        throw new Exception("Image data is required", 400);
    }
    
    // Sanitize and validate user ID
    $userId = (int)$postData['user_id'];
    if ($userId <= 0) {
        throw new Exception("Invalid User ID", 400);
    }
    
    // Decode base64 image
    $imageData = base64_decode($postData['image']);
    if ($imageData === false) {
        throw new Exception("Invalid image data", 400);
    }
    
    // Create upload directory if it doesn't exist
    $targetDir = "profile_images/";
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            throw new Exception("Failed to create upload directory", 500);
        }
    }
    
    // Generate secure filename
    $fileName = "user_{$userId}_" . bin2hex(random_bytes(8)) . ".jpg";
    $targetFilePath = $targetDir . $fileName;
    
    // Save the image file
    if (!file_put_contents($targetFilePath, $imageData)) {
        throw new Exception("Failed to save image file", 500);
    }
    
    // Verify the image is valid
    if (!getimagesize($targetFilePath)) {
        unlink($targetFilePath); // Clean up
        throw new Exception("Invalid image file", 400);
    }
    
    // Generate full URL for the image
    $imageUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
               $_SERVER['HTTP_HOST'] . 
               dirname($_SERVER['REQUEST_URI']) . '/' . 
               $targetFilePath;
    
    // Update database
    $stmt = $conn->prepare("UPDATE my_plants SET image_url = ? WHERE user_id = ?");
    if (!$stmt) {
        unlink($targetFilePath); // Clean up
        throw new Exception("Database prepare failed: " . $conn->error, 500);
    }
    
    $stmt->bind_param("si", $imageUrl, $userId);
    
    if (!$stmt->execute()) {
        unlink($targetFilePath); // Clean up
        throw new Exception("Database update failed: " . $stmt->error, 500);
    }
    
    $stmt->close();
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Plant image updated successfully',
        'image_path' => $imageUrl
    ];
    
    http_response_code(200);
    
} catch (Exception $e) {
    // Error handling
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($code);
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
} finally {
    // Close connection if exists
    if (isset($conn)) {
        $conn->close();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}