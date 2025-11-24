<?php
header("Content-Type: application/json");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "spcca";

// Establish database connection
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $conn->connect_error
    ]);
    exit;
}

// Get and validate POST data
$json = file_get_contents('php://input');
$input = json_decode($json, true);

if ($input === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// Required fields validation
$requiredFields = [
    'plant_id', 'user_id', 'check_date', 
    'overall_score', 'diagnosis', 'recommendation'
];

foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Missing required field: $field"
        ]);
        exit;
    }
}

try {
    // Strict integer validation for IDs
    $plant_id = filter_var($input['plant_id'], FILTER_VALIDATE_INT);
    $user_id = filter_var($input['user_id'], FILTER_VALIDATE_INT);
    
    if ($plant_id === false || $user_id === false || $plant_id <= 0 || $user_id <= 0) {
        throw new Exception("Plant ID and User ID must be positive integers");
    }

    // Validate and prepare other fields
    $check_date = $conn->real_escape_string($input['check_date']);
    
    $overall_score = filter_var($input['overall_score'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'max_range' => 100]
    ]);
    if ($overall_score === false) {
        throw new Exception("Overall score must be between 0 and 100");
    }
    
    $diagnosis = $conn->real_escape_string($input['diagnosis']);
    $recommendation = $conn->real_escape_string($input['recommendation']);

    // Check if record exists
    $checkQuery = $conn->prepare("SELECT id FROM plant_health_results WHERE plant_id = ? AND user_id = ?");
    $checkQuery->bind_param("ii", $plant_id, $user_id);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();
    $existingRecord = $checkResult->num_rows > 0;
    $checkQuery->close();

    if ($existingRecord) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE plant_health_results SET 
                              check_date = ?,
                              overall_score = ?,
                              diagnosis = ?,
                              recommendation = ?
                              WHERE plant_id = ? AND user_id = ?");
        
        $stmt->bind_param("sissii", 
            $check_date,
            $overall_score,
            $diagnosis,
            $recommendation,
            $plant_id,
            $user_id
        );
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO plant_health_results (
                              plant_id,
                              user_id,
                              check_date,
                              overall_score,
                              diagnosis,
                              recommendation
                              ) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("iisiss", 
            $plant_id,
            $user_id,
            $check_date,
            $overall_score,
            $diagnosis,
            $recommendation
        );
    }

    // Execute the statement
    $success = $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if (!$success) {
        throw new Exception("Database operation failed: " . $conn->error);
    }

    // Prepare response
    $response = [
        'success' => true,
        'message' => $existingRecord ? 'Plant health updated' : 'New record created',
        'action' => $existingRecord ? 'update' : 'insert',
        'plant_id' => $plant_id,
        'user_id' => $user_id,
        'affected_rows' => $affectedRows
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>