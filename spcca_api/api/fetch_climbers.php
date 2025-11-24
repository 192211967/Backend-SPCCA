<?php
// fetch_climbers.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

include 'db.php';

$sql = "SELECT Plant_Name, Species, Toxicity, image_url FROM plants WHERE Category = 'Climbers'"; // Fetch climbers
$result = $conn->query($sql);

$climbers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $climbers[] = $row;
    }
} else {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit();
}

echo json_encode($climbers);
$conn->close();
?>