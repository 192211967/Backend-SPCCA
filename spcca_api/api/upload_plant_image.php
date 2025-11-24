<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spcca";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        "success" => false, 
        "message" => "Database Connection Failed: " . $conn->connect_error
    ]));
}

// Check if required fields are present
if (!isset($_POST['user_id']) || !isset($_POST['image'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Missing required fields"
    ]);
    exit();
}

$userId = $_POST['user_id'];
$base64Image = $_POST['image'];

// Decode the Base64 image
$imageData = base64_decode($base64Image);
if ($imageData === false) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid image data"
    ]);
    exit();
}

// Create uploads directory if it doesn't exist
$uploadDir = "profile_images/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$filename = "plant_" . $userId . "_" . time() . ".jpg";
$filePath = $uploadDir . $filename;

// Save the image to server
if (file_put_contents($filePath, $imageData) === false) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Failed to save image"
    ]);
    exit();
}

// Construct the URL to access the image
$imageUrl = "http://" . $_SERVER['HTTP_HOST'] . "/spcca_api/" . $filePath;

// Return success response with image URL
echo json_encode([
    "success" => true,
    "message" => "Image uploaded successfully",
    "image_url" => $imageUrl
]);
?>