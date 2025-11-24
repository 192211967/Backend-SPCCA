<?php
header("Content-Type: application/json");
include("db.php"); // Ensure db.php contains a valid database connection

if (!isset($_GET['species']) || empty($_GET['species'])) {
    echo json_encode(["status" => "error", "message" => "Species parameter is missing"]);
    exit;
}

$species = mysqli_real_escape_string($conn, $_GET['species']);

$sql = "SELECT Plant_Name, Species, Category, Watering, Sunlight, Temperature, Humidity, 
               image_url, Fertilizing, Toxicity,Fertilizer 
        FROM plants WHERE Species = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $species);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(["status" => "success", "data" => $row]);
    } else {
        echo json_encode(["status" => "error", "message" => "No plant found for the given species"]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(["status" => "error", "message" => "Query preparation failed"]);
}

mysqli_close($conn);
?>
