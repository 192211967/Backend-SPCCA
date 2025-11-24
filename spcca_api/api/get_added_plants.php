<?php
include 'db_connection.php';

$sql = "SELECT * FROM added_plants";
$result = $conn->query($sql);

$plants = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }
}

echo json_encode($plants);
$conn->close();
?>
