<?php
// Update MySQL
$conn = new mysqli("localhost", "root", "", "spcca");
$id = 1;
$name = "Updated Name";
$conn->query("UPDATE users SET name='$name' WHERE id=$id");

// Update Firebase
$data = json_encode(["name" => $name]);
$firebaseUrl = "https://plant-e9716-default-rtdb.firebaseio.com/users/$id.json";

$ch = curl_init($firebaseUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH"); // Or PUT
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Updated MySQL and Firebase.";
?>
