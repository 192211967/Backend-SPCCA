<?php
header('Content-Type: application/json');
include 'db.php';

// Accept JSON input if available; otherwise fall back to $_POST
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if ($data !== null) {
    $firstName = $data["First_Name"] ?? "";
    $lastName  = $data["Last_Name"] ?? "";
    $username  = $data["Username"] ?? "";
    $email     = $data["Email"] ?? "";
    $phone     = $data["Phone_No"] ?? "";
    $gender    = $data["Gender"] ?? "";
    $password  = $data["Password"] ?? ""; // No hashing for now; consider hashing in production
} else {
    $firstName = $_POST["First_Name"] ?? "";
    $lastName  = $_POST["Last_Name"] ?? "";
    $username  = $_POST["Username"] ?? "";
    $email     = $_POST["Email"] ?? "";
    $phone     = $_POST["Phone_No"] ?? "";
    $gender    = $_POST["Gender"] ?? "";
    $password  = $_POST["Password"] ?? "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $firstName && $lastName && $username && $email && $phone && $password && $gender) {

    // Prepare statement to insert data
    $stmt = $conn->prepare("INSERT INTO registration (First_Name, Last_Name, Username, Email, Phone_No, Gender, Password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["error" => "SQL error: " . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("sssssss", $firstName, $lastName, $username, $email, $phone, $gender, $password);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Registration successful"]);
    } else {
        echo json_encode(["error" => "Error: " . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["error" => "Invalid request or missing fields"]);
}
?>