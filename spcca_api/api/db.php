<?php
$host = "localhost";
$username = "root";
$password = "";  // Ensure this is correct
$database = "spcca";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
