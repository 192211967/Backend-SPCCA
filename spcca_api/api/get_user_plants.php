<?php
header('Content-Type: application/json');
require_once 'db.php';

$response = ['success' => false, 'plants' => []];

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    
    try {
        $stmt = $conn->prepare("SELECT id, Name, Plant_Type FROM my_plants WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $response['plants'][] = [
                'id' => $row['id'],
                'name' => $row['Name'],
                'type' => $row['Plant_Type']
            ];
        }
        
        $response['success'] = true;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
}

echo json_encode($response);
?>