<?php
// Database connection parameters
$servername = "localhost";
$username = "root"; // Update as needed
$password = ""; // Update as needed
$database = "spcca";

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $plant_name = $conn->real_escape_string(trim($_POST['Plant_Name']));
    $species = $conn->real_escape_string(trim($_POST['Species']));
    $category = $conn->real_escape_string(trim($_POST['Category']));
    $watering = $conn->real_escape_string(trim($_POST['Watering']));
    $sunlight = $conn->real_escape_string(trim($_POST['Sunlight']));
    $temperature = $conn->real_escape_string(trim($_POST['Temperature']));
    $humidity = $conn->real_escape_string(trim($_POST['Humidity']));
    $fertilizing = $conn->real_escape_string(trim($_POST['Fertilizing']));
    $toxicity = $conn->real_escape_string(trim($_POST['Toxicity']));
    $image_url = $conn->real_escape_string(trim($_POST['image_url']));

    // Validate required fields
    if (empty($plant_name) || empty($species) || empty($category)) {
        die("Plant Name, Species, and Category are required fields.");
    }

    // Prepare the SQL statement
    $sql = "INSERT INTO plants (plant_name, species, category, watering, sunlight, temperature, humidity, fertilizing, toxicity, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Initialize a statement and prepare the SQL query
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters to the SQL query
        $stmt->bind_param("ssssssssss", $plant_name, $species, $category, $watering, $sunlight, $temperature, $humidity, $fertilizing, $toxicity, $image_url);

        // Execute the statement
        if ($stmt->execute()) {
            echo "New plant record created successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    echo "Invalid request method.";
}

// Close the database connection
$conn->close();
?>