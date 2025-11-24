<?php
// fetch_creepers.php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

include 'db.php';

$sql = "SELECT Plant_Name, Species, Toxicity, image_url FROM plants WHERE Category = 'Creepers'"; // Fetch creepers
$result = $conn->query($sql);

$creepers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $creepers[] = $row;
    }
} else {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit();
}

echo json_encode($creepers);
$conn->close();
?>