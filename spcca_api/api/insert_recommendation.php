<?php
require_once "db.php"; // Include database connection

// Get Public IP for location detection
$publicIP = file_get_contents("https://api64.ipify.org?format=json");
$ipData = json_decode($publicIP, true);
$ip = $ipData['ip'];

$geoApiUrl = "http://ip-api.com/json/$ip";
$response = file_get_contents($geoApiUrl);
$locationData = json_decode($response, true);

if ($locationData['status'] === 'success') {
    $city = $locationData['city'];

    // Fetch weather for this city
    $weatherApiKey = "dabd5af8a15135a0620fb72fd8e23573
";
    $apiUrl = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$weatherApiKey&units=metric";
    $weatherResponse = file_get_contents($apiUrl);
    $weatherData = json_decode($weatherResponse, true);

    if ($weatherData && isset($weatherData['main']['humidity'])) {
        $humidity = $weatherData['main']['humidity'];
        $rainfall = isset($weatherData['rain']['1h']) ? $weatherData['rain']['1h'] : 0;

        // Define watering adjustment rules
        $weatherAdjustment = 0;
        if ($rainfall > 2) {
            $weatherAdjustment = -2; // Delay watering
        } elseif ($humidity > 70) {
            $weatherAdjustment = -1; // Delay slightly
        } elseif ($humidity < 30) {
            $weatherAdjustment = +1; // Water earlier
        }

        // Update database
        $query = "UPDATE water_history SET weather_adjustment = $weatherAdjustment";
        if (mysqli_query($conn, $query)) {
            echo "Weather adjustment updated: $weatherAdjustment days for $city";
        } else {
            echo "Error updating database: " . mysqli_error($conn);
        }
    } else {
        echo "Failed to fetch weather data.";
    }
} else {
    echo "Location detection failed.";
}

mysqli_close($conn);
?>
