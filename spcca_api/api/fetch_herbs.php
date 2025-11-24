<?php
// fetch_herbs.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

include 'db.php';

$sql = "SELECT Plant_Name, Species, Toxicity, image_url FROM plants WHERE Category = 'Herbs'"; // Fetch herbs
$result = $conn->query($sql);

$herbs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $herbs[] = $row;
    }
} else {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit();
}

echo json_encode($herbs);
$conn->close();
?>