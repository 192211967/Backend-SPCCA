<?php
header("Content-Type: application/json; charset=UTF-8");

// Database credentials
include 'db.php';

// Fetch data from the Shrubs table, including Toxicity
$sql = "SELECT image_url, Plant_Name, Species, Toxicity FROM shrubs";
$result = $conn->query($sql);

$shrubs = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shrubs[] = $row;
    }
}

// Return JSON response
echo json_encode($shrubs);

$conn->close();
?>
