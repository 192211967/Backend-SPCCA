<?php
header("Content-Type: application/json");

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spcca";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the POST data
$species = $_POST['species']; // Use species as the identifier
$seasonal_adjustment = $_POST['seasonal_adjustment'];

// Prepare the SQL query to update the seasonal adjustment
$sql = "UPDATE watering_frequency SET seasonal_adjustment = ? WHERE species = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind parameters
    $stmt->bind_param("ss", $seasonal_adjustment, $species);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Failed to prepare the SQL statement."]);
}

// Close the connection
$conn->close();
?>