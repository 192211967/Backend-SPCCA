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

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid Request Method"]);
    exit();
}

// Get JSON input (for both raw JSON and form-data)
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// If no JSON data, use $_POST
if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
    $data = $_POST;
}

// Check if all required fields are present
$required_fields = [
    "user_id", 
    "Name", 
    "Plant_Name", 
    "Plant_Type", 
    "Scientific_Name", 
    "Place", 
    "Last_watered", 
    "Last_fertilized",
    "Fertilizer"
];

foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "message" => "Missing required field: $field"
        ]);
        exit();
    }
    
    // Trim and check if empty
    if (empty(trim($data[$field]))) {
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "message" => "Empty value for required field: $field"
        ]);
        exit();
    }
}

// Handle image (optional field)
$image_url = "";
if (isset($data['image_base64']) && !empty(trim($data['image_base64']))) {
    // Remove data URI prefix if present
    $base64_string = $data['image_base64'];
    if (strpos($base64_string, 'data:image') === 0) {
        $base64_string = preg_replace('#^data:image/\w+;base64,#i', '', $base64_string);
    }
    
    $imageData = base64_decode($base64_string);
    if ($imageData === false) {
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "message" => "Invalid image data"
        ]);
        exit();
    }
    
    // Generate unique filename
    $filename = uniqid() . '.jpg';
    $upload_dir = __DIR__ . '/plant_images/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            http_response_code(500);
            echo json_encode([
                "success" => false, 
                "message" => "Failed to create upload directory"
            ]);
            exit();
        }
    }
    
    $file_path = $upload_dir . $filename;
    if (!file_put_contents($file_path, $imageData)) {
        http_response_code(500);
        echo json_encode([
            "success" => false, 
            "message" => "Failed to save image"
        ]);
        exit();
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    $image_url = $protocol . $host . rtrim($base_path, '/') . '/plant_images/' . $filename;
} elseif (isset($data['original_image_url']) && !empty(trim($data['original_image_url']))) {
    $image_url = trim($data['original_image_url']);
}

// Get Toxicity (optional field)
$toxicity = isset($data['Toxicity']) ? trim($data['Toxicity']) : '';

// Prepare and bind parameters using prepared statements
$stmt = $conn->prepare("INSERT INTO my_plants (
    user_id,
    Name, 
    Plant_Name, 
    Plant_Type, 
    Scientific_Name, 
    Place, 
    Last_watered, 
    Last_fertilized, 
    Fertilizer,
    Toxicity,
    image_url
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit();
}

// Sanitize and bind parameters
$user_id = trim($data['user_id']);
$name = trim($data['Name']);
$plant_name = trim($data['Plant_Name']);
$plant_type = trim($data['Plant_Type']);
$scientific_name = trim($data['Scientific_Name']);
$place = trim($data['Place']);
$last_watered = trim($data['Last_watered']);
$last_fertilized = trim($data['Last_fertilized']);
$fertilizer = trim($data['Fertilizer']);

$stmt->bind_param("sssssssssss",
    $user_id,
    $name,
    $plant_name,
    $plant_type,
    $scientific_name,
    $place,
    $last_watered,
    $last_fertilized,
    $fertilizer,
    $toxicity,
    $image_url
);

// Execute the statement
if ($stmt->execute()) {
    echo json_encode([
        "success" => true, 
        "message" => "Plant added successfully",
        "plant_id" => $stmt->insert_id,
        "image_url" => $image_url
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error inserting plant: " . $stmt->error
    ]);
}

// Close connections
$stmt->close();
$conn->close();
?>