<?php
// Include database connection
include 'db.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get category from request (default: 'Herb')
$category = $_GET['category'] ?? 'Herb';

// Prepare and execute query
$sql = "SELECT plant_id, Plant_Name, Species, Category, Watering, Sunlight, Temperature, Humidity, Fertilizing, Toxicity 
        FROM plants 
        WHERE Category = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "SQL Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

$plants = $result->fetch_all(MYSQLI_ASSOC);

// Return JSON response
echo json_encode($plants ?: ["error" => "No plants found for category: $category"], JSON_PRETTY_PRINT);
?>
